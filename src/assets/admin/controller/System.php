<?php

namespace app\admin\controller;

use Eadmin\Controller;
use Eadmin\form\drive\Config;

use think\facade\Cache;

use Eadmin\form\Form;


use Eadmin\service\CaptchaService;
use think\facade\Db;


/**
 * 系统参数配置
 * Class System
 * @package app\admin\controller
 */
class System extends Controller
{
    /**
     * 系统参数配置
     * @auth true
     * @login true
     * @return string
     */
    public function config()
    {
        $form = new Form(new Config());
        $form->labelPosition('top');
        $form->title('系统参数配置');
        $form->image('system_web_logo', '网站LOGO')->size(80, 80);
        $form->text('system_web_name', '网站名称');
        $form->text('system_web_miitbeian', '网站备案号');
        $form->text('system_web_copyright', '网站版权信息');
        return $form;

    }

    /**
     * 获取验证码
     */
    public function verify()
    {
        $verify = CaptchaService::instance()->create();
        $verifyKey = md5($this->app->request->ip() . date('Y-m-d'));
        $verifyErrorNum = Cache::get($verifyKey);
        if ($verifyErrorNum >= 3) {
            $verify['mode'] = 2;
        } else {
            $verify['mode'] = 1;
        }
        $this->successCode($verify);
    }


}
