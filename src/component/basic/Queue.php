<?php

namespace Eadmin\component\basic;

use Eadmin\Admin;
use Eadmin\component\Component;
use Eadmin\model\SystemQueue;
use think\app\Url;
use think\facade\Db;

/**
 * 队列执行进度通知
 * @method $this params(array $value) 附加请求参数

 * @method $this url(string $value) 请求url
 * @method $this confirm(string $value) 确认消息文案
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
     * @param string $confirm 确认消息文案
     * @return $this
     */
    public function exec($title, $url, $confirm = ''){
        if(is_string($url)){
            $url = url($url);
        }
        $dispatch = Admin::getDispatch($url);
        if($dispatch){
            list($eadmin_class, $eadmin_function)  = Admin::getDispatchCall($dispatch);
            $this->auth($eadmin_class,$eadmin_function);
        }
        $this->confirm($confirm);
        $this->attr('title',$title);
        $this->url($url->build());
        return $this;
    }
    public static function create($content)
    {
        return new static($content);
    }
}
