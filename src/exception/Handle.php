<?php
/**
 * 异常处理
 */

namespace dux\Exception;

class Handle {

    protected $debug;
    protected $error;
    protected $log;
    protected $message;
    protected $code;
    protected $file;
    protected $line;
    protected $trace;

    /**
     * Handle constructor.
     * @param string $message
     * @param int $code
     * @param string $file
     * @param string $line
     * @param array $trace
     * @param bool $debug
     * @param bool $error
     * @param bool $log
     */
    public function __construct($message = '', $code = 0, $file = '', $line = '', $trace = [], $debug = true, $error = true, $log = true) {
        $this->message = $message;
        $this->code = $code;
        $this->file = $file;
        $this->line = $line;
        $this->trace = $trace;
        $this->debug = $debug;
        $this->error = $error;
        $this->log = $log;
        $this->render();
    }

    public function render() {
        $title = "{$this->message}";
        $desc = "line {$this->line} in file {$this->parserFile($this->file)}";
        $code = $this->code ?: 500;
        $trace = [];
        foreach ($this->trace as $value) {
            if (empty($value['file'])) {
                continue;
            }
            $trace[] = [
                'file' => $value['file'],
                'line' => $value['line'],
            ];
        }
        if ($this->log) {
            \dux\Dux::log($title . ' ' . $desc);
        }
        if (IS_CLI) {
            return "error: {$title} : {$desc}";
        }
        if (isAjax()) {
            if (!$this->debug) {
                $title = \dux\Dux::$codes[$code];
            }
            $data = [
                'code' => $code,
                'message' => $title,
                'line' => $desc,
                'trace' => $trace,
            ];
            \dux\Dux::header($code, function () use ($data) {
                return json_encode($data);
            }, [
                'Content-Type' => 'application/json; charset=UTF-8'
            ]);
        }
        if ($this->debug) {
            $content = file_get_contents($this->file);
            $content = explode(PHP_EOL, $content);
            $sumLine = count($content);
            $startLine = $this->line - 10;
            $curLine = $startLine >= 0 ? 9 : 9 + $startLine;
            $startLine = $startLine > 0 ? $startLine : 0;
            $fragment = array_slice($content, $startLine, 20);
            $fragment = implode(PHP_EOL, $fragment);
            $highlighter = new \Highlight\Highlighter();
            $highlighted = $highlighter->highlight('php', $fragment);
            $codeList = explode(PHP_EOL, $highlighted->value);
            foreach ($codeList as $key => $vo) {
                if ($curLine == $key) {
                    $codeList[$key] = "<div class='code-cur'>{$vo}</div>";
                } else {
                    $codeList[$key] = $vo . PHP_EOL;
                }
            }
            $codeList = implode('', $codeList);

            $html = [];
            $html[] = "<style>xx {lh}";
            $html[] = ".hljs-comment,.hljs-quote{color:#d4d0ab}.hljs-variable,.hljs-template-variable,.hljs-tag,.hljs-name,.hljs-selector-id,.hljs-selector-class,.hljs-regexp,.hljs-deletion{color:#ffa07a}.hljs-number,.hljs-built_in,.hljs-builtin-name,.hljs-literal,.hljs-type,.hljs-params,.hljs-meta,.hljs-link{color:#f5ab35}.hljs-attribute{color:#ffd700}.hljs-string,.hljs-symbol,.hljs-bullet,.hljs-addition{color:#abe338}.hljs-title,.hljs-section{color:#00e0e0}.hljs-keyword,.hljs-selector-tag{color:#dcc6e0}.hljs{display:block;overflow-x:auto;background:#2b2b2b;color:#f8f8f2;padding:1em}.hljs-emphasis{font-style:italic}.hljs-strong{font-weight:bold}@media screen and (-ms-high-contrast:active){.hljs-addition,.hljs-attribute,.hljs-built_in,.hljs-builtin-name,.hljs-bullet,.hljs-comment,.hljs-link,.hljs-literal,.hljs-meta,.hljs-number,.hljs-params,.hljs-string,.hljs-symbol,.hljs-type,.hljs-quote{color:highlight}.hljs-keyword,.hljs-selector-tag{font-weight:bold}}";
            $html[] = "a {color:#333; text-decoration: none; margin-left: 10px; margin-right: 10px;}";
            $html[] = "ul {background: #f5f5f5; padding: 15px; line-height: 25px; padding-left: 50px; margin-top: 0; color: #333;overflow-x: auto;display: block;white-space:nowrap;}";
            $html[] = "ul li b {width: 50px; display: inline-block; text-align: center;}";
            $html[] = "h1 {display:block; line-height:30px; word-wrap: break-word; word-break: break-all;}";
            $html[] = "h1 code{padding:2px 5px; background:#dd4250;color:#fff; font-size:24px; position: relative; top:-3px; margin-right: 10px;}";
            $html[] = "h1 span { }";
            $html[] = "h2 {padding: 10px 15px; background: #ddd; font-size:16px; margin: 0;}";
            $html[] = "pre code {display:block;}";
            $html[] = "pre {display:block; margin-bottom: 0;}";
            $html[] = "pre code .code-cur{ margin-bottom: 0; background:rgba(255,0,0,.4); margin-top: 3px;line-height: 20px;}";
            $html[] = ".pre-foot {background: #444;padding:5px 10px; text-align: right; font-size:14px; margin-bottom: 1em;}";
            $html[] = ".pre-foot a {color: #fff;}";
            $html[] = "footer {color:#999;}";
            $html[] = "</style>";
            $html[] = "<title>{$title}</title>";
            $html[] = "<main style='max-width: 860px; margin-left: auto; margin-right: auto;'>";
            $html[] = "<div style='margin-top: 40px; margin-bottom: 40px;'><h1><code>{$code}</code><span>{$title}</span></h1></div>";
            $html[] = "<h2>{$desc}</h2>";
            $html[] = "<pre><code class='hljs {$highlighted->language}'>{$codeList}</code></pre>";
            $html[] = "<div class='pre-foot'><a href='idea://open?file={$this->file}&line={$this->line}'>[Idea]</a> <a href='vscode://file/{$this->file}:{$this->line}'>[VScode]</a></div>";

            if ($trace) {
                $html[] = "<ul>";
                foreach ($trace as $vo) {
                    $html[] = "<li>line <b style=''>{$vo['line']}</b>  in file {$vo['file']}</li>";
                }
                $html[] = "</ul>";
            }
            $html[] = "<footer>DuxPHP " . VERSION . ", run time " . \dux\Dux::runTime() . "s</footer>";
            $html[] = "</main>";
            $html = implode(PHP_EOL, $html);
        } else {
            if (!$this->error) {
                $title = '内部服务器错误!';
            }
            $html = file_get_contents(CORE_PATH . 'tpl/error.html');
            $html = str_replace('{$title}', $title, $html);
        }
        return \dux\Dux::header(500, function () use ($html) {
            return $html;
        }, [
            'Content-Type' => 'text/html; charset=UTF-8'
        ]);
    }


    public static function parserFile($file) {
        return str_replace(ROOT_PATH, '/', $file);
    }

}