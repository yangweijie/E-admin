<?php
/**
 * Created by PhpStorm.
 * User: rocky
 * Date: 2020-04-15
 * Time: 20:28
 */

namespace Eadmin\model;


use think\Model;

class SystemMenu extends BaseModel
{
    public function __construct(array $data = [])
    {
        $this->table = config('admin.database.menu_table');
        parent::__construct($data);
    }
}
