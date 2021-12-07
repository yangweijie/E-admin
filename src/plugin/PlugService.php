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
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use think\App;
use think\facade\Cache;
use think\facade\Console;
use think\facade\Db;
use think\facade\Request;
use think\helper\Arr;

/**
 * @property Client $client
 */
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
            'base_uri' => 'https://www.ex-admin.com/api/Plugin/',
            'verify' => false,
            'timeout' => 10
        ]);
        $this->plugPathBase = app()->getRootPath() . config('admin.extension.dir', 'plugin');
        foreach (glob($this->plugPathBase . '/*') as $file) {
            if (is_dir($file) && $this->checkFiles($file)) {
                $name = basename($file);
                $this->plugPaths[$name] = $file;
            }
        }
        $this->loginToken = md5(Request::header('Authorization') . 'plug');
    }

    /**
     * 登录token
     * @return mixed
     */
    public function token(){
        return Cache::get($this->loginToken);
    }
    /**
     * 是否登录
     * @return bool
     */
    public function isLogin()
    {
        return Cache::has($this->loginToken);
    }

    /**
     * 获取插件目录集合
     * @return array
     */
    public function getPlugPath()
    {
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
        if (count($this->plugPaths) > 0 && !Cache::has('plugverify' . date('Y-m-d'))) {
            $this->verify();
        }
        $loader = Composer::loader();
        $unauthorized = Cache::get('plugverify'.date('Y-m-d')) ?? [];
        foreach ($this->plugPaths as $name => $plugPaths) {
            if(in_array($name,$unauthorized)) continue;
            $arr = $this->info($name);
            $arr['plug_path'] = $plugPaths;
            $psr4 = Arr::get($arr, 'namespace');
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

    private function verify()
    {
        $data = [];
        foreach ($this->serviceProvider as $serviceProvider) {
            $data[] = [
                'name' => $serviceProvider->getName(),
                'info' => $serviceProvider->getInfo(),
            ];
        }
        try {
            $response = $this->client->post('verify', [
                'form_params' => [
                    'domain' => request()->host(),
                    'plugs' => $data,
                ]
            ]);
            if ($response->getStatusCode() == 200) {
                $content = $response->getBody()->getContents();
                $content = json_decode($content, true);
                Cache::set('plugverify' . date('Y-m-d'), $content['data'], 60 * 60 * 24);
            }
        } catch (\Exception $exception) {

        }

    }

    /**
     * 获取插件logo
     * @param $name
     * @return null
     */
    public function getLogin($name){
        $file = $this->plugPathBase . DIRECTORY_SEPARATOR . $name.DIRECTORY_SEPARATOR.'logo.png';

        if(is_file($file)){
            $content =  file_get_contents($file);
            return 'data:image/png;base64,' . base64_encode($content);
        }
        return null;
    }
    public function authorize($info)
    {
        $response = $this->client->post('authorize', [
            'headers' => [
                'Authorization' => self::token()
            ],
            'form_params' => [
                'info' => $info,
            ]
        ]);
        $content = $response->getBody()->getContents();
        $content = json_decode($content, true);
        if (empty($content['data'])) {
            return false;
        }
        return $content['data'];
    }

    protected function valid($info)
    {
        $response = $this->client->post('valid', [
            'form_params' => [
                'info' => $info,
            ]
        ]);
        $content = $response->getBody()->getContents();
        $content = json_decode($content, true);
        if ($content['code'] == 200) {
            return true;
        }
        return false;
    }

    /**
     * 插件是否存在
     * @param $name
     * @return bool
     */
    public function exist($name)
    {
        $response = $this->client->post('exist', [
            'form_params' => [
                'name' => $name,
            ]
        ]);
        $content = $response->getBody()->getContents();
        $content = json_decode($content, true);
        if ($content['code'] == 200) {
            return false;
        } else {
            return true;
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
        if (isset($this->serviceProvider[$name])) {
            return $this->serviceProvider[$name];
        }
        return null;
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
    public function all($search = '', $cate_id = 0, $page = 1, $size = 20, $names = [])
    {
        if (count($this->plugs) == 0) {
            $response = $this->client->post("list", [
                'form_params' => [
                    'cate_id' => $cate_id,
                    'page' => $page,
                    'size' => $size,
                    'search' => $search,
                    'names' => $names,
                ]
            ]);
            $content = $response->getBody()->getContents();
            $content = json_decode($content, true);

            $this->plugs = $content['data']['data'];
            $this->total = $content['data']['total'];

            if(count($this->plugs) > 0){
                $names = array_column($this->installed(), 'name');
            }
            foreach ($this->plugs as &$plug) {
                $plug['install'] = false;
                if (in_array($plug['name'], $names)) {
                    $info = $this->info($plug['name']);
                    $plug['version'] = $info['version'];
                    $plug['status'] = $this->info($plug['name'])['status'];
                    $plug['install'] = true;
                }
            }
        }
        return $this->plugs;
    }

    public function total()
    {
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
        $names = [];

        foreach ($this->plugPaths as $name => $plug) {
            $info = $this->info($name);
            $names[] = $info['name'];
        }

        $onlinePlugs = $this->all('', 0, 1, 1000, $names);

        $names = array_column($onlinePlugs, 'name');
        foreach ($this->plugPaths as $name => $plug) {
            $info = $this->info($name);
            $info['install'] = true;
            $info['versions'] = [];
            $info['requires'] = [];
            $index = array_search($info['name'], $names);
            if ($index !== false) {
                $info = $onlinePlugs[$index];
            }
            if (isset($this->serviceProvider[$info['name']])) {
                $service = $this->serviceProvider[$info['name']];
                if (method_exists($service, 'setting')) {
                    $info['setting'] = app()->invoke([$service, 'setting']);
                }
            }
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
    public function login($username, $password)
    {
        $response = $this->client->post('login', [
            'form_params' => [
                'username' => $username,
                'password' => $password,
            ]
        ]);
        $content = $response->getBody()->getContents();
        $res = json_decode($content, true);
        if ($res['code'] == 200) {
            halt($res['data']['token']);
            Cache::set($this->loginToken, $res['data']['token'], 60 * 60 * 24);
            return true;
        } else {
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
        $info = Composer::parse($directory . '/info.json');
        if (!isset($info['authorize_key'])) {
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
     * 在线安装
     * @param string $name 插件名称
     * @param string $version 版本
     * @return bool
     */
    public function onlineInstall($name, $version)
    {
        $plugZip = app()->getRuntimePath() . 'plug' . time() . '.zip';
        $this->client->get('install', [
            'query' => [
                'name' => $name,
                'version' => $version,
            ],
            'headers' => [
                'Authorization' => self::token()
            ],
            'save_to' => $plugZip
        ]);
        $filesystem = new Filesystem();
        try {
            $zip = new \ZipArchive();
            if ($zip->open($plugZip) === true) {
                $path = app()->getRuntimePath() . 'plugin';
                if (!is_dir($path)) {
                    mkdir($path, 0755, true);
                }
                $zip->extractTo($path);
                $zip->close();
                $finder = new Finder();
                foreach ($finder->in($path)->files() as $file) {
                    $this->install($file->getRealPath());
                }
                $filesystem->remove($path);
                unlink($plugZip);
                return true;
            } else {
                unlink($plugZip);
                return false;
            }
        } catch (\Exception $exception) {
            return false;
        }
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
                    $zip->extractTo($path);
                    //关闭
                    $zip->close();
                    if (!$this->checkFiles($path) || !$this->valid($info)) {
                        $filesystem = new Filesystem();
                        $filesystem->remove($path);
                        return false;
                    }
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
                }
                return false;
            } else {
                return false;
            }
        } catch (\Exception $exception) {
            return false;
        }
    }

    public function buildIde()
    {
        $this->initialize();
        $doc = '';
        $count = count($this->plugPaths);
        $this->plugPaths = array_unique($this->plugPaths);
        $i = 0;
        foreach ($this->plugPaths as $name => $plug) {
            $info = $this->info($name);
            $title = $info['title'];
            $doc .= " * @property $name\\" . ucfirst($name) . "Service \$$name $title";
            if (($i + 1) != $count) {
                $doc .= PHP_EOL;
            }
            $i++;
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
     */
    public function uninstall($name,$except=[])
    {
        //查找插件依赖关系
        $plugs = array_keys($this->plugPaths);
        $requires = [];
        $except[] = $name;
        foreach ($plugs as $plug) {
            if (in_array($plug,$except)) continue;
            if($this->isInstall($plug)){
                $info = $this->info($plug);
                $requires = array_merge($requires, array_keys($info['plugin']));
            }
        }
        $info = $this->info($name);
        $requires = array_diff(array_keys($info['plugin']), $requires);

        //卸载依赖插件
        foreach ($requires as $require) {
            $this->uninstall($require,$except);
        }
        $path = $this->plugPathBase . '/' . $name;
        $this->dataMigrate('rollback', $path);
        $filesystem = new \Symfony\Component\Filesystem\Filesystem;
        $filesystem->remove($path);
        Db::name('system_menu')->where('mark', $name)->delete();
        Db::name('system_config')->where('mark', $name)->delete();
        return true;
    }
}
