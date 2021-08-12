<?php
/**
 * Created by PhpStorm.
 * User: rocky
 * Date: 2020-03-25
 * Time: 21:43
 */

namespace Eadmin;


use Eadmin\component\basic\Message;
use Eadmin\component\basic\Notification;
use Eadmin\controller\Crontab;
use Eadmin\controller\FileSystem;
use Eadmin\controller\ResourceController;
use Eadmin\controller\Queue;
use Eadmin\facade\Schedule;
use Eadmin\middleware\Response;
use Eadmin\service\BackupData;
use Eadmin\service\MenuService;
use Eadmin\service\QueueService;
use think\facade\Console;
use think\facade\Db;
use think\route\Resource;
use think\Service;
use Eadmin\controller\Backup;
use Eadmin\controller\Log;
use Eadmin\controller\Menu;
use Eadmin\controller\Notice;
use Eadmin\controller\Plug;
use Eadmin\middleware\Permission;
use Eadmin\service\FileService;
use Eadmin\service\PlugService;
use Eadmin\service\TableViewService;

class ServiceProvider extends Service
{
    public function register()
    {
        //json压缩
        $this->zlib();
        //注册上传路由
        FileService::instance()->registerRoute();
        //注册插件
        PlugService::instance()->register();
        //视图路由
        Admin::registerRoute();;
        //权限中间件
        $this->app->middleware->route(\Eadmin\middleware\Permission::class);
        $this->registerService();
    }
    //检测静态文件版本发布
    protected function publishVersion(){
        $file = __DIR__.'/assets/public/version.txt';
        $system_version = file_get_contents($file);
        $file = app()->getRootPath().'public/eadmin/version.txt';
        $version = '';
        if(is_file($file)){
            $version = file_get_contents($file);
        }
        if($system_version != $version){
            Console::call('eadmin:publish',['-f','-p']);
        }
    }
    protected function zlib(){
        header("Access-Control-Allow-Origin:*");
        if (extension_loaded('zlib')) {
            if (isset($_SERVER['HTTP_ACCEPT_ENCODING']) and strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== FALSE) {
                ob_start('ob_gzhandler');
            }
        }
    }
    protected function registerService()
    {
        $this->app->bind([
            'admin.menu'         => MenuService::class,
            'admin.message'      => Message::class,
            'admin.notification' => Notification::class,
        ]);
    }
    protected function crontab(){
        try{
            Schedule::call('数据库备份和定时清理excel目录',function () {
                //数据库备份
                if(sysconf('databackup_on') == 1){
                    BackupData::instance()->backup();
                    $list = BackupData::instance()->getBackUpList();
                    if(count($list) > sysconf('database_number')){
                        $backData = array_pop($list);
                        BackupData::instance()->delete($backData['id']);
                    }
                }
                //定时清理excel目录
                $fileSystem = new \Symfony\Component\Filesystem\Filesystem();
                $fileSystem->remove(app()->getRootPath().'public/upload/excel');
            })->everyDay(sysconf('database_day'));
        }catch (\Exception $exception){

        }
    }
    public function boot()
    {
        $this->commands([
            'Eadmin\command\BuildView',
            'Eadmin\command\Publish',
            'Eadmin\command\Plug',
            'Eadmin\command\Migrate',
            'Eadmin\command\Seed',
            'Eadmin\command\Iseed',
            'Eadmin\command\Install',
            'Eadmin\command\ReplaceData',
            'Eadmin\command\ClearDatabase',
            'Eadmin\command\Queue',
            'Eadmin\command\Crontab',
        ]);
        //定时任务
        $this->crontab();
        //检测静态文件版本发布
        $this->publishVersion();
    }
}
