<?php
/**
 * Created by PhpStorm.
 * User: rocky
 * Date: 2020-08-09
 * Time: 16:49
 */

namespace Eadmin\service;

use Composer\Autoload\ClassLoader;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use think\App;
use think\facade\Cache;
use think\facade\Console;
use think\facade\Db;
use think\helper\Arr;
use Eadmin\Service;

class PlugService extends Service
{
    protected $plugPathBase = '';
    protected $plugPaths = [];
    protected $plugs = [];
    protected static $loader;
    protected $client;

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->client = new Client([
            'base_uri' => 'https://eadmin.togy.com.cn/api/',
            'verify' => false,
        ]);
        $this->plugPathBase = app()->getRootPath() . config('admin.extension.dir', 'eadmin-plugs');

        foreach (glob($this->plugPathBase . '/*') as $file) {

            if (is_dir($file)) {
                foreach (glob($file . '/*') as $file) {
                    $this->plugPaths[] = $file;
                }
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
     * 获取 composer 类加载器.
     *
     * @return ClassLoader
     */
    public function loader()
    {
        if (!static::$loader) {
            static::$loader = include $this->app->getRootPath() . '/vendor/autoload.php';
        }
        return static::$loader;
    }

    /**
     * 注册扩展
     */
    public function register()
    {
        $loader = $this->loader();
        $plugs = [];
        foreach ($this->plugPaths as $plugPaths) {
            $file = $plugPaths . DIRECTORY_SEPARATOR . 'composer.json';
            if (is_file($file)) {
                $arr = json_decode(file_get_contents($file), true);
                $plugs[] = $arr;
            }
        }
        $names = array_column($plugs, 'name');

        try {
            $plugNames = Db::name('system_plugs')->whereIn('name', $names)->where('status', 1)->column('name');
        } catch (\Exception $exception) {
            $plugNames = [];
        }
        foreach ($this->plugPaths as $plugPaths) {
            $file = $plugPaths . DIRECTORY_SEPARATOR . 'composer.json';
            if (is_file($file)) {
                $arr = json_decode(file_get_contents($file), true);
                $psr4 = Arr::get($arr, 'autoload.psr-4');
                $name = Arr::get($arr, 'name');
                if (in_array($name, $plugNames)) {
                    if ($psr4) {
                        foreach ($psr4 as $namespace => $path) {
                            $path = $plugPaths . '/' . trim($path, '/') . '/';
                            $loader->addPsr4($namespace, $path);
                        }
                    }
                    $serviceProvider = Arr::get($arr, 'extra.e-admin');
                    if ($serviceProvider) {
                        $this->app->register($serviceProvider);
                    }
                }
            }
        }
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
        $response = $this->client->get("plugs/list", [
            'query' => [
                'cate_id' => $cate_id,
                'page' => $page,
                'size' => $size,
                'search' => $search,
                'names' => $names,
            ]
        ]);
        $content = $response->getBody()->getContents();
        $plugs = json_decode($content, true)['data']['data'];
        $delNames = [];
        foreach ($plugs as &$plug) {
            $status = $this->getInfo($plug['composer'], 'status');
            $plug['status'] = $status ?? false;
            $plug['install_version'] = $this->getInfo($plug['composer'], 'version');
            $plug['install'] = is_null($status) ? false : true;
            $plug['path'] = $this->plugPathBase . '/' . $plug['composer'];
            $this->plugs[] = $plug;
            if (!is_dir($plug['path'])) {
                $plug['status'] = false;
                $delNames[] = trim($plug['composer']);
            };
        }
        Db::name('system_plugs')->whereIn('name', $delNames)->delete();
        return $this->plugs;
    }

    /**
     * 已安装插件
     * @param string $search 搜索的关键词
     * @return array
     */
    public function installed($search = '', $page = 1, $size = 20)
    {
        $names = Db::name('system_plugs')->column('name');
        if (count($names) == 0) {
            return [];
        }
        $onlinePlugs = $this->all($search, 0, $page, $size, $names);

        foreach ($this->plugPaths as $plugPaths) {
            $file = $plugPaths . DIRECTORY_SEPARATOR . 'composer.json';
            if (is_file($file)) {
                $arr = json_decode(file_get_contents($file), true);
                $plugs[] = $arr;
            }
        }
        $onlinePlugNames = array_column($onlinePlugs, 'composer');

        $plugnames = array_column($plugs, 'name');

        $names = array_diff($plugnames,$onlinePlugNames);
        foreach ($names as $name){
            $status = $this->getInfo($name, 'status');
            $plug['status'] = $status ?? false;
            $plug['install_version'] = '';
            $plug['install'] = is_null($status) ? false : true;
            $plug['path'] = $this->plugPathBase . '/' . $plug['composer'];
            $this->plugs[] = $plug;
            if (!is_dir($plug['path'])) {
                $plug['status'] = false;
                $delNames[] = trim($plug['composer']);
            };
        }

    }

    /**
     * 插件状态
     * @param string $name 插件名称
     * @param string $field 字段
     * @return mixed
     */
    public function getInfo($name, $field = 'status')
    {
        try {
            return Db::name('system_plugs')->where('name', $name)->value($field);
        } catch (\Exception $exception) {
            return false;
        }
    }

    /**
     * 启用禁用
     * @param string $name 插件名称
     * @param int $status 状态
     * @return int
     * @throws \think\db\exception\DbException
     */
    public function enable($name, $status)
    {
        return Db::name('system_plugs')->where('name', $name)->update(['status' => $status]);
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
            !is_dir($directory . '/src')
            || !is_file($directory . '/composer.json')
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
        $migrations = $path . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'migrations';
        if (is_dir($migrations)) {
            Console::call('migrate:eadmin', ['cmd' => $cmd, 'path' => $migrations]);
        }

        return true;
    }

    /**
     * 安装
     * @param string $name 插件名称
     * @param string $path 插件目录
     * @param string $version 版本
     * @return mixed
     */
    public function install($name, $path, $version)
    {
        try {
            $client = new Client(['verify' => false]);
            $plugZip = app()->getRootPath() . 'plug' . time() . '.zip';
            $client->get($path, ['save_to' => $plugZip]);
            $zip = new \ZipArchive();
            if ($zip->open($plugZip) === true) {
                $path = $this->plugPathBase . '/' . $name;
                if (!is_dir($path)) {
                    mkdir($path, 0755, true);
                }
                for ($i = 0; $i < $zip->numFiles; $i++) {
                    if ($i > 0) {
                        $filename = $zip->getNameIndex($i);
                        $pathArr = explode('/', $filename);
                        array_shift($pathArr);
                        $pathName = implode('/', $pathArr);
                        $fileInfo = pathinfo($filename);
                        $toFile = $path . '/' . $pathName;
                        if (isset($fileInfo['extension'])) {
                            copy("zip://" . $plugZip . "#" . $filename, $toFile);
                        } else {
                            if (!is_dir($toFile)) mkdir($toFile, 0755, true);
                        }
                    }
                }
                //关闭
                $zip->close();
                unlink($plugZip);
                $this->dataMigrate('run', $path);
                $seed = $path . '/src/database' . DIRECTORY_SEPARATOR . 'seeds';
                if (is_dir($seed)) {
                    Console::call('seed:eadmin', ['path' => $seed]);
                }
                Db::name('system_plugs')->insert([
                    'name' => trim($name),
                    'version' => $version
                ]);
                return true;
            } else {
                return false;
            }
        } catch (RequestException $exception) {
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
        FileSystemService::instance()->delFiels($path);
        Db::name('system_plugs')->where('name', $name)->delete();
        Db::name('system_menu')->where('mark', $name)->delete();
        Db::name('system_config')->where('mark', $name)->delete();
        return true;
    }
}
