<?php
/**
 * Created by PhpStorm.
 * User: rocky
 * Date: 2020-04-25
 * Time: 17:17
 */

namespace Eadmin\model;

use Eadmin\Admin;

class AdminModel extends \app\model\BaseModel
{
    public function __construct(array $data = [])
    {
        $this->table = config('admin.database.user_table');
        parent::__construct($data);
    }


    // 角色组
    public function roles()
    {
        $authModel = config('admin.database.auth_model');
        $userAuthModel = config('admin.database.user_auth_model');
        return $this->belongsToMany($authModel, $userAuthModel, 'auth_id', 'user_id');
    }

    // 密码 - 修改器
    protected function setPasswordAttr($val)
    {
        return password_hash($val, PASSWORD_DEFAULT);
    }

    // 获取权限
    public function permissions()
    {
        $userAuthModel = config('admin.database.user_auth_model');
        $roleIds = $userAuthModel::where('user_id', $this->id)->column('auth_id');
        $authNodeModel = config('admin.database.auth_node_model');
        return $authNodeModel::whereIn('auth_id', $roleIds)->select()->toArray();
    }

    // 获取菜单
    public function menus()
    {
        $userAuthModel = config('admin.database.user_auth_model');
        $roleIds = $userAuthModel::where('user_id', $this->id)->column('auth_id');
        $authMenuModel = config('admin.database.auth_menu_model');
        $menuIds = $authMenuModel::whereIn('auth_id', $roleIds)->column('menu_id');
        return Admin::menu()->menus($menuIds);
    }
}
