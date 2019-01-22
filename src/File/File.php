<?php
namespace Waiterphp\Core\File;
class File
{
    static public function mv($sourceFile, $targetFile)
    {
        $sourceFile = self::formatPath($sourceFile);
        $targetFile =  self::formatPath($targetFile);
        self::initDir(dirname($targetFile));
        return rename($sourceFile, $targetFile);
    }

    //往文件里面写入数据
    static public function write($file, $content, $type = 'x')
    {
        //检测文件夹
        $dir = dirname($file);
        self::initDir($dir);

        //添加内容
        $fp = fopen($file, $type);
        flock($fp, LOCK_EX);
        fwrite($fp, $content);
        flock($fp, LOCK_UN);
        fclose($fp);
        return true;
    }

    //初始化目录
    static public function initDir($dir, $power = 0777, $isRecursive = true)
    {
        if (is_dir($dir)) {
            return true;
        } else {
            $basedir = dirname($dir);
            self::initDir($basedir);
            mkdir($dir, $power, $isRecursive);
            return true;
        }
    }

    static public function getFiles($dir)
    {
        $files = array();
        $handle = opendir($dir);
        assert_exception($handle == true, 'can not open dir:' . $dir);
        while (($file = readdir($handle)) !== false)//循环读取目录中的文件名并赋值给$file
        {
            if ($file == "." || $file == "..") {//排除当前路径和前一路径
                continue;
            }

            $filePath = realpath($dir . "/" . $file);
            if (is_dir($filePath)) {
                $files = array_merge($files, self::getFiles($filePath));
            } else {
                $files[] = $filePath;
            }
        }

        return $files;
    }

    static function rm($file)
    {
        $status = unlink($file);
        assert_exception($status == false, 'delete fiel error:'.$file);
    }

    public static function relativePath($sourcePath, $targetPath)
    {
        $sourcePath = self::formatPath($sourcePath);
        $targetPath = self::formatPath($targetPath);
        $sourcePath_array = explode(DIRECTORY_SEPARATOR, $sourcePath);
        $targetPath_array = explode(DIRECTORY_SEPARATOR, $targetPath);
        $samePath = array_intersect_assoc($sourcePath_array, $targetPath_array);
        $sameDepth = count($samePath);
        $relativeDepth = count($sourcePath_array) - $sameDepth;
        $relativeTarget = array_slice($targetPath_array, $sameDepth);
        $path_array = array_merge(array_fill(0, $relativeDepth, '..'), $relativeTarget);
        return implode('/', $path_array);
    }

    public static function getContents($file)
    {
        return file_get_contents($file);
    }

    private static function formatPath($path)
    {
        $path = realpath($path);
        $path = str_replace('/', DIRECTORY_SEPARATOR, $path);
        return str_replace('\\', DIRECTORY_SEPARATOR, $path);
    }
}