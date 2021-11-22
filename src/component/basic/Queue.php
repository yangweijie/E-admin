<?php

namespace Eadmin\component\basic;

use Eadmin\component\Component;
use think\facade\Db;

/**
 * 队列执行进度通知
 */
class Queue extends Component
{
    protected $name = 'EadminQueue';
    public function __construct($queueId)
    {
        parent::__construct();
        $title = Db::name('system_queue')->where('id',$queueId)->value('name');
        $this->attr('title',$title);
        $this->attr('queueId',$queueId);
    }

    /**
     * 创建
     * @param $queueId 队列id sysqueue函数返回
     * @return Queue
     */
    public static function create($queueId)
    {
        return new self();
    }
}
