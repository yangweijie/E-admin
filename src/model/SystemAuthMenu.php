<?php
/**
 * Created by PhpStorm.
 * User: rocky
 * Date: 2020-04-25
 * Time: 21:02
 */

namespace Eadmin\model;




use think\facade\Cache;
use think\Model;

class SystemAuthMenu extends BaseModel
{
    public function __construct(array $data = [])
    {
        $this->table = config('admin.database.auth_menu_table');
        parent::__construct($data);
    }
}
