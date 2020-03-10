<?php
namespace waiterphp\core\Upload;

class Upload
{

    public $name = '';
    public $file = '';
    public $size = '';

    public static function get($fileName, $type = null)
    {
        if (!isset($_FILES[$fileName])) {
            throw new \Exception('not upload file:' . $fileName);
        }
        if ($_FILES[$fileName]['error'] > 0) {
            throw new \Exception('file upload error:' . $fileName);
        }
        if (!empty($type)) {

        }

        $upload = new self();
        $upload->name = $_FILES[$fileName]['name'];
        $upload->file = $_FILES[$fileName]['tmp_name'];
        $upload->size = $_FILES[$fileName]['size'];
        return $upload;
    }

    public function moveTo($targetName)
    {
        \waiterphp\core\File::initDir(dirname($targetName));
        return move_uploaded_file($this->file, $targetName);
    }
}