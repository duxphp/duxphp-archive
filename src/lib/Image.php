<?php

/**
 * 图像处理类
 */

namespace dux\lib;

class Image {


    /**
     * 图像标识
     * @var string
     */
    protected $md5;

    /**
     * 操作对象
     * @var string
     */
    protected $object;

    /**
     * 图像对象
     * @var object
     */
    protected $imgObj;

    /**
     * 参数配置
     * @var array
     */
    protected $config = [
        'type' => 'imagick',
        'font' => ''
    ];

    /**
     * 构建函数
     * @param null $img
     * @param array $config
     */
    public function __construct($img = null, array $config = []) {
        $this->config = array_merge($this->config, $config);
        if ($img) {
            $this->imgObj = $this->getObj()->make($img);
        }
    }

    /**
     * 图片缩放
     * @param int $width
     * @param int $height
     * @param $type
     * @return $this
     */
    public function thumb(int $width, int $height, $type = 0) {
        switch ($type) {
            // 居中裁剪缩放
            case 1:
            case 'center':
                $this->imgObj->fit($width, $height, function ($constraint) {
                    $constraint->upsize();
                }, 'center');
                break;
            // 固定尺寸
            case 2:
            case 'fixed':
                $this->imgObj->resize(300, 200, function ($constraint) {
                    $constraint->upsize();
                });
                break;
            // 等比例缩放
            case 0:
            case 'scale':
            default:
                if ($width > $height) {
                    $this->imgObj->resize(null, $height, function ($constraint) {
                        $constraint->aspectRatio();
                        $constraint->upsize();
                    });
                } else {
                    $this->imgObj->resize($width, null, function ($constraint) {
                        $constraint->aspectRatio();
                        $constraint->upsize();
                    });
                }
        }
        return $this;
    }

    /**
     * 图片裁剪
     * @param int $width
     * @param int $height
     * @param int $x
     * @param int $y
     * @return $this
     */
    public function crop(int $width, int $height, int $x = 0, int $y = 0) {
        $this->imgObj->crop($width, $height, $x, $y);
        return $this;
    }

    /**
     * 圆形裁剪
     * @return $this
     */
    public function circle() {
        $width = $this->imgObj->width();
        $height = $this->imgObj->height();
        $mask = $this->circleMask($width, $height);
        $this->imgObj->mask($mask);
        return $this;
    }

    /**
     * 圆形遮罩
     * @param int $width
     * @param int $height
     * @return \Intervention\Image\Image
     */
    private function circleMask(int $width, int $height) {
        $bigWidth = $width * 2;
        $bigHeight = $height * 2;
        $circle = $this->getObj()->canvas($bigWidth, $bigHeight, '#000000');
        $circle = $circle->circle($bigWidth - 1, $bigWidth / 2, $bigHeight / 2, function ($draw) {
            $draw->background('#ffffff');
        });
        return $circle->resize($width, $height);
    }

    /**
     * 图片水印
     * @param $source
     * @param $locate
     * @param int $alpha
     * @return $this
     */
    public function water($source, $locate = 0, int $alpha = 80) {
        $position = 'center';
        switch ($locate) {
            //左上角水印
            case 1:
            case 'top-left':
                $position = 'top-left';
                break;
            //上居中水印
            case 2:
            case 'top':
                $position = 'top';
                break;
            //右上角水印
            case 3:
            case 'top-right':
                $position = 'top-right';
                break;
            //左居中水印
            case 4:
            case 'left':
                $position = 'left';
                break;
            //居中水印
            default:
            case 5:
            case 'center':
                $position = 'center';
                break;
            //右居中水印
            case 6:
            case 'right':
                $position = 'right';
                break;
            //左下角水印
            case 7:
            case 'bottom-left':
                $position = 'bottom-left';
                break;
            //下居中水印
            case 8:
            case 'bottom':
                $position = 'bottom';
                break;
            //右下角水印
            case 9:
            case 'bottom-right':
                $position = 'bottom-right';
                break;
        }
        $watermark = $this->getObj()->make($source)->opacity($alpha);
        $this->imgObj->insert($watermark, $position, 10, 10);
        return $this;
    }

    /**
     * 文字水印
     * @param string $text
     * @param int $size
     * @param string $color
     * @param int $locate
     * @param int $padding
     * @return $this
     */
    public function text(string $text, int $size = 16, string $color = '#000000', int $locate = 9, int $padding = 10) {
        switch ($locate) {
            //左上角水印
            case 1:
                $x = $padding;
                $y = $padding;
                $align = 'left';
                $valign = 'top';
                break;
            //上居中水印
            case 2:
                $x = round($this->imgObj->width() / 2);
                $y = $padding;
                $align = 'center';
                $valign = 'top';
                break;
            case 3:
                //右上角水印
                $x = $this->imgObj->width() - $padding;
                $y = $padding;
                $align = 'right';
                $valign = 'top';
                break;
            //左居中水印
            case 4:
                $x = $padding;
                $y = round($this->imgObj->height() / 2);
                $align = 'left';
                $valign = 'center';
                break;
            //居中水印
            case 5:
                $x = round($this->imgObj->width() / 2);
                $y = round($this->imgObj->height() / 2);
                $align = 'center';
                $valign = 'center';
                break;
            //右居中水印
            case 6:
                $x = $this->imgObj->width() - $padding;
                $y = round($this->imgObj->height() / 2);
                $align = 'right';
                $valign = 'center';
                break;
            //左下角水印
            case 7:
                $x = $padding;
                $y = $this->imgObj->height() - $padding;
                $align = 'left';
                $valign = 'bottom';
                break;
            //下居中水印
            case 8:
                $x = round($this->imgObj->width() / 2);
                $y = $this->imgObj->height() - $padding;
                $align = 'center';
                $valign = 'bottom';
                break;
            //右下角水印
            default:
            case 9:
                $x = $this->imgObj->width() - $padding;
                $y = $this->imgObj->height() - $padding;
                $align = 'right';
                $valign = 'bottom';
                break;
        }
        $this->imgObj->text($text, $x, $y, function ($font) use ($size, $color, $align, $valign) {
            $font->file($this->config['font']);
            $font->size($size);
            $font->color($color);
            $font->align($align);
            $font->valign($valign);
        });
        return $this;
    }


    /**
     * 图片合成
     * @param array $data
     * @return $this
     */
    public function generate(array $data) {
        /*$data = [
            [
                'type' => 'image',
                'file' => '',
                'width' => 0,
                'height' => 0,
                'round' => false,
                'x' => 0,
                'y' => 0
            ],
            [
                'type' => 'text',
                'text' => '',
                'width' => 0,
                'height' => 0,
                'size' => '14',
                'color' => '#000000',
                'align' => 'center',
                'valign' => 'center',
                'x' => 0,
                'y' => 0
            ]
        ];*/
        foreach ($data as $vo) {
            if ($vo['type'] == 'text') {
                if($vo['align'] <> 'left' || $vo['align'] <> 'center' || $vo['align'] <> 'right') {
                    $vo['align'] = 'left';
                }
                if($vo['valign'] <> 'top' || $vo['valign'] <> 'center' || $vo['valign'] <> 'bottom') {
                    $vo['valign'] = 'top';
                }
                if ($vo['align'] == 'left') {
                    $x = $vo['x'];
                }
                if ($vo['align'] == 'center') {
                    $x = round($vo['width'] / 2) + $vo['x'];
                }
                if ($vo['align'] == 'right') {
                    $x = $vo['width'] + $vo['x'];
                }
                if ($vo['valign'] == 'top') {
                    $y = $vo['y'];
                }
                if ($vo['valign'] == 'center') {
                    $y = round($vo['height'] / 2) + $vo['y'];
                }
                if ($vo['valign'] == 'bottom') {
                    $y = $vo['height'] + $vo['y'];
                }
                $this->imgObj->text($this->autoWrap($vo['text'], $vo['size'], $vo['width']), $x, $y, function ($font) use ($vo) {
                    $font->file($this->config['font']);
                    $font->size($vo['size']);
                    $font->color($vo['color']);
                    $font->align($vo['align']);
                });
            }
            if ($vo['type'] == 'image') {
                if (is_string($vo['file'])) {
                    $image = $this->getObj()->make($vo['file']);
                }else {
                    $image = $vo['file'];
                }
                if ($vo['round']) {
                    //设置圆角
                    $mask = $this->circleMask($image->width(), $image->height());
                    $image->mask($mask);
                }
                //缩放图标
                $image->resize($vo['width'], $vo['height']);
                $this->imgObj->insert($image, 'top-left', $vo['x'], $vo['y']);
            }
        }
        return $this;
    }

    /**
     * 自动换行
     * @param string $text
     * @param int $size
     * @param int $width
     * @return string
     */
    private function autoWrap(string $text, int $size, int $width) {
        $fontsize = round($size / 96 * 72, 1);
        $str = "";
        for ($i = 0; $i < mb_strlen($text); $i++) {
            $letter[] = mb_substr($text, $i, 1);
        }
        foreach ($letter as $l) {
            $teststr = $str . " " . $l;
            $testbox = imagettfbbox($fontsize, 0, $this->config['font'], $teststr);
            if (($testbox[2] > $width) && ($str !== "")) {
                $str .= "\n";
            }
            $str .= $l;
        }
        return $str;
    }

    /**
     * 生成二维码
     * @param string $text
     * @param int $size
     * @param array $label
     * @param array $logo
     * @return $this
     * @throws \Endroid\QrCode\Exception\InvalidPathException
     */
    public function qrcode(string $text, int $size = 300, array $label = [], array $logo = []) {
        $qrCode = new \Endroid\QrCode\QrCode($text);
        if ($logo) {
            $qrCode->setLogoPath($logo['file']);
            $qrCode->setLogoSize($logo['width'] ? $logo['width'] : 80, $logo['height'] ? $logo['height'] : 80);
        }
        if ($label) {
            $qrCode->setLabel($label['text'], $label['size'] ? $label['size'] : 16, $this->config['font'], \Endroid\QrCode\LabelAlignment::CENTER);
        }
        $this->imgObj = $this->getObj()->make($qrCode->writeString());
        return $this;
    }

    /**
     * 获取图片内容
     * @param string|null $type
     * @param int $quality
     * @return mixed
     */
    public function get(string $type = null, int $quality = 90) {
        return $this->imgObj->response($type, $quality)->getContent();
    }

    /**
     * 保存图片
     * @param string $filename
     * @param int|null $quality
     * @param int|null $type
     * @return bool
     */
    public function save(string $filename, int $quality = null, int $type = null) {
        $this->imgObj->save($filename, $quality, $type);
        return true;
    }

    /**
     * 输出到浏览器
     * @param string|null $type
     * @param int $quality
     */
    public function output(string $type = null, int $quality = 90) {
        header('Content-Type: ' . $this->imgObj->mime());
        echo $this->imgObj->response($type, $quality)->getContent();
    }

    /**
     * 获取图片对象
     * @return \Intervention\Image\Image|object
     */
    public function getImg() {
        return $this->imgObj;
    }

    /**
     * 获取类库对象
     * @return \Intervention\Image\ImageManager|string
     */
    public function getObj() {
        if ($this->object) {
            return $this->object;
        }
        $this->object = new \Intervention\Image\ImageManager(['driver' => $this->config['type']]);
        return $this->object;
    }

}