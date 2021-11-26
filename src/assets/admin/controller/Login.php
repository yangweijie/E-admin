<?php
/**
 * Created by PhpStorm.
 * User: rocky
 * Date: 2020-04-09
 * Time: 23:24
 */

namespace app\admin\controller;



use EasyWeChat\Factory;
use think\facade\Cache;
use think\facade\Db;
use think\facade\Validate;
use Eadmin\Controller;
use Eadmin\service\CaptchaService;
use Eadmin\service\TokenService;
use Eadmin\Admin;

/**
 * 登陆
 * Class Login
 * @package app\admin\controller
 */
class Login extends Controller
{

    /**
     * 登陆
     * @auth false
     * @login false
     */
    public function index()
    {
        if($this->request->isPost()){
            $data = $this->request->post();
            $validate = Validate::rule([
                'username' => 'require',
                'password' => 'require|min:5'
            ])->message([
                'username.require' => admin_trans('admin.account_not_empty'),
                'password.require' =>admin_trans('admin.password_not_empty'),
                'password.min' => admin_trans('admin.password_min_length'),
            ]);
            $valiRes =  $validate->check($data);
            $username = $data['username'];
            $password = $data['password'];
            if ($valiRes === true) {
                $verifyKey = md5($this->app->request->ip().date('Y-m-d'));
                $usernameVerifyKey = md5($this->app->request->ip().$username.date('Y-m-d'));
                $verifyErrorNum = Cache::get($usernameVerifyKey);
                if($verifyErrorNum >= 10){
                    $this->errorCode(3003);
                }
                $model = config('admin.database.user_model');
                $user = $model::where('username', $username)->find();
                if (empty($user) || !password_verify($password,$user['password'])) {
                    $this->app->cache->inc($usernameVerifyKey,1);
                    $this->app->cache->inc($verifyKey,1);
                    $this->errorCode(3001);
                }
                $verifyErrorNum = Cache::get($verifyKey);
                if($verifyErrorNum >= 3 && !CaptchaService::instance()->check($data['verify'],$data['hash'])){
                    $this->errorCode(4003);
                }
                if ($user['status'] == 0) {
                    $this->errorCode(3002);
                }
                //微信openid绑定账号
                if($this->request->has('wxBind') && $this->request->has('openid')){
                    $user->openid = $data['openid'];
                    $user->save();
                }
                $tokens = Admin::token()->encode($user);
                $ip = app()->request->ip();
                $user->login_ip = $ip;
                $user->login_at = date('Y-m-d H:i:s');
                $user->login_num = $user->login_num+1;
                $user->save();
                admin_success_message(admin_trans('admin.login_success'))->data($tokens);
            } else {
                admin_error_message($validate->getError());
            }
        }else{
           $where= ['system_web_logo','system_web_name','system_web_miitbeian','system_web_copyright'];
           $data = Db::name('SystemConfig')->whereIn('name',$where)->column('value','name');
           return Admin::view('/login')->attrs([
               'webLogo'=>$data['system_web_logo'],
               'webName'=>$data['system_web_name'],
               'webMiitbeian'=>$data['system_web_miitbeian'],
               'webCopyright'=>$data['system_web_copyright'],
               'deBug'=>(bool) env('APP_DEBUG')
           ]);
        }
    }
   
    /**
     * 退出登陆
     * @auth false
     * @login false
     */
    public function logout()
    {
        Admin::token()->logout();
        admin_success_message(admin_trans('admin.logout'));
    }
}
