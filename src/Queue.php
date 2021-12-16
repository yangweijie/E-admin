<?php


namespace Eadmin;

use Carbon\Carbon;
use think\facade\Cache;
use think\facade\Db;
use think\queue\Job;

/**
 * 系统队列
 * Class Queue
 * @package Eadmin
 * @property Job $job
 */
abstract class Queue
{
    protected $job;
    protected $tableName = 'system_queue';
    protected $queueId = 0;
    protected $time;
    protected $queue;
    protected $second = null;
    public function init($job)
    {
        $this->time = microtime(true);
        $this->job = $job;
        $data = json_decode($this->job->getRawBody(), true);
        $this->queueId = $data['data']['system_queue_id'];
        $this->queue = Db::name($this->tableName)->find($this->queueId);
    }

    /**
     * 更新进度百分比
     * @param int $total 总和
     * @param int $count 当前记录总数
     * @param string|null $message 描述
     */
    public function percentage(int $total, int $count, string $message = null)
    {
        $total = $total < 1 ? 1 : $total;
        $progress = sprintf("%.2f", $count / $total * 100);
        $this->progress($message, $progress);
    }

    /**
     * 更新进度
     * @param string|null $message 描述
     * @param null $progress 百分比
     * @param null $status 状态 3完成，4失败
     * @return array|mixed
     * @throws \think\db\exception\DbException
     */
    public function progress(string $message = null, $progress = null, $status = null)
    {
        if (is_numeric($status)) {
            $update['status'] = $status;
            if ($status == 2) {
                $update['exec_time'] = date('Y-m-d H:i:s');
            }
            if ($status == 4 || $status == 3) {

                $update['task_time'] = microtime(true) - $this->time;
            }
            Db::name($this->tableName)->where('id', $this->queueId)->update($update);
        }
        $cacheKey = 'queue_' . $this->queueId . '_progress';
        $data = Cache::get($cacheKey) ?: [];
        if (!isset($data['status'])) {
            $data['progress'] = 0;
        }
        if (!isset($data['status'])) {
            $data['status'] = Db::name($this->tableName)->where('id', $this->queueId)->value('status');
        }
        if (is_numeric($progress)) {
            $second = (microtime(true) - $this->time);
            if($progress > 0){
                $second = $second / $progress;
            }
            $second =  (100-$progress) * $second;
            $second = round($second,0);
            $data['remain_time'] = Carbon::parse($second)->format('H小时i分s秒');
            $data['progress'] = $progress;
        }
        if (is_numeric($status)) {
            $data['status'] = $status;
        }
        if (!is_null($message) || !is_null($progress) || !is_null($status)) {
            $data['history'][] = ['message' => $message, 'progress' => $data['progress'], 'datetime' => date('Y-m-d H:i:s')];
            $data['history'] = array_slice($data['history'],-100);
        }
        Cache::set($cacheKey, $data, 86400);
        return $data;
    }

    /**
     * 重新发布
     * @param int $delay 延迟时间
     */
    public function release($delay = 0)
    {
        $this->job->release($delay);
        $this->progress('任务重新发布', 0, 1);
    }

    //执行失败
    public function error($message)
    {
        $this->progress($message, 100, 4);
    }

    //执行完成
    public function success($message)
    {
        $this->progress($message, 100, 3);
    }
    private function isQueued()
    {
        if ($this->queue['is_queue'] == 1) {
            Db::startTrans();
            try {
                $res = Db::name($this->tableName)
                    ->where('queue', $this->queue['queue'])
                    ->where('is_queue', 1)
                    ->where('id', '<>', $this->queueId)
                    ->where('status', 2)
                    ->lock(true)
                    ->find();
                Db::commit();
            } catch (\Exception $exception) {
                Db::rollback();
            }
            if ($res) {
                return true;
            }
        }
        return false;
    }

    public function fire(Job $job, $data)
    {
        $this->init($job);
        if ($this->queue && $this->queue['status'] < 3) {
            if ($this->isQueued()) {
                $this->progress('任务排队中');
                $this->release(3);
                return false;
            }
            if ($this->queue['status'] == 2) {
                $this->progress('任务进程被中断，重试任务开始', 0, 2);
            } else {
                $this->progress('任务开始', 0, 2);
            }
            $result = false;
            $this->job->delete();
            try {
                $result = $this->handle($data);
            } catch (\Throwable $exception) {
                $this->error('<b style="color: red">任务失败错误信息</b>：' . $exception->getMessage());
                $this->error('<b style="color: red">任务失败追踪错误</b>：' . $exception->getTraceAsString());
            }
            if ($result) {
                $this->success('<b style="color: green">任务完成</b>' . PHP_EOL . PHP_EOL);
                return true;
            }
        }
        $this->error('<b style="color: red">任务失败</b>');
    }
}
