<?php
namespace Waiterphp\Builder\Lib;

class Coder
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