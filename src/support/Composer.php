<?php

namespace Eadmin\support;

use Composer\Autoload\ClassLoader;

class Composer
{
    protected static $loader;
    /**
     * 获取 composer 类加载器.
     *
     * @return ClassLoader
     */
    public static function loader()
    {
        if (!static::$loader) {
            static::$loader = include app()->getRootPath() . '/vendor/autoload.php';
        }
        return static::$loader;
    }
    /**
     * @param $file
     * @return mixed
     */
    public static function parse($file){
        return json_decode(file_get_contents($file), true);
    }
}
