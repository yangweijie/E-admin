<?php

namespace Eadmin\support;


use think\helper\Str;

class Annotation
{
    public static function parse($doc){
        if (preg_match('#^/\*\*(.*)\*/#s', $doc, $comment) === false) {
            return false;
        }
        if (!isset($comment[1])) {
            return false;
        }
        $comment = trim($comment [1]);
        if (preg_match_all('#^\s*\*(.*)#m', $comment, $lines) === false) {
            return false;
        }
        $comments = end($lines);
        $title = array_shift($comments);
        $data['title'] = trim($title);
        foreach ($comments as $comment){
            $param = [];
            $comment = ltrim($comment);
            if (preg_match('/^@param/i', $comment)) {
                $arr = explode(' ',$comment);
                array_shift($arr);
                $varIndex = -1;
                foreach ($arr as $index => $row){
                    if(Str::startsWith($row,'$')){
                        $varIndex = $index;
                    }
                }
                $descArr = array_slice($arr,$varIndex+1);
                $param['desc'] = trim(implode('',$descArr));
                if($varIndex == 1){
                    $param['type'] = trim($arr[0]);
                }
                $param['var'] = trim(substr($arr[$varIndex],1));
                $data['params'][] = $param;
            }elseif (preg_match('/^@response/i', $comment)){
                $arr = explode(' ',$comment);
                array_shift($arr);
                $param['resource'] = array_shift($arr);
                $param['desc'] = trim(implode('',$arr));
                $data['response'][] = $param;
            }
        }
        return $data;
    }
}
