<?php
/**
 * Created by PhpStorm.
 * User: rocky
 * Date: 2021-12-16
 * Time: 22:04
 */

namespace Eadmin\model;

use think\facade\Cache;
use think\Model;
class SystemFieldAuth extends BaseModel
{
    public function __construct(array $data = [])
    {
        $this->table = config('admin.database.field_auth_table');
        parent::__construct($data);
    }
    // 插入后回调
    public static function onAfterInsert(Model $model)
    {
        Cache::tag('eadmin_auth_field')->clear();
    }

    // 更新后回调
    public static function onAfterUpdate(Model $model)
    {
        Cache::tag('eadmin_auth_field')->clear();
    }
}