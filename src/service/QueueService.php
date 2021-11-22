<?php


namespace Eadmin\service;


use Eadmin\model\SystemQueue;
use Eadmin\Queue;
use think\facade\Db;

class QueueService extends Queue
{
    public function __construct($id = 0)
    {
        $this->queueId = $id;
    }

    public function retry()
    {
        $queue = SystemQueue::find($this->queueId);
        $queue->save(['status' => 1]);
        $data = $queue['queue_data'];
        $this->progress('任务重试');
        $data['system_queue_id'] = $this->queueId;
        queue($queue['queue'], $data);
    }

    /**
     * 添加队列任务
     * @param string $title 标题
     * @param string $job 任务
     * @param array $data 数据
     * @param int $delay 延迟时间
     * @param bool $queue 多进程下默认并发执行,true排队执行
     * @return int|string
     */
    public function queue($title, $job, array $data, $delay = 0, $queue)
    {
        $status = 1;
        if ($queue) {
            $status = Db::name('system_queue')
                ->where('queue', $job)
                ->where('is_queue', 1)
                ->where('status', '<', 3)->find() ? 0 : 1;
        }
        $id = Db::name('system_queue')->insertGetId([
            'name' => $title,
            'queue' => $job,
            'status' => $status,
            'is_queue' => (int)$queue,
            'queue_data' => json_encode($data),
            'plan_time' => date('Y-m-d H:i:s', time() + $delay),
        ]);
        $data['system_queue_id'] = $id;
        queue($job, $data, $delay);
        return $id;
    }
}
