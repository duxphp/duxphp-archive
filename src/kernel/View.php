<?php
/**
 * 模板引擎
 */

namespace dux\kernel;


class View {
    /**
     * 模板路径
     * @var string
     */
    public $path;

    /**
     * 模板赋值
     * @var array
     */
    protected $vars = [];

    /**
     * 模板文件
     * @var string
     */
    private $template;

    /**
     * 模板配置
     * @var array
     */
    protected $config = [];

    /**
     * 缓存对象
     * @var null
     */
    protected $cache = null;

    /**
     * 标签定界符
     */
    protected $__ldel = '{';
    protected $__rdel = '}';
    protected $__ltag = '<!--';
    protected $__rtag = '-->';
    private $_template_preg = [], $_template_replace = [];

    /**
     * 初始化类
     * View constructor.
     * @param array $config
     */
    public function __construct(array $config = []) {
        $this->config = array_merge($this->config, $config);
        $this->cache = \dux\Dux::cache('tpl');
        $this->set('__Template', $this);

    }

    /**
     * 获取模板赋值
     * @param mixed $key 键名
     * @return mixed Value
     */
    public function get($key) {
        return isset($this->vars[$key]) ? $this->vars[$key] : null;
    }

    /**
     * 设置模板赋值
     * @param mixed $key 键名
     * @param string $value 键值
     */
    public function set($key, $value = null) {
        if (is_array($key) || is_object($key)) {
            foreach ($key as $k => $v) {
                $this->vars[$k] = $v;
            }
        } else {
            $this->vars[$key] = $value;
        }
    }

    /**
     * 检查赋值存在
     * @param string $key 键名
     * @return boolean
     */
    public function has($key) {
        return isset($this->vars[$key]);
    }

    /**
     * 清除模板赋值
     * @param string $key 键名
     */
    public function clear($key = null) {
        if (is_null($key)) {
            $this->vars = [];
        } else {
            unset($this->vars[$key]);
        }
    }

    /**
     * 模板编译
     * @param string $filePath
     * @param array $data
     * @return false|string
     */
    public function compile(string $filePath, array $data = []) {
        if (!empty($data)) {
            $this->vars = array_merge($this->vars, $data);
        }
        $fileName = 'tpl.' . md5($filePath);
        $fileTime = filemtime($filePath);
        $cache = $this->cache->get($fileName);
        $cache = json_decode($cache, true);
        if (empty($cache) || $fileTime > $cache['time']) {
            $template = file_get_contents($filePath);
            $template = $this->templateParse($template);
            $value = json_encode(['time' => $fileTime, 'tpl' => $template]);
            $this->cache->set($fileName, $value);
        } else {
            $template = $cache['tpl'];
        }
        return $template;
    }

    /**
     * 模板输出
     * @param string $file
     * @param array $data
     * @param bool $type
     * @throws \Exception
     */
    public function render(string $file, array $data = [], bool $type = false) {
        if(!$type) {
            $this->exists($file);
            $template = $this->compile($this->template, $data);
        }else {
            if (!empty($data)) {
                $this->vars = array_merge($this->vars, $data);
            }
            $template = $this->templateParse($file);
        }
        extract($this->vars);
        eval('?>' . $template);
    }

    /**
     * 返回渲染
     * @param string $file
     * @param array $data
     * @param bool $type
     * @return false|string
     * @throws \Exception
     */
    public function fetch(string $file, array $data = [], bool $type = false) {
        ob_start();
        $this->render($file, $data, $type);
        return ob_get_clean();
    }

    /**
     * 检查模板存在
     * @param string $file
     * @throws \Exception
     */
    public function exists(string $file) {
        $this->template = $this->getTemplate($file);
        if (!file_exists($this->template)) {
            throw new \Exception("Template file not found: {$this->template}.");
        }
    }

    /**
     * 获取模板路径
     * @param string $file
     * @return string
     */
    public function getTemplate(string $file) {
        if(strripos($file,'.') === false){
            $file .= '.html';
        }
        return $file;
    }

    /**
     * HTML还原
     * @param string $str
     */
    public function e(string $str) {
        echo htmlentities($str);
    }

    /**
     * 添加标签
     * @param callable $callback
     * @return array
     */
    public function addTag(callable $callback) {
        $tag = $callback([$this->__ltag, $this->__rtag], [$this->__ldel, $this->__rdel]);
        if (empty($tag)) {
            return [];
        }
        foreach ($tag as $key => $vo) {
            $this->_template_preg[] = $key;
            $this->_template_replace[] = $vo;
        }
    }

    /**
     * 设置系统标签
     */
    public function setTags() {
        //替换判断
        $this->_template_preg[] = '/' . $this->__ltag . "if\{(.*?)\}" . $this->__rtag . '/i';
        $this->_template_preg[] = '/' . $this->__ltag . '\{else\}' . $this->__rtag . '/i';
        $this->_template_preg[] = '/' . $this->__ltag . '(else if|elseif)\{(.*?)\}' . $this->__rtag . '/i';

        $this->_template_replace[] = '<?php if (\\1){ ?>';
        $this->_template_replace[] = '<?php }else{ ?>';
        $this->_template_replace[] = '<?php }else if (\\2){ ?>';

        //替换循环
        $this->_template_preg[] = '/' . $this->__ltag . '(loop|foreach)\{(.*?)\}' . $this->__rtag . '/i';
        $this->_template_replace[] = '<?php foreach (\\2) { ?>';
        $this->_template_preg[] = '/' . $this->__ltag . 'for\{(.*?)\}' . $this->__rtag . '/i';
        $this->_template_replace[] = '<?php for (\\1) { ?>';

        $this->_template_preg[] = '/<(.*?)dux-loop=[\'|"](.*?)[\'|"](.*?)>([\s\S]*?)<\/(.*?)>/';
        $this->_template_replace[] = [$this, 'parseLoop'];


        $this->_template_preg[] = '/<(.*?)dux-attr=\"(.*?)\"(.*?)(>|\/>)/';
        $this->_template_replace[] = [$this, 'parseAttr'];

        //注释标签
        $this->_template_preg[] = '/' . $this->__ltag . '\{(\#|\*)(.*?)(\#|\*)\}' . $this->__ltag . '/';
        $this->_template_replace[] = '';

        //引入页面
        $this->_template_preg[] = '/' . $this->__ltag . 'include\{(.*)\}' . $this->__rtag . '/i';
        $this->_template_replace[] = "<?php \$__Template->render(\"$1\"); ?>";

        //替换变量
        $this->_template_preg[] = '/\$\w+((\.\w+)*)?/';
        $this->_template_replace[] = [$this, 'parseVar'];

        $this->_template_preg[] = '/' . $this->__ldel . '((( *(\+\+|--) *)*?(([_a-zA-Z][\w]*\(.*?\))|\$((\w+)((\[|\()(\'|")?\$*\w*(\'|")?(\)|\]))*((->)?\$?(\w*)(\((\'|")?(.*?)(\'|")?\)|))){0,})( *\.?[^ \.]*? *)*?){1,})' . $this->__rdel . '/i';
        $this->_template_replace[] = '<?php echo \\1; ?>';

        //结束符号
        $this->_template_preg[] = '/' . $this->__ltag . '\{\/(.*?)\}' . $this->__rtag . '/i';
        $this->_template_replace[] = '<?php } ?>';

        //替换通用循环
        $this->_template_preg[] = '/' . $this->__ltag . '(\w+)\{([^"].*)\}' . $this->__rtag . '/i';
        $this->_template_replace[] = [$this, 'parseFor'];

        //替换常量
        $this->_template_preg[] = '/__PUBLIC__/';
        $this->_template_replace[] = ROOT_URL . '/public';

        $this->_template_preg[] = '/__ROOT__/';
        $this->_template_replace[] = ROOT_URL;

        $this->_template_preg[] = '/__APP__/';
        $this->_template_replace[] = ROOT_URL . '/app';

        //替换JS压缩
        $this->_template_preg[] = '/\/\/js-compress([\s\S]*?)\/\/js-end/';
        $this->_template_replace[] = [$this, 'parseJs'];

        //scss解析
        $this->_template_preg[] = '/\/\*[\s]+scss[\s]+\*\/([\s\S]*?)\/\*[\s]+end[\s]+scss[\s]+\*\//';
        $this->_template_replace[] = [$this, 'parseScss'];

        //自动渲染
        $this->_template_preg[] = '/<(.*?)dux-auto(.*?)>([\s\S]*?)<\/(.*?)>/';
        $this->_template_replace[] = [$this, 'parseAutoLabel'];

    }

    /**
     * 模板解析
     * @param  string $template 模板内容
     * @return string
     */
    public function templateParse(string $template) {
        $this->setTags();
        foreach ($this->_template_preg as $key => $vo) {
            if (is_array($this->_template_replace[$key])) {
                $template = preg_replace_callback($vo, $this->_template_replace[$key], $template);
            } else {

                $template = preg_replace($vo, $this->_template_replace[$key], $template);
            }
        }
        return trim($template);
    }

    private function parseAttr($var) {
        return  "<" . $var[1] . "<?php echo " . $var[2] . "; ?>" . $var[3] . $var[4];
    }

    private function parseLoop($var) {
        return  "<?php foreach (".$var[2].") { ?>" . "\n<" . $var[1] . $var[3] . ">\n" . $var[4] . "\n</" . $var[5] . "><?php } ?>";
    }

    private function parseAutoLabel($var) {
        $html = "<" . $var[1] . $var[2] . ">\n";
        $label = $var[4];
        if ($label == 'style') {
            $html = str_replace('scss', 'css', $html);
            $scss = new \Leafo\ScssPhp\Compiler();
            $html .= $scss->compile($var[3]);
        } elseif ($label == 'script') {
            $packer = new \dux\vendor\Packer($var[3], 'Normal', true, false, true);
            $html .= $packer->pack();
        }else {
            $html .= $var[3];
        }
        $html .= "\n</" . $label . ">";
        return $html;
    }

    private function parseScss($var) {
        return build_scss($var[1]);
    }
    
    private function parseJs($var) {
        return pack_js($var[1]);
    }

    private function parseVar($var) {
        if (empty($var[0])) {
            return;
        }
        $vars = explode('.', $var[0]);
        $var = array_shift($vars);
        $name = $var;
        foreach ($vars as $val) {
            $name .= '["' . $val . '"]';
        }
        return $name;
    }

    private function parseFor($var) {
        $tpl = trim($var[2]);
        $item = trim($var[1]);
        $tpl = ' ' . $tpl;
        $tpl = preg_replace(" /\s([_a-zA-Z]+)=/", ', "\1"=>', $tpl);
        $tpl = substr($tpl, 1);
        //匹配必要参数
        $dataArray = [];
        if (preg_match_all('/\s"([_a-zA-Z]+)"=>"(.+?)"/', $tpl, $result)) {
            foreach ($result[1] as $key => $value) {
                $dataArray[$value] = $result[2][$key];
            }
        }
        //生成模块调用
        $html = '<?php $' . $item . 'List = target("' . strtolower($dataArray['app']) . '/Label", "service")->' . ucfirst($dataArray['label']) . '([' . $tpl . ']); ';
        switch ($item) {
            case 'echo':
                $html .= ' echo $' . $item . 'List; ?>';
                break;
            case 'assign':
                $html .= '$' . $dataArray['list'] . ' = $' . $item . 'List; ?>';
                break;
            default:
                $html .= 'if(is_array($' . $item . 'List)) foreach($' . $item . 'List as $' . $item . 'Key =>  $' . $item . '){ $' . $item . '["i"] = $' . $item . 'Key + 1;  ?>';
                break;
        }
        return $html;
    }

}

