<?php

/**
 * GD图片处理驱动
 */

namespace dux\lib\image;

class GdDriver implements ImageInterface {

    protected $imgRes;
    protected $file;

    protected $info = array();

    protected $errorMsg = '';

    /**
     * 构建函数
     * @param string $img 图片路径
     */
    public function __construct($img) {
        if (!is_file($img)) {
            $this->errorMsg = '图片不存在！';
            return false;
        }
        $this->file = $img;
        $imgInfo = getimagesize($img);
        if (empty($imgInfo)) {
            $this->errorMsg = '非法图像资源！';
            return false;
        }
        $this->info = array(
            'width' => $imgInfo[0],
            'height' => $imgInfo[1],
            'type' => image_type_to_extension($imgInfo[2], false),
            'mime' => $imgInfo['mime'],
        );
        $img = file_get_contents($img);
        try {
            $this->imgRes = imagecreatefromstring($img);
        } catch (\Exception $e) {
            $this->errorMsg = $e->getMessage();
            return false;
        }
    }

    /**
     * 裁剪图片
     * @param  integer $w 图片宽度
     * @param  integer $h 图片高度
     * @param  integer $x X坐标
     * @param  integer $y Y坐标
     * @param  integer $width 目标宽度
     * @param  integer $height 目标高度
     * @return object
     */
    public function crop($w, $h, $x = 0, $y = 0, $width = null, $height = null) {
        if (empty($this->imgRes)) {
            $this->errorMsg = '图像处理失败！';
            return false;
        }
        empty($width) && $width = $w;
        empty($height) && $height = $h;
        $img = imagecreatetruecolor($width, $height);
        $color = imagecolorallocate($img, 255, 255, 255);
        imagefill($img, 0, 0, $color);
        imagecopyresampled($img, $this->imgRes, 0, 0, $x, $y, $width, $height, $w, $h);
        imagedestroy($this->imgRes);
        $this->imgRes = $img;
        $this->info['width'] = $width;
        $this->info['height'] = $height;
        return $this;
    }

    /**
     * 缩放图片
     * @param int $width
     * @param int $height
     * @param string $type
     * @return $this
     * @throws \Exception
     */
    public function thumb($width, $height, $type = 'scale') {
        $w = $this->info['width'];
        $h = $this->info['height'];
        switch ($type) {
            //等比缩放
            case 'scale':
            case 1:
                if ($w < $width && $h < $height) return $this;
                $scale = min($width / $w, $height / $h);
                $x = $y = 0;
                $width = $w * $scale;
                $height = $h * $scale;
                break;
            //居中裁剪缩放
            case 'center':
            case 2:
                $scale = max($width / $w, $height / $h);
                $w = $width / $scale;
                $h = $height / $scale;
                $x = ($this->info['width'] - $w) / 2;
                $y = ($this->info['height'] - $h) / 2;
                break;
            //固定尺寸
            case 'fixed':
            case 3:
                $x = $y = 0;
                break;
            default:
                $this->errorMsg = '无此缩图类型！';
                return false;
        }
        $this->crop($w, $h, $x, $y, $width, $height);
        return $this;
    }


    /**
     * 图片水印
     * @param string $source
     * @param int $locate
     * @param int $alpha
     * @return $this
     * @throws \Exception
     */
    public function water($source, $locate = 0, $alpha = 80) {
        if (empty($this->imgRes)) {
            $this->errorMsg = '水印处理失败！';
            return false;
        }
        $info = getimagesize($source);
        if (!$info) {
            $this->errorMsg = '非法图像资源！';
            return false;
        }
        $fun = 'imagecreatefrom' . image_type_to_extension($info[2], false);
        $water = $fun($source);
        imagealphablending($water, true);
        if (!$locate) {
            $locate = rand(1, 9);
        }
        switch ($locate) {
            //左上角水印
            case 1:
                $x = $y = 0;
                break;
            //上居中水印
            case 2:
                $x = ($this->info['width'] - $info[0]) / 2;
                $y = 0;
                break;
            //右上角水印
            case 3:
                $x = $this->info['width'] - $info[0];
                $y = 0;
                break;
            //左居中水印
            case 4:
                $x = 0;
                $y = ($this->info['height'] - $info[1]) / 2;
                break;
            //居中水印
            case 5:
                $x = ($this->info['width'] - $info[0]) / 2;
                $y = ($this->info['height'] - $info[1]) / 2;
                break;
            //右居中水印
            case 6:
                $x = $this->info['width'] - $info[0];
                $y = ($this->info['height'] - $info[1]) / 2;
                break;
            //左下角水印
            case 7:
                $x = 0;
                $y = $this->info['height'] - $info[1];
                break;
            //下居中水印
            case 8:
                $x = ($this->info['width'] - $info[0]) / 2;
                $y = $this->info['height'] - $info[1];
                break;
            //右下角水印
            case 9:
                $x = $this->info['width'] - $info[0];
                $y = $this->info['height'] - $info[1];
                break;
        }
        $src = imagecreatetruecolor($info[0], $info[1]);
        $color = imagecolorallocate($src, 255, 255, 255);
        imagefill($src, 0, 0, $color);
        imagecopy($src, $this->imgRes, 0, 0, $x, $y, $info[0], $info[1]);
        imagecopy($src, $water, 0, 0, 0, 0, $info[0], $info[1]);
        imagecopymerge($this->imgRes, $src, $x, $y, 0, 0, $info[0], $info[1], $alpha);
        imagedestroy($src);
        imagedestroy($water);
        return $this;
    }

    /**
     * 输出图片
     * @param  string $filename 文件名
     * @param  string $type 图片类型
     * @return boolean
     */
    public function output($filename, $type = null) {
        if (empty($this->imgRes)) {
            $this->errorMsg = '图片输出失败！';
            return false;
        }
        if (!$type) {
            $type = $this->info['type'];
        } else {
            $type = strtolower($type);
        }
        if ('jpeg' == $type || 'jpg' == $type) {
            $func = 'imagejpeg';
        } else {
            $func = 'image' . $type;
        }
        if (!function_exists($func)) {
            $this->error = '无法对该图片格式进行处理！';
            return false;
        }
        return $func($this->imgRes, $filename);
    }

    public function getRGB() {
        $file = $this->file;
        $type = $this->info['type'];

        if ('jpeg' == $type || 'jpg' == $type) {
            $func = 'imagecreatefromjpeg';
        } else {
            $func = 'imagecreatefrom' . $type;
        }
        if (!function_exists($func)) {
            $this->error = '无法分析该图片！';
            return false;
        }
        $img = $func($file);

        $w = $this->info['width'];
        $h = $this->info['height'];
        $r = $g = $b = 0;
        for ($y = 0; $y < $h; $y++) {
            for ($x = 0; $x < $w; $x++) {
                $rgb = imagecolorat($img, $x, $y);
                $r += $rgb >> 16;
                $g += $rgb >> 8 & 255;
                $b += $rgb & 255;
            }
        }
        $pxls = $w * $h;

        $r = (round($r / $pxls));
        $g = (round($g / $pxls));
        $b = (round($b / $pxls));
        return $this->rgb2html($r, $g, $b);
    }

    public function rgb2html($r, $g = -1, $b = -1) {
        if (is_array($r) && sizeof($r) == 3)
            list($r, $g, $b) = $r;
        $r = intval($r);
        $g = intval($g);
        $b = intval($b);
        $r = dechex($r < 0 ? 0 : ($r > 255 ? 255 : $r));
        $g = dechex($g < 0 ? 0 : ($g > 255 ? 255 : $g));
        $b = dechex($b < 0 ? 0 : ($b > 255 ? 255 : $b));
        $color = (strlen($r) < 2 ? '0' : '') . $r;
        $color .= (strlen($g) < 2 ? '0' : '') . $g;
        $color .= (strlen($b) < 2 ? '0' : '') . $b;
        return '#' . $color;
    }

    /**
     * 获取错误信息
     */
    public function getError() {
        return $this->errorMsg;
    }

    /**
     * 获取图片信息
     */
    public function getInfo() {
        return $this->info;
    }

    /**
     * 析构函数
     */
    public function __destruct() {
        if (is_resource($this->imgRes)) {
            imagedestroy($this->imgRes);
        }
        unset($this->imgRes);
        unset($this->info);
    }
}