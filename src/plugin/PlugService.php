<?php
/**
 * Created by PhpStorm.
 * User: rocky
 * Date: 2020-08-09
 * Time: 16:49
 */

namespace Eadmin\plugin;

use Composer\Autoload\ClassLoader;
use Eadmin\component\basic\Button;
use Eadmin\plugin\PlugServiceProvider;
use Eadmin\support\Composer;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Cookie\SetCookie;
use GuzzleHttp\Exception\RequestException;
use think\App;
use think\facade\Cache;
use think\facade\Console;
use think\facade\Db;
use think\facade\Request;
use think\helper\Arr;

class PlugService
{
    /**
     * 插件基础目录
     * @var string
     */
    protected $plugPathBase = '';
    /**
     * 插件目录集合
     * @var array
     */
    protected $plugPaths = [];
    protected $plugs = [];

    /**
     * 插件服务集合
     * @var array
     */
    protected $serviceProvider = [];
    protected $client;
    protected $total = 0;
    protected $loginToken = '';
    public function __construct()
    {
        $this->initialize();;
    }

    protected function initialize()
    {
        $this->app = app();
        $this->client = new Client([
            'base_uri' => 'http://1.117.208.85:1000/api/Plugin/',
            'verify' => false,
        ]);
        $this->plugPathBase = app()->getRootPath() . config('admin.extension.dir', 'plugin');
        foreach (glob($this->plugPathBase . '/*') as $file) {
            if (is_dir($file) && $this->checkFiles($file)) {
                $this->plugPaths[] = $file;
            }
        }
        $this->loginToken = md5(Request::header('Authorization').'plug');
    }
    /**
     * 是否登录
     * @return bool
     */
    public function isLogin(){
        return Cache::has($this->loginToken);
    }

    /**
     * 获取插件目录集合
     * @return array
     */
    public function getPlugPath(){
        return $this->plugPaths;
    }
    /**
     * 获取插件基本目录
     * @return string
     */
    public function getBasePath()
    {
        return $this->plugPathBase;
    }


    /**
     * 注册扩展
     */
    public function register()
    {
        $loader = Composer::loader();
        foreach ($this->plugPaths as $plugPaths) {
            $file = $plugPaths . DIRECTORY_SEPARATOR . 'info.json';
            if (is_file($file)) {
                $arr = Composer::parse($file);
                $arr['plug_path'] = $plugPaths;
                $psr4 = Arr::get($arr, 'namespace');
                $name = Arr::get($arr, 'name');
                $loader->addPsr4(Arr::get($arr, 'namespace') . '\\', $plugPaths);
                $serviceProvider = Arr::get($arr, 'services');
                if ($serviceProvider && $arr['status']) {
                    $configPath = $plugPaths . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'config.php';
                    $this->app->register($serviceProvider);
                    $service = $this->app->getService($serviceProvider);
                    $this->serviceProvider[$name] = $service;
                    $this->app->bind($serviceProvider, $service);
                    if (method_exists($service, 'withinfo')) {
                        $service->withinfo($arr);
                    }
                }
            }
        }
    }

    /**
     * 获取注册插件服务
     * @param null $name 插件名称
     * @return PlugServiceProvider
     */
    public function getServiceProviders($name = null)
    {
        if (empty($name)) {
            return $this->serviceProvider;
        }
        return $this->serviceProvider[$name];
    }

    public function getCate()
    {
        $response = $this->client->get("cate");
        $content = $response->getBody()->getContents();
        $content = json_decode($content, true);
        return $content['data'];
    }

    /**
     * 获取所有插件
     * @param string $search 搜索的关键词
     */
    public function all($search = '', $cate_id = 0, $page = 1, $size = 20)
    {
        if(count($this->plugs) == 0){
            $response = $this->client->get("list", [
                'query' => [
                    'cate_id' => $cate_id,
                    'page' => $page,
                    'size' => $size,
                    'search' => $search,
                ]
            ]);
            $content = $response->getBody()->getContents();
            $content = json_decode($content, true);
            $this->plugs = $content['data']['data'];
            $this->total = $content['data']['total'];
            $names = array_column($this->installed(),'name');
            foreach ($this->plugs as &$plug) {
                $plug['install'] = false;
                if(in_array($plug['name'],$names)){
                    $info = $this->info($plug['name']);
                    $plug['version'] = $info['version'];
                    $plug['install'] = true;
                }
            }
        }
        return $this->plugs;
    }
    public function total(){
        return $this->total;
    }
    /**
     * 已安装插件
     * @param string $search 搜索的关键词
     * @return array
     */
    public function installed($search = '')
    {
        $plugs = [];
        foreach ($this->plugPaths as $plug) {
            $info = $this->info(basename($plug));
            if(isset($this->serviceProvider[$info['name']])){
                $service = $this->serviceProvider[$info['name']];
                if(method_exists($service,'setting')){
                    $info['setting'] = app()->invoke([$service,'setting']);
                }
            }
            $info['install'] = true;
            $info['versions'] = [];
            $plugs[] = $info;
        }
        return $plugs;
    }
    /**
     * 登录
     * @param string $username 账号
     * @param string $password 密码
     * @return mixed
     */
    public function login($username,$password){
        $response = $this->client->post('login',[
            'form_params'=>[
                'username'=>$username,
                'password'=>$password,
            ]
        ]);
        $content = $response->getBody()->getContents();
        $res = json_decode($content, true);
        if($res['code'] == 200){
            Cache::set($this->loginToken,$res['data']['token'],60*60*24);
            return true;
        }else{
            return false;
        }
    }

    /**
     * 是否安装插件
     * @param $name 插件名称
     * @return bool
     */
    public function isInstall($name)
    {
        $path = $this->plugPathBase . DIRECTORY_SEPARATOR . $name;
        return $this->checkFiles($path);
    }

    /**
     * 插件信息
     * @param string $name 插件名称
     * @return array
     */
    public function info($name)
    {
        return Composer::parse($this->infoJson($name));
    }

    protected function infoJson($name)
    {
        return $this->plugPathBase . DIRECTORY_SEPARATOR . $name . DIRECTORY_SEPARATOR . 'info.json';
    }

    /**
     * 设置插件信息
     * @param $name
     * @param $bool
     * @return false|string
     */
    public function setInfo($name, array $data)
    {

        $content = $this->info($name);
        $content = array_merge($content, $data);
        return file_put_contents($this->infoJson($name), json_encode($content, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    }

    /**
     * 启用
     * @param string $name 插件名称
     * @return int
     */
    public function enable($name)
    {
        return $this->setInfo($name, ['status' => true]);
    }

    /**
     * 禁用
     * @param $name 插件名称
     * @return false|string
     */
    public function disable($name)
    {
        return $this->setInfo($name, ['status' => false]);
    }

    /**
     * 校验扩展包内容是否正确.
     *
     * @param string $directory
     *
     * @return bool
     */
    protected function checkFiles($directory)
    {
        if (
            !is_file($directory . '/config.php')
            || !is_file($directory . '/info.json')
        ) {
            return false;
        }
        return true;
    }

    /**
     * 执行命令
     * @param string $cmd 命令
     * @param string $path 路径
     * @return bool
     */
    protected function dataMigrate($cmd, $path)
    {
        $migrations = $path . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'migrations';
        if (is_dir($migrations)) {
            Console::call('migrate:eadmin', ['cmd' => $cmd, 'path' => $migrations]);
        }

        return true;
    }

    /**
     * 安装
     * @param string $fileZip zip压缩包
     * @return mixed
     */
    public function install($fileZip)
    {
        try {
            $zip = new \ZipArchive();
            if ($zip->open($fileZip) === true) {
                $info = $zip->getFromName('info.json');
                $info = json_decode($info, true);
                $path = $this->plugPathBase . '/' . $info['name'];
                if (!is_dir($path)) {
                    mkdir($path, 0755, true);
                }
                $zip->extractTo($path);
                //关闭
                $zip->close();
                $this->dataMigrate('run', $path);
                $seed = $path . '/database' . DIRECTORY_SEPARATOR . 'seeds';
                if (is_dir($seed)) {
                    Console::call('seed:eadmin', ['path' => $seed]);
                }
                $this->initialize();
                $this->register();
                //添加菜单
                $serviceProvider = $this->getServiceProviders($info['name']);
                $serviceProvider->addMenus();
                //生成ide提示
                $this->buildIde();
                return true;
            } else {
                return false;
            }
        } catch (\Exception $exception) {
            dump($exception->getMessage());
            halt($exception->getTraceAsString());
            return false;
        }
    }

    public function buildIde()
    {
        $this->initialize();
        $doc = '';
        $count = count($this->plugPaths);
        foreach ($this->plugPaths as $index => $plug) {
            $name = basename($plug);
            $info = $this->info($name);
            $name = $info['name'];
            $title = $info['title'];
            $doc .= " * @property $name\\" . ucfirst($name) . "Service \$$name $title";
            if (($index + 1) != $count) {
                $doc .= PHP_EOL;
            }
        }
        $content = <<<PHP
<?php
namespace plugin;
/**
$doc
 */
class IDE{}
PHP;
        return file_put_contents($this->plugPathBase . DIRECTORY_SEPARATOR . 'IDE.php', $content);
    }

    /**
     * 卸载
     * @param string $name 插件名称
     * @param string $path 插件路径
     */
    public function uninstall($name, $path)
    {
        $this->dataMigrate('rollback', $path);
        $filesystem = new \Symfony\Component\Filesystem\Filesystem;
        $filesystem->remove($path);
        Db::name($this->table)->where('name', $name)->delete();
        Db::name('system_menu')->where('mark', $name)->delete();
        Db::name('system_config')->where('mark', $name)->delete();
        Db::name('system_config_cate')->where('mark', $name)->delete();
        return true;
    }
}
