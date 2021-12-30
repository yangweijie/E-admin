<?php

namespace Eadmin\plugin;


use Eadmin\Admin;
use think\App;
use think\exception\ClassNotFoundException;
use think\helper\Str;
use think\route\dispatch\Controller;

class PlugDispatch extends Controller
{
    public function init(App $app)
    {
        $this->app = $app;

        // 执行路由后置操作
        $this->doRouteAfter();
        $result = $this->dispatch;

        $result = explode('/', $this->request->pathinfo());

        // 获取控制器名
        $controller = strip_tags($result[2] ?: $this->rule->config('default_controller'));
        if (strpos($controller, '.')) {
            $pos = strrpos($controller, '.');
            $this->controller = substr($controller, 0, $pos) . '.' . Str::studly(substr($controller, $pos + 1));
        } else {
            $this->controller = Str::studly($controller);
        }
        $this->controller = "plugin\\{$result[1]}\\controller\\api\\" . $this->controller;
        // 获取操作名
        $this->actionName = strtolower($this->request->method());
        if(isset($result[3])){
            $this->actionName .=Str::studly($result[3]);
        }
        $this->request->setAction($this->actionName);
        $this->dispatch = [$this->controller, $this->actionName];
    }

    /**
     * 实例化访问控制器
     * @access public
     * @param string $name 资源地址
     * @return object
     * @throws ClassNotFoundException
     */
    public function controller($name)
    {

        $suffix = $this->rule->config('controller_suffix') ? 'Controller' : '';

        $controllerLayer = $this->rule->config('controller_layer') ?: 'controller';
        $emptyController = $this->rule->config('empty_controller') ?: 'Error';

        $class = $name;

        if (class_exists($class)) {
            return $this->app->make($class, [], true);
        } elseif ($emptyController && class_exists($emptyClass = $this->app->parseClass($controllerLayer, $emptyController . $suffix))) {
            return $this->app->make($emptyClass, [], true);
        }

        throw new ClassNotFoundException('class not exists:' . $class, $class);
    }
}
