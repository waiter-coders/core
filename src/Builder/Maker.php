<?php
namespace Waiterphp\Core\Builder;

use Waiterphp\Core\File\File as File;
use Waiterphp\Core\Console\Shell as Shell;

class Maker
{
    private $template = '';
    private $params = [];
    private $content = null;

    public function params($params)
    {
        $this->params = $params;
        return $this;
    }

    public function template($template, $isFile = true)
    {
        if ($isFile) {
            $template = file_get_contents($template);
        }
        $this->template = $template;
        return $this;
    }

    public function buildToFile($file)
    {
        // 检查文件是否存在，存在则询问用户是否覆盖
        if (is_file($file)) {
            $continue = Shell::askUser('已经有该为文件，是否覆盖？' . $file);
            if ($continue == false) { // 不覆盖则直接跳出
                return false;
            }
        }

        // 编辑模板，写入文件
        $this->compile();
        File::write($file, $this->content);
    }

    public function getContent()
    {
        $this->compile();
        return $this->content;
    }

    private function compile()
    {
        // 检查是否已经编译过内容
        if ($this->content !== null) {
            return true;
        } 
        
        // 检查编译模板内容是否存在
        assert_exception(!empty($this->template), 'template empty');
        
        // 编译内容
        $this->content = Compiler::compile($this->template, $this->params);
        return $this;
    }
}

class Compiler
{
    public static function compile($content, $params = [], $paramsPreffix = '')
    {
        if (empty($params)) {
            return $content;
        }

        // 切片
        $mode = PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE;
        $content = preg_replace('/[ |\t]{1,}(\/\*[^\n]+\*\/)\s{1,}/U', '$1', $content); // 去除模板语法后面的空行
        $piece = preg_split('/(\/\*[^\n]+\*\/)/U', $content, -1, $mode);

        // 树状化切片
        $tree = self::covertToTree($piece);

        // 解析树
        $content = self::analyze($tree, $params);

        return $content;
    }

    private static function covertToTree($piece, &$selectIndex = 0)
    {
        // 解析node层信息
        $tree = [];
        $total = count($piece);
        while ($selectIndex < $total) {
            $fragment = $piece[$selectIndex];
            $selectIndex++;
            // foreach解析
            if (preg_match('/\/\*[ ]*foreach[ ]*\(\$(\w+)[ ]+as[ ]+\$(\w+)\=\>\$(\w+)\)[ ]*\{[ ]*\*\//i', $fragment, $matches)) {
                array_shift($matches);
                $tree[] = [
                    'type'=>'foreach',
                    'params'=>$matches,
                    'template'=>self::covertToTree($piece, $selectIndex),
                ];
            }
            // if解析
            else if(preg_match('/\/\*[ ]*if[ ]*([^\{]+)[ ]*\{[ ]*\*\/[\r|\n]*/i', $fragment, $matches)) {
                $tree[] = [
                    'type'=>'if',
                    'params'=>trim($matches[1]),
                    'template'=>self::covertToTree($piece, $selectIndex),
                ];
            }
            // 遇到结束符号直接返回
            else if(preg_match('/\/\*[ ]*\}[ ]*\*\//i', $fragment, $matches)) {
                return $tree;
            }
            //内容片断解析
            else {
                $tree[] = [
                    'type'=>'template',
                    'template'=>$fragment,
                ];
            }
        }
        return $tree;
    }

    private static function analyze($tree, $params, $paramsPreffix = '')
    {
        $content = '';
        foreach ($tree as $node) {
            // 解析模板
            $template = $node['template'];
            if (is_array($template)) {
                $template = self::analyze($template, $params, $paramsPreffix);
            }
            // 根据类型做相应运算
            switch($node['type']) {
                case 'foreach':
                    list($array, $itemKey, $itemValue) = $node['params'];
                    assert_exception(isset($params[$array]), 'not has foreach key : ' . $array);
                    $itemValue = self::wrapLitter($itemValue, $paramsPreffix);
                    foreach ($params[$array] as $key=>$value) {
                        $itemContent = self::replace([$itemKey=>$key], $template, $paramsPreffix);
                        $itemContent = self::replace($value, $itemContent, $itemValue);
                        $content .= $itemContent;
                    }
                    break;
                case 'if':
                    $ifContent = self::replace($params, $template, $paramsPreffix);
                    eval('if (' . $node['params'] . ') {$content .= $ifContent;' . '}');
                    break;
                default:
                    $content .= self::replace($params, $template, $paramsPreffix);
            }
        }
        return $content;
    }

    private static function replace($params, $template, $paramsPreffix = '')
    {
        foreach ($params as $key=>$value) {
            if (!is_array($value)) {
                $replaceKey = self::wrapLitter($key, $paramsPreffix);
                $template = str_replace($replaceKey, $value, $template);
            }
        }
        return $template;
    }

    private static function wrapLitter($words, $paramsPreffix = '')
    {
        $words = '_' . trim($words, '_') . '_';
        $paramsPreffix = trim($paramsPreffix, '_');
        return empty($paramsPreffix) ? $words : '_' . $paramsPreffix . $words;

    }
}