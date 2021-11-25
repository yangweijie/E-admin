<?php


namespace Eadmin\model;


class SystemNotice extends BaseModel
{
    public function __construct(array $data = [])
    {
        $this->table = config('admin.database.notice_table');
        parent::__construct($data);
    }
}
