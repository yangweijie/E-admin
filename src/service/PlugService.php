<?php
/**
 * Created by PhpStorm.
 * User: rocky
 * Date: 2020-08-09
 * Time: 16:49
 */

namespace Eadmin\service;

use Composer\Autoload\ClassLoader;
use Eadmin\component\basic\Button;
use Eadmin\PlugServiceProvider;
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
    protected $table = 'system_plugs';
    public function __construct()
    {
        $this->initialize();;
    }
    protected function initialize()
    {
        $this->app  = app();
        $this->client = new Client([
            'base_uri' => 'https://eadmin.togy.com.cn/api/',
            'verify' => false,
        ]);
        $this->plugPathBase = app()->getRootPath() . config('admin.extension.dir', 'plugin');
        foreach (glob($this->plugPathBase . '/*') as $file) {
            if (is_dir($file) && $this->checkFiles($file)) {
                $this->plugPaths[] = $file;
            }
        }
    }


    /**
     * 获取插件目录
     * @return string
     */
    public function getPath()
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
                $loader->addPsr4(Arr::get($arr, 'namespace').'\\', $plugPaths);
                $serviceProvider = Arr::get($arr, 'services');
                if ($serviceProvider) {
                    $configPath = $plugPaths . DIRECTORY_SEPARATOR . 'src'.DIRECTORY_SEPARATOR . 'config.php';
                    $this->app->register($serviceProvider);
                    $service = $this->app->getService($serviceProvider);
                    $this->serviceProvider[$name] = $service;
                    $this->app->bind($serviceProvider,$service);
                    if(method_exists($service,'withComposerProperty')){
                        $service->withComposerProperty($arr);
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
    public function getServiceProviders($name = null){
        if(empty($name)){
            return $this->serviceProvider;
        }
        return $this->serviceProvider[$name];
    }
    public function getCate()
    {
        $response = $this->client->get("Plugs/cate");
        $content = $response->getBody()->getContents();
        $content = json_decode($content, true);
        return $content['data'];
    }

    /**
     * 获取所有插件
     * @param string $search 搜索的关键词
     */
    public function all($search = '', $cate_id = 0, $page = 1, $size = 20, $names = null)
    {
       return $this->installed();
    }

    /**
     * 已安装插件
     * @param string $search 搜索的关键词
     * @return array
     */
    public function installed($search = '')
    {
        foreach ($this->plugPaths as $plug){
            $info = $this->info(basename($plug));
            $info['install'] = true;
            $plugs[] = $info;
        }
        return $plugs;
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
    protected function infoJson($name){
        return $this->plugPathBase.DIRECTORY_SEPARATOR.$name.DIRECTORY_SEPARATOR.'info.json';
    }
    /**
     * 设置插件状态
     * @param $name
     * @param $bool
     * @return false|string
     */
    public function setStatus($name,$status){

        $content = $this->info($name);
        $content['status'] = $status;
        return file_put_contents($this->infoJson($name),json_encode($content,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
    }
    /**
     * 启用
     * @param string $name 插件名称
     * @return int
     */
    public function enable($name)
    {
       return $this->setStatus($name,true);
    }

    /**
     * 禁用
     * @param $name 插件名称
     * @return false|string
     */
    public function disable($name){
        return $this->setStatus($name,false);
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
                $info = json_decode($info,true);
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
                $file = $path. DIRECTORY_SEPARATOR . 'composer.json';
                $serviceProvider = $this->getServiceProviders($info['name']);
                $serviceProvider->addMenus();
                return true;
            } else {
                return false;
            }
        } catch (\Exception $exception) {
            return false;
        }
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
