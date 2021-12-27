<?php
namespace Eadmin\support;
use think\facade\Lang;
use think\helper\Arr;

/**
 * 翻译
 */
class Translator
{
    /**
     * 获取语言变量值
     * @param string $name 语言变量名
     * @param mixed $default 默认值
     * @param array $vars 动态变量值
     * @param string $lang 语言
     * @return mixed
     */
    public function trans(string $name, $default = null, array $vars = [], string $lang = ''){
        $name = strtolower($name);
        $lang = Lang::getLangSet();
        if(strpos($name,'.')){
            $arr = explode('.',$name);
            $filename = array_shift($arr);
            $value = Lang::get(null,$vars,$filename.'-'.$lang);
            if(empty($value)){
                $value =  Lang::get(null,$vars,$lang);
            }else{
                $name = implode('.',$arr);
            }
        }
        if(is_null($default)){
            $default = $name;
        }
        return Arr::get($value,$name,$default);
    }
}
