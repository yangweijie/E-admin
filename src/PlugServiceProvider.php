<?php

namespace Eadmin;

use Closure;
use Eadmin\service\PlugService;
use think\App;
use think\facade\Request;
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
     * 获取命名空间
     * @return string
     */
    final public function getNamespace()
    {
        $psr = $this->composerProperty['autoload']['psr-4'];
        $psr = array_flip($psr);
        return end($psr);
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
        if (is_null($value)) {
            return Arr::get($data, $key);
        }
        Arr::set($data, $key, $value);
        $content = var_export($data, true);
        $content = <<<PHP
<?php
return $content;
PHP;
        return file_put_contents($file, $content);
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
        if($this->enabled()){
            //插件菜单
            $this->addMenus();
            //注册路由
            $this->registerRoute();
        }
    }

    /**
     * 注册路由
     */
    final function registerRoute()
    {
        $dir = basename($this->getPath());
        $this->app->route->group($dir, function () {
            $namespace = $this->getNamespace();
            $pathArr = explode('/', Request::pathinfo());
            if ($pathArr[0] == 'api') {
                $namespace .= 'controller\\api\\';
                $method = Request::method();
                $this->app->route->any('<controller>/<function>', $namespace . '<controller>@' . $method . '<function>');
            } else {
                $namespace .= 'controller\\';
                $this->app->route->any('<controller>/<function>', $namespace . '<controller>@<function>');
                $this->app->route->any('<controller>', $namespace . '<controller>@index');
            }
        });
    }

    /**
     * 添加菜单
     * @param array $menus
     */
    final function addMenus(array $menus = [])
    {
        if (count($menus) == 0) {
            $menus = $this->menus();
        }
        $names = array_column($menus, 'name');
        foreach ($menus as $key => &$menu) {
            $menu['id'] = md5(serialize($menu));
            if (isset($menu['pid'])) {
                $index = array_search($menu['pid'], $names);
                if ($index !== false) {
                    $pidMenu = $menus[$index];
                    unset($pidMenu['id']);
                    unset($pidMenu['pid']);
                    $menu['pid'] = md5(serialize($pidMenu));
                }
            } else {
                $menu['pid'] = 0;
            }
            if (!$menu['status']) {
                unset($menus[$key]);
            }
        }
        Admin::menu()->add($menus);
    }
}
