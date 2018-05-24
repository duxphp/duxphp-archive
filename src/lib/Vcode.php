<?php
// +----------------------------------------------------------------------
// | Name:Vcode验证码类
// +----------------------------------------------------------------------
// | version 2.0 更新:支持中文验证码
// +----------------------------------------------------------------------
// | http://silenceper.com/vcode
// +----------------------------------------------------------------------
// | Author: silenceper
// +----------------------------------------------------------------------
// | Date  : 2013-01-21 18:37
// +----------------------------------------------------------------------
namespace dux\lib;
class Vcode {
    //图片资源
    private $image = null;
    //生成的验证码字的个数
    public $codeNum;
    //验证码高度
    public $height;
    //验证码宽度
    public $width;
    //干扰元素数量
    private $disturbColorNum;
    //生成的code
    public $code = '';
    //是否生成中文验证码
    private $chinese;
    //字体路径
    private $fontFace = '';
    //单例模式
    private static $instance = null;
    //验证码名称
    private $vcodeNme = 'vCode';


    /**架构函数
     *
     * Vcode constructor.
     * @param int $width 验证码宽度 默认120
     * @param int $height 验证码高度 默认 35
     * @param int $codeNum 验证码数量 默认4
     */
    public function __construct($width = 120, $height = 35, $codeNum = 4, $fontFace = '', $vcodeName = '') {
        //初始化
        $this->width = $width;
        $this->height = $height;
        $this->codeNum = $codeNum;
        //字体
        if ($fontFace) {
            $this->fontFace = $fontFace;
        }
        //session名
        if ($vcodeName) {
            $this->vcodeName = $vcodeName;
        }
        //设置干扰元素数量
        $number = floor($width * $height / 20);
        if ($number > 240 - $codeNum) {
            $this->disturbColorNum = 240 - $codeNum;
        } else {
            $this->disturbColorNum = $number;
        }
    }


    /** 显示验证码图片
     * @param string $fontFace
     * @param bool $chinese 是否为中文
     */
    public function showImage($chinese = false) {
        $this->chinese = $chinese;
        //创建图片资源
        $this->createImage();
        //设置干扰颜色
        $this->setDisturbColor();
        //生成code
        $this->createCode();
        //写入SESSION
        \Dux::session()->set($this->vcodeName, $this->code);

        //往图片上添加文本
        $this->outputText($this->fontFace);
        //输出图像
        $this->ouputImage();
    }

    /**
     * 较验验证码
     * @param string $vCode
     * @return bool
     */
    public function check($vCode = '') {
        if (empty($vCode)) {
            return false;
        }
        if (empty($this->chinese)) {
            $vCode = strtolower($vCode);
        }
        $code = strtolower(\Dux::session()->get($this->vcodeName));
        if ($vCode <> $code) {
            return false;
        }
        return true;
    }

    /**
     *创建图片 无边框
     */
    private function createImage() {
        //生成图片资源
        $this->image = imagecreatetruecolor($this->width, $this->height);
        //画出图片背景
        $backColor = imagecolorallocate($this->image, mt_rand(255, 255), mt_rand(255, 255), mt_rand(255, 255));
        imagefill($this->image, 0, 0, $backColor);
    }

    /**
     *设置干扰颜色
     */
    private function setDisturbColor() {
        //画出点干扰
        for ($i = 0; $i <= $this->disturbColorNum; $i++) {
            $pixelColor = imagecolorallocate($this->image, mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255));
            imagesetpixel($this->image, mt_rand(0, $this->width), mt_rand(0, $this->height), $pixelColor);
        }
        //画出干扰线 待续

    }

    //往图片上添加文本
    private function outputText($fontFace = '') {
        //画出code
        for ($i = 0; $i < $this->codeNum; $i++) {
            $fontColor = imagecolorallocate($this->image, mt_rand(0, 155), mt_rand(0, 155), mt_rand(0, 155));
            //设置了fontFace 则使用imagettftext
            if ($fontFace) {
                $fontSize = mt_rand($this->width / $this->codeNum - 8, $this->width / $this->codeNum - 7);
                $x = floor(($this->width - 4) / $this->codeNum) * $i + 5;
                $y = mt_rand($fontSize, $this->height - 2);
                imagettftext($this->image, $fontSize, mt_rand(-30, 30), $x, $y, $fontColor, $fontFace, self::msubstr($this->code, $i));

            } else {
                //没有设置 fontFace 则使用 imagechar
                $fontSize = 20;
                $x = floor($this->width / $this->codeNum) * $i + 3;
                $y = mt_rand(0, $this->height - 20);
                imagechar($this->image, $fontSize, $x, $y, $this->code{$i}, $fontColor);
            }
        }
    }

    //生成code
    private function createCode() {
        if (!$this->chinese) {
            $string = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        } else {
            $string = '们以我到他会作时要动国产的一是工就年阶义发成部民可出能方进在了不和有大这主中人上为来分生对于学下级地个用同行面说种过命度革而多子后自社加小机也经力线本电高量长党得实家定深法表着水理化争现所二起政三好十战无农使性前等反体合斗路图把结第里正新开论之物从当两些还天资事队批点育重其思与间内去因件日利相由压员气业代全组数果期导平各基或月毛然如应形想制心样干都向变关问比展那它最及外没看治提五解系林者米群头意只明四道马认次文通但条较克又公孔领军流入接席位情运器并飞原油放立题质指建区验活众很教决特此常石强极土少已根共直团统式转别造切九你取西持总料连任志观调七么山程百报更见必真保热委手改管处己将修支识病象几先老光专什六型具示复安带每东增则完风回南广劳轮科北打积车计给节做务被整联步类集号列温装即毫知轴研单色坚据速防史拉世设达尔场织历花受求传口断况采精金界品判参层止边清至万确究书术状厂须离再目海交权且儿青才证低越际八试规斯近注办布门铁需走议县兵固除般引齿千胜细影济白格效置推空配刀叶率述今选养德话查差半敌始片施响收华觉备名红续均药标记难存测士身紧液派准斤角降维板许破述技消底床田势端感往神便贺村构照容非搞亚磨族火段算适讲按值美态黄易彪服早班麦削信排台声该击素张密害侯草何树肥继右属市严径螺检左页抗苏显苦英快称坏移约巴材省黑武培著河帝仅针怎植京助升王眼她抓含苗副杂普谈围食射源例致酸旧却充足短划剂宣环落首尺波承粉践府鱼随考刻靠够满夫失包住促枝局菌杆周护岩师举曲春元超负砂封换太模贫减阳扬江析亩木言球朝医校古呢稻宋听唯输滑站另卫字鼓刚写刘微略范供阿块某功套友限项余倒卷创律雨让骨远帮初皮播优占死毒圈伟季训控激找叫云互跟裂粮粒母练塞钢顶策双留误础吸阻故寸盾晚丝女散焊功株亲院冷彻弹错散商视艺灭版烈零室轻血倍缺厘泵察绝富城冲喷壤简否柱李望盘磁雄似困巩益洲脱投送奴侧润盖挥距触星松送获兴独官混纪依未突架宽冬章湿偏纹吃执阀矿寨责熟稳夺硬价努翻奇甲预职评读背协损棉侵灰虽矛厚罗泥辟告卵箱掌氧恩爱停曾溶营终纲孟钱待尽俄缩沙退陈讨奋械载胞幼哪剥迫旋征槽倒握担仍呀鲜吧卡粗介钻逐弱脚怕盐末阴丰雾冠丙街莱贝辐肠付吉渗瑞惊顿挤秒悬姆烂森糖圣凹陶词迟蚕亿矩康遵牧遭幅园腔订香肉弟屋敏恢忘编印蜂急拿扩伤飞露核缘游振操央伍域甚迅辉异序免纸夜乡久隶缸夹念兰映沟乙吗儒杀汽磷艰晶插埃燃欢铁补咱芽永瓦倾阵碳演威附牙芽永瓦斜灌欧献顺猪洋腐请透司危括脉宜笑若尾束壮暴企菜穗楚汉愈绿拖牛份染既秋遍锻玉夏疗尖殖井费州访吹荣铜沿替滚客召旱悟刺脑措贯藏敢令隙炉壳硫煤迎铸粘探临薄旬善福纵择礼愿伏残雷延烟句纯渐耕跑泽慢栽鲁赤繁境潮横掉锥希池败船假亮谓托伙哲怀割摆贡呈劲财仪沉炼麻罪祖息车穿货销齐鼠抽画饲龙库守筑房歌寒喜哥洗蚀废纳腹乎录镜妇恶脂庄擦险赞钟摇典柄辩竹谷卖乱虚桥奥伯赶垂途额壁网截野遗静谋弄挂课镇妄盛耐援扎虑键归符庆聚绕摩忙舞遇索顾胶羊湖钉仁音迹碎伸灯避泛亡答勇频皇柳哈揭甘诺概宪浓岛袭谁洪谢炮浇斑讯懂灵蛋闭孩释乳巨徒私银伊景坦累匀霉杜乐勒隔弯绩招绍胡呼痛峰零柴簧午跳居尚丁秦稍追梁折耗碱殊岗挖氏刃剧堆赫荷胸衡勤膜篇登驻案刊秧缓凸役剪川雪链渔啦脸户洛孢勃盟买杨宗焦赛旗滤硅炭股坐蒸凝竟陷枪黎救冒暗洞犯筒您宋弧爆谬涂味津臂障褐陆啊健尊豆拔莫抵桑坡缝警挑污冰柬嘴啥饭塑寄赵喊垫丹渡耳刨虎笔稀昆浪萨茶滴浅拥穴覆伦娘吨浸袖珠雌妈紫戏塔锤震岁貌洁剖牢锋疑霸闪埔猛诉刷狠忽灾闹乔唐漏闻沈熔氯荒茎男凡抢像浆旁玻亦忠唱蒙予纷捕锁尤乘乌智淡允叛畜俘摸锈扫毕璃宝芯爷鉴秘净蒋钙肩腾枯抛轨堂拌爸循诱祝励肯酒绳穷塘燥泡袋朗喂铝软渠颗惯贸粪综墙趋彼届墨碍启逆卸航衣孙龄岭骗休借';
        }
        for ($i = 0; $i < $this->codeNum; $i++) {
            //$char = self::msubstr($string, mt_rand(0, mb_strlen($string, 'utf-8') - 1));
            $char=$string{mt_rand(0,mb_strlen($string)-1)};
            $this->code .= $char;
        }
    }

    //输出图像
    private function ouputImage() {
        ob_clean();    //防止出现'图像因其本身有错无法显示'的问题
        if (imagetypes() & IMG_GIF) {
            header("Content-Type:image/gif");
            imagepng($this->image);
        } else if (imagetypes() & IMG_JPG) {
            header("Content-Type:image/jpeg");
            imagepng($this->image);
        } else if (imagetypes() & IMG_PNG) {
            header("Content-Type:image/png");
            imagepng($this->image);
        } else if (imagetypes() & IMG_WBMP) {
            header("Content-Type:image/vnd.wap.wbmp");
            imagepng($this->image);
        } else {
            die("PHP不支持图像创建");
        }
    }

    /*
     * msubstr() 截取字符串
     *
     */
    static private function msubstr($str, $start = 0, $length = 1, $charset = "utf-8") {
        if (function_exists("mb_substr"))
            $slice = mb_substr($str, $start, $length, $charset);
        elseif (function_exists('iconv_substr')) {
            $slice = iconv_substr($str, $start, $length, $charset);
        } else {
            $re['utf-8'] = "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|[\xe0-\xef][\x80-\xbf]{2}|[\xf0-\xff][\x80-\xbf]{3}/";
            $re['gb2312'] = "/[\x01-\x7f]|[\xb0-\xf7][\xa0-\xfe]/";
            $re['gbk'] = "/[\x01-\x7f]|[\x81-\xfe][\x40-\xfe]/";
            $re['big5'] = "/[\x01-\x7f]|[\x81-\xfe]([\x40-\x7e]|\xa1-\xfe])/";
            preg_match_all($re[$charset], $str, $match);
            $slice = join("", array_slice($match[0], $start, $length));
        }
        return $slice;
    }


    public static function getInstance($width = 120, $height = 35, $codeNum = 4, $fontFace = '') {
        if (self::$instance === null) {
            self::$instance = new self($width, $height, $codeNum, $fontFace);
        }
        return self::$instance;
    }
}