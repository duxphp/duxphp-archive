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
     * @var string
     */
    protected $imgObj;

    /**
     * 参数配置
     * @var array
     */
    protected $config = [
        'type' => 'gd',
        'font' => ''
    ];

    /**
     * 构建函数
     * @param string $img 图片路径
     * @param string $driver 图片驱动
     */
    public function __construct($img, $config = []) {
        $this->config = $config;
        $this->imgObj = $this->getObj()->make($img);
    }

    /**
     * 图片缩放
     * @param $width
     * @param $height
     * @param string $type
     */
    public function thumb($width, $height, $type = 'scale') {
        switch ($type) {
            // 居中裁剪缩放
            case 'center':
                $this->imgObj->fit($width, $height, function () {
                    $constraint->upsize();
                }, 'center');
                break;
            // 固定尺寸
            case 'fixed':
                $this->imgObj->resize(300, 200, function () {
                    $constraint->upsize();
                });
                break;
            // 等比例缩放
            case 'scale':
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
            default:
        }
    }

    /**
     * 图片裁剪
     * @param $width
     * @param $height
     * @param int $x
     * @param int $y
     * @return $this
     */
    public function crop($width, $height, $x = 0, $y = 0) {
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
        $bigWidth = $width * 2;
        $bigHeight = $height * 2;
        $circle = $this->getObj()->canvas($bigWidth, $bigHeight, '#000000');
        $circle = $circle->circle($bigWidth - 1, $bigWidth / 2, $bigHeight / 2, function ($draw) {
            $draw->background('#ffffff');
        });
        $mask = $circle->resize($width, $height);
        $this->imgObj->mask($mask);
        return $this;
    }

    public function water($source, $locate = 0, $alpha = 80) {
        $position = 'center';
        switch ($locate) {
            //左上角水印
            case 1:
                $position = 'top-left';
                break;
            //上居中水印
            case 2:
                $position = 'top';
                break;
            //右上角水印
            case 3:
                $position = 'top-right';
                break;
            //左居中水印
            case 4:
                $position = 'left';
                break;
            //居中水印
            default:
            case 5:
                $position = 'center';
                break;
            //右居中水印
            case 6:
                $position = 'right';
                break;
            //左下角水印
            case 7:
                $position = 'bottom-left';
                break;
            //下居中水印
            case 8:
                $position = 'bottom';
                break;
            //右下角水印
            case 9:
                $position = 'bottom-right';
                break;
        }
        $watermark = Image::make($source)->opacity($alpha);
        $this->imgObj->insert($watermark, $position, 10, 10);
        return $this;
    }

    public function text($text, $size, $locate, $x = 0, $y = 0) {
        switch ($locate) {
            //左上角水印
            case 1:
                $align = 'left';
                $valign = 'top';
                break;
            //上居中水印
            case 2:
                $align = 'center';
                $valign = 'top';
                break;
            //右上角水印
            case 3:
                $align = 'right';
                $valign = 'top';
                break;
            //左居中水印
            case 4:
                $align = 'left';
                $valign = 'center';
                break;
            //居中水印
            default:
            case 5:
                $align = 'center';
                $valign = 'center';
                break;
            //右居中水印
            case 6:
                $align = 'right';
                $valign = 'center';
                break;
            //左下角水印
            case 7:
                $align = 'left';
                $valign = 'bottom';
                break;
            //下居中水印
            case 8:
                $align = 'bottom';
                $valign = 'center';
                break;
            //右下角水印
            case 9:
                $align = 'right';
                $valign = 'bottom';
                break;
        }

        $this->imgObj->text($text, $x, $y, function ($font) {
            $font->file($this->config['font']);
            $font->size(50);
            $font->color('#000000');
            $font->align($align);
            $font->valign($valign);
        });
    }

    public function save($filename, $quality = null, $type = null) {
        $this->imgObj->save($filename, $quality, $type);
        return true;
    }

    public function output($type = null, $quality = 90) {
        echo $this->imgObj->response($type, $quality);
    }

    public function generate($data = []) {
        $data = [
            [
                'type' => 'image',
                'width' => 0,
                'height' => 0,
                'round' => false,
                'file' => '',
                'x' => 0,
                'y' => 0
            ],
            [
                'type' => 'text',
                'width' => 0,
                'size' => '14',
                'color' => '#000000',
                'text' => '',
                'align' => 'center',
                'x' => 0,
                'y' => 0
            ]
        ];

        foreach ($data as $vo) {
            if($vo['type'] == 'text') {
                $this->text($this->aut, );
            }
        }



    }

    private function autoWrap($str, $size, $width, $fontangle = 0) {
        $txt = "";
        $lineWidth = 0;
        preg_match_all("/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|[\xe0-\xef][\x80-\xbf]{2}|[\xf0-\xff][\x80-\xbf]{3}/", $str, $temp);
        foreach ($temp[0] as $v) {
            $box = @imagettfbbox($size, $fontangle, $this->config['font'], $v);
            $w = max($box[2], $box[4]) - min($box[0], $box[6]);
            $lineWidth += intval($w);
            if (($lineWidth > $width) && ($v !== "")) {
                $txt .= PHP_EOL;
                $lineWidth = 0;
            }
            $txt .= $v;
        }
        return $txt;
    }

    public function getImg() {
        return $this->imgObj;
    }

    public function getObj() {
        if ($this->object) {
            return $this->object;
        }
        $this->object = new \Intervention\Image\ImageManager(['driver' => $this->config['type']]);
        return $this->object;
    }

}