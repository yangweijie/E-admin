<?php
/**
 * Created by PhpStorm.
 * User: rocky
 * Date: 2020-04-25
 * Time: 16:40
 */

namespace Eadmin\model;


class SystemAuth extends BaseModel
{
    protected $dataAuth = ['admin_id'];
    public function __construct(array $data = [])
    {
        $this->table = config('admin.database.auth_table');
        parent::__construct($data);
    }
}
