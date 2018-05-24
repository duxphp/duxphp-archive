<?php

/**
 * Imagick图片处理驱动
 */

namespace dux\lib\image;

class ImagickDriver implements ImageInterface {

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
        try {
            $this->imgRes = new \Imagick($img);
            if (!$this->imgRes) {
                $this->errorMsg = '非法图像资源！';
                return false;
            }
            $size = $this->imgRes->getImagePage();
            $this->info = array(
                'width' => $size['width'],
                'height' => $size['height'],
                'type' => strtolower($this->imgRes->getImageFormat()),
            );
            $this->file = $img;

        } catch (\Exception $e) {
            $this->errorMsg = $e->getMessage();
            return false;
        }
    }

    /**
     * 裁剪图片
     * @param $w
     * @param $h
     * @param int $x
     * @param int $y
     * @param null $width
     * @param null $height
     * @return $this|bool
     */
    public function crop($w, $h, $x = 0, $y = 0, $width = null, $height = null) {
        if (!$this->imgRes) {
            $this->errorMsg = '图像裁剪失败！';
            return false;
        }

        empty($width)  && $width  = $w;
        empty($height) && $height = $h;

        if ($this->info['type'] == 'gif') {
            $img = $this->imgRes->coalesceImages();
            $this->imgRes->destroy();
            do {
                $this->_crop($w, $h, $x, $y, $width, $height, $img);
            } while ($img->nextImage());

            $this->imgRes = $img->deconstructImages();
            $img->destroy();
        } else {
            $this->_crop($w, $h, $x, $y, $width, $height);
        }

        return $this;
    }

    private function _crop($w, $h, $x, $y, $width, $height, $img = null){
        is_null($img) && $img = $this->imgRes;

        if($x != 0 || $y != 0 || $w != $this->info['width'] || $h != $this->info['height']){
            $img->cropImage($w, $h, $x, $y);
            $img->setImagePage($w, $h, 0, 0);
        }

        if($w != $width || $h != $height){
            $img->scaleImage($width, $height);
        }

        $this->info['width']  = $width;
        $this->info['height'] = $height;

    }

    /**
     * 缩放图片
     * @param int $width
     * @param int $height
     * @param string $type
     * @return $this|bool
     */
    public function thumb($width, $height, $type = 'scale') {
        $w = $this->info['width'];
        $h = $this->info['height'];
        $x = $y = 0;
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
     */
    public function water($source, $locate = 0, $alpha = 80) {
        if (empty($this->imgRes)) {
            $this->errorMsg = '水印处理失败！';
            return false;
        }
        if (!is_file($source)) {
            $this->errorMsg = '水印图像不存在！';
            return false;
        }

        $water = new \Imagick(realpath($source));
        $info  = array($water->getImageWidth(), $water->getImageHeight());

        $x = $y = 0;
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

        //创建绘图资源
        $draw = new \ImagickDraw();
        $draw->composite($water->getImageCompose(), $x, $y, $info[0], $info[1], $water);

        if('gif' == $this->info['type']){
            $img = $this->imgRes->coalesceImages();
            $this->imgRes->destroy();
            do{
                $img->drawImage($draw);
            } while ($img->nextImage());
            $this->imgRes = $img->deconstructImages();
            $img->destroy();
        } else {
            $this->imgRes->drawImage($draw);
        }
        $draw->destroy();
        $water->destroy();

        return $this;
    }

    /**
     * GIF裁剪
     * @param $width
     * @param $height
     * @param bool $isCrop
     * @param int $w
     * @param int $h
     * @param int $x
     * @param int $y
     */
    private function resizeGif($width, $height, $isCrop = false, $w = 0, $h = 0, $x = 0, $y = 0) {
        $dest = new \Imagick();
        $color_transparent = new \ImagickPixel("transparent");
        foreach ($this->imgRes as $img) {
            $page = $img->getImagePage();
            $tmp = new \Imagick();
            $tmp->newImage($page['width'], $page['height'], $color_transparent, 'gif');
            $tmp->compositeImage($img, \Imagick::COMPOSITE_OVER, $page['x'], $page['y']);

            $tmp->thumbnailImage($width, $height, true);
            if ($isCrop) {
                $tmp->cropImage($w, $h, $x, $y);
            }
            $dest->addImage($tmp);
            $dest->setImagePage($tmp->getImageWidth(), $tmp->getImageHeight(), 0, 0);
            $dest->setImageDelay($img->getImageDelay());
            $dest->setImageDispose($img->getImageDispose());
        }
        $this->image = $dest;
    }

    /**
     * 输出图片
     * @param string $filename
     * @param null $type
     * @return bool
     */
    public function output($filename, $type = null) {
        if (empty($this->imgRes)) {
            $this->errorMsg = '图片输出失败！';
            return false;
        }
        $this->imgRes->stripImage();
        switch ($this->info['type']) {
            case 'gif':
                $this->imgRes->writeImages($filename, true);
                break;
            case 'jpg':
            case 'jpeg':
                $this->imgRes->setImageCompressionQuality(100);
                $this->imgRes->writeImage($filename);
                break;
            case 'png':
                $flag = $this->imgRes->getImageAlphaChannel();
                // 如果png背景不透明则压缩
                if (\imagick::ALPHACHANNEL_UNDEFINED == $flag or \imagick::ALPHACHANNEL_DEACTIVATE == $flag) {
                    $this->imgRes->setImageType(\imagick::IMGTYPE_PALETTE);
                    $this->imgRes->writeImage($filename);
                } else {
                    $this->imgRes->writeImage($filename);
                }
                unset($flag);
                break;
            default:
                $this->imgRes->writeImage($filename);
                break;
        }
        $this->imgRes->destroy();
        return true;
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
        if ($this->imgRes !== null) {
            $this->imgRes->destroy();
        }
    }
}