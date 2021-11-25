<?php

namespace Eadmin\model;

class SystemFile extends BaseModel
{
    public function __construct(array $data = [])
    {
        $this->table = config('admin.database.file_table');
        parent::__construct($data);
    }
}
