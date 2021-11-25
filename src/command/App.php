<?php
/**
 * Created by PhpStorm.
 * User: rocky
 * Date: 2020-12-07
 * Time: 20:50
 */

namespace Eadmin\command;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use think\console\Input;
use think\console\input\Option;
use think\console\Output;
use think\console\Command;
use think\helper\Str;

class App extends Command
{
    protected function configure()
    {
        // 指令配置
        $this->setName('eadmin:app')->setDescription('创建新的应用');
        $this->addArgument('name', 1, "应用名称");
    }
    protected function execute(Input $input, Output $output)
    {
        $name = $input->getArgument('name');
        $appPath = app()->getAppPath() . $name;
        $filesystem = new Filesystem();
        if (is_dir($appPath)) {
            if (!$this->output->confirm($this->input, "确认覆盖目录[$appPath]? [y]/n")) {
                return false;
            }
            $filesystem->remove($appPath);
        }
        $filesystem = new Filesystem();
        $filesystem->mirror(__DIR__ . '/../assets/admin', $appPath);
        //控制器
        $controllPath = $appPath.DIRECTORY_SEPARATOR.'controller';
        $finder = new Finder();
        foreach ($finder->in($controllPath)->files() as $file){
            $content = file_get_contents($file->getRealPath());
            $content = str_replace([
                'admin.',
                'namespace app\admin\controller;',
            ],[
                $name.'.',
                "namespace app\\$name\\controller;",
            ],$content);
            file_put_contents($file->getRealPath(),$content);
        }
        //模型
        $modelPath = $appPath.DIRECTORY_SEPARATOR.'model';
        $filesystem->mirror(__DIR__ . '/../model', $modelPath);
        $finder = new Finder();
        foreach ($finder->in($modelPath)->files() as $file){
            $content = file_get_contents($file->getRealPath());
            $content = str_replace([
                'admin.',
                'namespace Eadmin\model;',
            ],[
                $name.'.',
                "namespace app\\$name\\model;",
            ],$content);
            file_put_contents($file->getRealPath(),$content);
        }
        //语言包
        $langPath = $appPath.DIRECTORY_SEPARATOR.'lang';
        $finder = new Finder();
        foreach ($finder->in($langPath)->files() as $file){
            $path = str_replace('admin.php',"{$name}.php",$file->getRealPath());
            $filesystem->rename($file->getRealPath(),$path);
        }
        //数据库迁移
        $databasePath = $appPath.DIRECTORY_SEPARATOR.'database';
        $filesystem->mirror(__DIR__ . '/../database', $databasePath);
        $finder = new Finder();
        foreach ($finder->in($databasePath)->files() as $file){
            if(strpos($file->getFilename(),'system_jobs') || strpos($file->getFilename(),'system_queue') || strpos($file->getFilename(),'system_config')){
                unlink($file->getRealPath());
                continue;
            }
            $content = file_get_contents($file->getRealPath());
            $content = str_replace([
                'system_',
                'CreateSystem',

            ],[
                $name.'_',
                'Create'.ucfirst($name),
            ],$content);
            file_put_contents($file->getRealPath(),$content);
            $path = str_replace('_system_',"_{$name}_",$file->getRealPath());
            $filesystem->rename($file->getRealPath(),$path,true);
        }
        $output->writeln("<info>create $name app success</info>");
    }
}
