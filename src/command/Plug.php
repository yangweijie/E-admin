<?php


namespace Eadmin\command;


use think\console\Command;
use think\console\Input;
use think\console\input\Option;
use think\console\Output;

class Plug extends Command
{
    protected $package;
    protected $namespace;
    protected $description;
    protected $className;
    protected function configure()
    {
        // 指令配置
        $this->setName('eadmin:plug')->setDescription('生成插件');
        $this->addArgument('name', 1, "插件包名");
        $this->addOption('description', 1, Option::VALUE_REQUIRED,"插件描述");
        $this->addOption('namespace', 2, Option::VALUE_REQUIRED,"命名空间");
    }
    protected function execute(Input $input,Output $output)
    {
        $this->package = $input->getArgument('name');
        $plugName = $this->package;
        $this->namespace = 'plugin\\'.$input->getOption('namespace');
        $this->description = $input->getOption('description');
        $plugDir = $this->app->getRootPath().config('admin.extension.dir','plugin');
        if(!is_dir($plugDir)) mkdir($plugDir);

        $this->className = ucfirst($plugName);
        if(is_null($this->namespace)){
           $this->namespace = $plugName;
        }
        //插件名目录
        $plugNameDir = $plugDir.DIRECTORY_SEPARATOR.$plugName.DIRECTORY_SEPARATOR;
        if(!is_dir($plugNameDir)) mkdir($plugNameDir);
        //控制器目录
        mkdir($plugNameDir.'controller');
        //控制器api目录
        mkdir($plugNameDir.'controller'.DIRECTORY_SEPARATOR.'api');
        //模型目录
        mkdir($plugNameDir.'model');
        //语言包目录
        mkdir($plugNameDir.'lang');
        //数据库
        $database = $plugNameDir.'database';
        mkdir($plugNameDir.'database');
        //数据库迁移
        mkdir($database.DIRECTORY_SEPARATOR.'migrations');
        //数据库填充
        mkdir($database.DIRECTORY_SEPARATOR.'seeds');
        //config文件
        file_put_contents($plugNameDir.'config.php','<?php');
        $res =  $this->composerFile($plugNameDir);
        if($res){
            $this->serviceFile($plugNameDir);
            file_put_contents($plugNameDir.'README.md','# Ex-admin Extension');
            $output->writeln('<info>created successfully.</info>');
        }else{
            $output->error('创建失败 (Creation failed)');
        }
    }

    protected function composerFile($dir){
        $stub =$this->getStubs('composer.json');
        $composerContent = str_replace([
            '{package}',
            '{description}',
            '{namespace}',
            '{className}',
        ],[
            $this->package,
            $this->description,
            str_replace('\\', '\\\\', $this->namespace.'\\'),
            $this->className
        ],$stub);
        $composerFile = $dir.DIRECTORY_SEPARATOR.'composer.json';
        if(is_file($composerFile)){
            $this->output->error('插件命名冲突');
        }else{
            return file_put_contents($composerFile,$composerContent);
        }
    }
    protected function serviceFile($dir){
        $stub = $this->getStubs('service');
        $serviceContent = str_replace([
            '{%namespace%}',
            '{%className%}',
        ],[
            $this->namespace,
            $this->className.'Service',
        ],$stub);
        file_put_contents($dir.$this->className.'Service.php',$serviceContent);
    }
    protected function getStubs($name)
    {
        $stubPath = __DIR__ . DIRECTORY_SEPARATOR . 'plug' . DIRECTORY_SEPARATOR;
        return  file_get_contents($stubPath . $name.'.stub');
    }
}
