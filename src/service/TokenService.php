<?php
/**
 * @Author: rocky
 * @Copyright: 广州拓冠科技 <http://my8m.com>
 * Date: 2019/7/11
 * Time: 15:15
 */

namespace Eadmin\service;

use Eadmin\Admin;
use Eadmin\traits\ApiJson;
use think\db\Query;
use think\facade\Cache;
use think\facade\Request;
use Eadmin\model\AdminModel;
use Eadmin\Service;


class TokenService
{

    use ApiJson;

    const IV = 'yHXo48tHnXWSyUY9';
    //密钥
    protected $key = '';
    //过期时间
    protected $expire = 7200;
    //当前token
    protected static $token = '';
    protected $model = '';
    protected static $userModel = null;
    protected $unique = false;
    protected $authFields = [];
    protected $config = [];

    public function __construct($type = null)
    {
        $this->init($type);
    }

    /**
     * 初始化
     * @param string $type 配置类型
     */
    public function init($type){

        $app = Admin::getAppName();
        $type = config($app.'.token.default');
        if(empty($type)){
            $app = 'admin';
            $type = config($app.'.token.default');
        }
        $this->config = config($app.'.token.' . $type);
        $this->key = substr(md5($this->config['key']), 8, 16);
        $this->model = $this->config['model'];
        $this->unique = $this->config['unique'];
        $this->expire = $this->config['expire'] ?? 7200;
        $this->authFields = $this->config['auth_field'] ?? [];
        if (isset($this->config['debug']) && $this->config['debug']) {
            $user = $this->model::find($this->config['uid']);
            $tokens = $this->encode($user);
            $this->set($tokens['token']);
        }
        return $this;
    }
    /**
     * 退出token
     * @Author: rocky
     * 2019/12/7 13:37
     * @param $token
     * @return mixed
     */
    public function logout($token = '')
    {
        if (empty($token)) {
            return Cache::set(md5(self::$token), time(), $this->expire);
        } else {
            return Cache::set(md5($token), time(), $this->expire);
        }
    }

    /**
     * 获取当前token
     * @Author: rocky
     * 2019/11/4 16:31
     * @return $token
     */
    public function get()
    {
        return self::$token ? self::$token : Request::header('Authorization');
    }

    /**
     * 设置token
     * @Author: rocky
     * 2019/11/4 16:31
     * @param $token
     */
    public function set($token)
    {
        self::$token = $token;
        return true;
    }

    /**
     * 清除token
     * @Author: rocky
     * 2019/11/4 16:31
     * @param $token
     */
    public function clear()
    {
        self::$token = '';
        return true;
    }

    /**
     * 返回token
     * @Author: rocky
     * 2019/7/11 15:27
     * @param $data
     * @return array
     */
    public function encode($data)
    {
        $data['expire'] = time() + $this->expire;
        $str = json_encode($data);
        $token = openssl_encrypt($str, 'aes-256-cbc', $this->key, 0, self::IV);
        if (isset($data['id'])) {
            $cacheKey = 'last_auth_token_' . $data['id'];
            //开启唯一登录就将上次token加入黑名单
            if (Cache::has($cacheKey) && $this->unique) {
                $logoutToken = Cache::get($cacheKey);
                $this->logout($logoutToken);
            }
            //保存最新token
            Cache::set($cacheKey, $token, $this->expire);
        }
        $this->set($token);
        return [
            'token' => $token,
            'expire' => (int)$this->expire
        ];
    }

    /**
     * 解密TOKEN
     * @Author: rocky
     * 2019/7/11 18:52
     * @param $token
     * @return string
     */
    public function decode($token = '')
    {
        if (empty($token)) {
            $token = Request::header('Authorization');
            if (Request::has('Authorization')) {
                $token = rawurldecode(Request::param('Authorization'));
            }
        }
        $str = openssl_decrypt($token, 'aes-256-cbc', $this->key, 0, self::IV);
        if ($str === false) {
            return false;
        } else {
            return json_decode($str, true);
        }
    }

    /**
     * 刷新token
     * @param $token
     * @return array|bool
     */
    public function refresh($token = '')
    {
        $token = $token ? $token : Request::header('Authorization');
        $data = $this->decode($token);
        if ($data) {
            $this->logout($token);
            return $this->encode($data);
        } else {
            return false;
        }
    }

    /**
     * 验证token
     * @Author: rocky
     * 2019/7/12 17:12
     * @param $token 需要验证的token
     * @return bool|\think\response\Json 通过返回真
     */
    public function auth($token = null)
    {
        if (is_null($token)) {
            $token = self::$token ? self::$token : Request::header('Authorization') ?? urldecode(Request::param('Authorization'));
        }
        if (empty($token)) {
            $this->errorCode(40000, admin_trans('token.login_auth'),['multi_app'=>Admin::getAppName()]);
        }
        $data = $this->decode($token);
        if ($data === false) {
            $this->errorCode(40001, admin_trans('token.login_auth_error'),['multi_app'=>Admin::getAppName()]);
        } elseif (Cache::has(md5($token)) && $this->unique) {
            $this->errorCode(40003, admin_trans('token.login_other'),['multi_app'=>Admin::getAppName()]);
        } elseif ($data['expire'] < time()) {
            $this->errorCode(40002, admin_trans('token.login_expire'),['multi_app'=>Admin::getAppName()]);
        }
        $model = new $this->model;
        $pk = $model->getPk();
        //验证用户表信息
        if (isset($data[$pk])) {
            if ($this->user()) {
                foreach ($this->authFields as $field) {
                    if (isset($data[$field]) && $data[$field] != $this->user()[$field]) {
                        $this->errorCode(40002, admin_trans('token.login_expire'),['multi_app'=>Admin::getAppName()]);
                    }
                }
            } else {
                $this->errorCode(40002, admin_trans('token.login_expire'),['multi_app'=>Admin::getAppName()]);
            }
        } else {
            $this->errorCode(40001, admin_trans('token.login_auth_error'),['multi_app'=>Admin::getAppName()]);
        }
        return true;
    }

    /**
     * 获取Token保存数组key下的值
     * @Author: rocky
     * 2019/7/12 17:17
     * @param $name
     * @return string
     */
    public function getVar($name)
    {
        $token = self::$token ? self::$token : rawurldecode(Request::header('Authorization'));
        $data = $this->decode($token);
        if (isset($data[$name])) {
            return $data[$name];
        } else {
            return null;
        }
    }

    /**
     * 获取用户id
     * @Author: rocky
     * 2019/7/12 17:18
     * @return string
     */
    public function id()
    {
        return $this->getVar('id') ?? 0;
    }

    /**
     * 返回用户模型
     * @param bool $lock 是否锁表
     * @return mixed
     */
    public function user($lock = false)
    {
        if (is_null($this->id())) {
            return null;
        }
        if (is_null(self::$userModel)) {
            $user = new $this->model;
            $tableFields = $user->getTableFields();
            self::$userModel = $user->lock($lock)
                ->when(in_array('delete_time', $tableFields), function (Query $query) {
                    $query->where('delete_time',0);
                })->find($this->id());
        }
        return self::$userModel;
    }
}
