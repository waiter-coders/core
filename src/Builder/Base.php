<?php
namespace Waiterphp\Builder;

use Waiterphp\Core\File\File as File;
use Waiterphp\Core\Console\Shell as Shell;

use Waiterphp\Builder\Dispatcher as Dispatcher;
use Waiterphp\Builder\Lib\Compiler as Compiler;

abstract class Base
{
    abstract public function build($params = []);

    protected $basePath = '';

    public function __construct($basePath)
    {
        assert_exception(is_dir($basePath), 'base path is not exist:' . $basePath);
        $basePath = realpath($basePath);
        $this->basePath = $basePath;
    }

    protected function buildFile($template, $file, $params = [], $isForce = false)
    {
        // 检查文件是否存在，存在则询问用户是否覆盖
        if ($isForce == false && is_file($file)) {
            $continue = Shell::askUser('已有以下文件，是否覆盖？' . $file);
            if ($continue == false) { // 不覆盖则直接跳过
                return false;
            } else {
                Shell::output('覆盖后，可通过git对比文件更改！');
            }
        }

        // 编辑模板，写入文件
        $template = file_get_contents($template);
        $content = Compiler::compile($template, $params);
        File::write($file, $content, 'w');
    }

    protected function buildDir($templateDir, $targetDir, $params = [], $isForce = false)
    {
        $templates = File::getFiles($templateDir);
        $dirLength = strlen(rtrim($templateDir, DIRECTORY_SEPARATOR));
        foreach ($templates as $template) {
            $relativePath = trim(substr($template, $dirLength), DIRECTORY_SEPARATOR);
            $targetFile = $targetDir . DIRECTORY_SEPARATOR . $relativePath;
            $this->buildFile($template, $targetFile, $params, $isForce);
        }
    }

    protected function dispatcher($package, $params = [], $basePath = '')
    {
        $basePath = empty($basePath) ? $this->basePath : $basePath;
        return (new Dispatcher($basePath))->build($package, $params);
    }
}