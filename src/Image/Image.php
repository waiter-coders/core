<?php
namespace Waiterphp\Core\Image;

class Image
{
    private $imageName;
    private $info = array(
        'width'=>0,
        'height'=>0,
        'type'=>'',
        'size'=>0,
    );
    private static $imageType = array(
        1=>'gif',
        2=>'jpg',
        3=>'png',
    );

    private $imageSource = '';
    private $sourceX = 0;
    private $sourceY = 0;
    private $sourceWidth = 0;
    private $sourceHeight = 0;
    private $targetX = 0;
    private $targetY = 0;
    private $targetWidth = 0;
    private $targetHeight = 0;
    private $copyWidth = 0;
    private $copyHeight = 0;

    public static function get($image)
    {
        return new self($image);
    }

    private function __construct($image)
    {
        if (!is_file($image)) {
            throw new \Exception('not set file:'.$image);
        }
        $this->imageName = $image;
        $this->info = $this->extractInfo($image);
        $this->imageSource = $this->imageSource($image, $this->info['type']);
        $maxWidth = $this->width();
        if ($maxWidth > 800) { // 防止太大图片
            $maxWidth = 800;
        }
        $this->scale($maxWidth);
    }

    private function imageSource($image, $type)
    {
        if ($type == 'jpg') {
            return imagecreatefromjpeg($image);
        }
        if ($type == 'gif') {
            return imagecreatefromgif($image);
        }
        if ($type == 'png') {
            return imagecreatefrompng($image);
        }
        throw new \Exception('image type not set:' . $type);
}

    private function extractInfo($image)
    {
        $info = array();
        list($info['width'], $info['height'], $info['type']) = getimagesize($image);
        $info['type'] = isset(self::$imageType) ? self::$imageType[$info['type']] : '';
        $info['size'] = ceil(filesize($image) / 1000) . "k";
        return $info;
    }

    public function width()
    {
        return $this->info['width'];
    }

    public function height()
    {
        return $this->info['height'];
    }

    public function type()
    {
        return $this->info['type'];
    }

    public function size()
    {
        return $this->info['size'];
    }

    public function scale($width = null, $height = null, $cutSource = true)
    {
        if (empty($width) && empty($height)) {
            throw new \Exception('width and height is empty');
        }
        $this->targetWidth = empty($width) ? floor($this->width() * $height / $this->height())  : $width;
        $this->targetHeight = empty($height) ? floor($this->height() * $width / $this->width()) : $height;
        if ($cutSource == true) {
            $this->sourceWidth = floor($this->targetWidth * $this->height() / $this->targetHeight);
            $this->sourceHeight = $this->height();
            if ($this->sourceWidth > $this->width()) {
                $this->sourceWidth = $this->width();
                $this->sourceHeight = floor($this->targetHeight * $this->width() / $this->targetWidth);
            }
            $this->sourceX = floor(($this->width() - $this->sourceWidth) / 2);
            $this->sourceY = floor(($this->height() - $this->sourceHeight) / 2);
            $this->copyWidth = $this->targetWidth;
            $this->copyHeight = $this->targetHeight;
        } else {
            $this->sourceWidth = $this->width();
            $this->sourceHeight = $this->height();
            $this->copyWidth =  floor($this->width() * $this->targetHeight / $this->height());
            $this->copyHeight = $this->targetHeight;
            if ($this->copyWidth > $this->targetWidth) {
                $this->copyWidth = $this->targetWidth;
                $this->copyHeight = floor($this->height() * $this->targetWidth / $this->width());
            }
            $this->targetX = floor(($this->targetWidth - $this->copyWidth) / 2);
            $this->targetY = floor(($this->targetHeight - $this->copyHeight) / 2);
        }
        return $this;
    }

    public function cut($posX, $posY, $width, $height)
    {

        return $this;
    }

    public function water()
    {
        return $this;
    }

    public function text()
    {
        return $this;
    }

    public function toWeb()
    {

    }

    public function save($path, $isForce = false)
    {
        // 校验文件权限
        if (is_dir($path)) {
            $path .= basename($this->imageName);
        }
        if (is_file($path) && $isForce == false) {
            throw new \Exception('file already exist');
        }

        // 处理图像
        $targetImage = imagecreatetruecolor($this->targetWidth, $this->targetHeight);
        if ($this->type() == 'png') {
            $backgroundColor = imagecolorallocatealpha($targetImage, 0, 0, 0, 127);
        } else {
            $backgroundColor = imagecolorallocate($targetImage, 255, 255, 255);
        }
        imagefill($targetImage, 0, 0, $backgroundColor);
        imagecopyresampled($targetImage, $this->imageSource, // 图片资源
            $this->targetX, $this->targetY, $this->sourceX, $this->sourceY, // 源图片坐标和目标图片坐标
            $this->copyWidth, $this->copyHeight,  // 目标图片尺寸
            $this->sourceWidth, $this->sourceHeight); // 源图片尺寸

        // 清空变量
        $this->sourceX = 0;
        $this->sourceY = 0;
        $this->sourceWidth = 0;
        $this->sourceHeight = 0;
        $this->targetX = 0;
        $this->targetY = 0;
        $this->targetWidth = 0;
        $this->targetHeight = 0;

        // 保存图片
        if (is_file($path) && $isForce) {
            \Waiterphp\Core\File\File::remove($path);
        }
        \Waiterphp\Core\File\File::write($path, '');
        if ($this->type() == 'png') {
            imagesavealpha($targetImage, true);
            imagepng($targetImage, $path);
        } else {
            imagejpeg($targetImage, $path);
        }
        imagedestroy($targetImage);
    }

    public function __destruct()
    {
        imagedestroy($this->imageSource);
    }
}
