<?php

namespace Eadmin;

use Eadmin\service\PlugService;
use think\App;
use think\helper\Arr;
use \think\Service;

class PlugServiceProvider extends Service
{
    protected $composerProperty;

    /**
     * 判断扩展是否启用.
     *
     * @return bool
     */
    final public function enabled()
    {
        return PlugService::instance()->getInfo($this->getName(), 'status') ? true : false;
    }

    /**
     * 判断扩展是否禁用.
     *
     * @return bool
     */
    final public function disabled()
    {
        return !$this->enabled();
    }

    /**
     * 获取扩展路径
     * @return mixed
     */
    final public function getPath()
    {

        return $this->composerProperty['plug_path'];
    }

    /**
     * 获取扩展名称
     * @return mixed
     */
    final public function getName()
    {
        return $this->composerProperty['name'];
    }

    final public function withComposerProperty($composerProperty)
    {
        $this->composerProperty = $composerProperty;
    }

    /**
     * 获取或保存配置.
     * @param string $key
     * @param string $value
     * @return array|\ArrayAccess|false|int|mixed
     */
    final public static function config($key, $value = null)
    {
        $file = static::instance()->getPath() . '/src/config.php';
        $data = include $file;
        if(is_null($value)){
            return Arr::get($data, $key);
        }
        Arr::set($data,$key,$value);
        $content = var_export($data, true);
        $content = <<<PHP
<?php
return $content;
PHP;
        return file_put_contents($file,$content);
    }
    /**
     * 获取自身实例.
     *
     * @return $this
     */
    public static function instance()
    {
        return app(static::class);
    }
    public function boot()
    {
        $this->addMenus();
    }
    /**
     * 添加菜单
     * @param array $menus
     */
    final function addMenus(array $menus = []){
        if(count($menus) == 0){
            $menus = $this->menus();
        }
        $names = array_column($menus,'name');
        foreach ($menus as $key=>&$menu){
            $menu['id'] = md5(serialize($menu));
            if(isset($menu['pid'])){
                $index = array_search($menu['pid'],$names);
                if($index !== false){
                    $pidMenu = $menus[$index];
                    unset($pidMenu['id']);
                    unset($pidMenu['pid']);
                    $menu['pid'] = md5(serialize($pidMenu));
                }
            }else{
                $menu['pid'] = 0;
            }
            if(!$menu['status']){
                unset($menus[$key]);
            }
        }
        if($this->enabled()){
            Admin::menu()->add($menus);
        }
    }
}
