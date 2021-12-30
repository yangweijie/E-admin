<?php

namespace Eadmin\support;
use Eadmin\service\ApiTokenService;
use Eadmin\service\TokenService;
use think\Facade;
use think\Model;

/**
 * @see TokenService
 * @method TokenService init($type) static 初始化
 * @method array encode($data) static 加密返回token
 * @method string refresh() static 刷新token
 * @method array decode($token) static 解密token
 * @method bool auth() static 验证token
 * @method bool getVar($name) static 获取Token保存数组key下的值
 * @method int id() static 获取用户id
 * @method bool set($token) static 设置token
 * @method bool get() static 获取token
 * @method bool logout($token) static 退出token
 * @method bool clear() static 清除token
 * @method Model user($lock=false) static 获取用户模型
 */
class Token extends Facade
{
    protected static function getFacadeClass()
    {
        return ApiTokenService::class;
    }
    public static function __callStatic($method, $params)
    {
        return call_user_func_array([static::createFacade('',['api']), $method], $params);
    }
}
