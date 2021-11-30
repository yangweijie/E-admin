<?php

namespace Eadmin\model;


class SystemAuthData extends BaseModel
{
    public function __construct(array $data = [])
    {
        $this->table = config('admin.database.auth_data_table');
        parent::__construct($data);
    }
}
