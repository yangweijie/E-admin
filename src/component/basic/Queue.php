<?php

namespace Eadmin\component\basic;

use Eadmin\component\Component;
use think\app\Url;
use think\facade\Db;

/**
 * 队列执行进度通知
 * @method $this queueId($value)
 */
class Queue extends Component
{
    protected $name = 'EadminQueue';
    public function __construct($content)
    {
        parent::__construct();
        $this->content($content);
    }
    /**
     * 执行队列
     * @param string $title 标题
     * @param string $url 请求路径
     * @return $this
     */
    public function exec($title, $url){
        if($url instanceof Url){
            $url = $url->build();
        }
        $this->attr('title',$title);
        $this->attr('url',$url);
        return $this;
    }
    public static function create($content)
    {
        return new self($content);
    }
}
