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
use think\facade\Console;
use think\helper\Str;

class App extends Command
{
    protected function configure()
    {
        // 指令配置
        $this->setName('eadmin:app')->setDescription('创建新的应用');
        $this->addArgument('name', 1, "应用名称");
        $this->addOption('delete', 'd', Option::VALUE_NONE, '删除应用');
    }

    protected function execute(Input $input, Output $output)
    {
        $name = $input->getArgument('name');
        $isDel = $input->getOption('delete');
        $appPath = app()->getAppPath() . $name;
        $filesystem = new Filesystem();
        $databasePath = $appPath . DIRECTORY_SEPARATOR . 'database';
        $migrationsPath = $databasePath . DIRECTORY_SEPARATOR . 'migrations';
        $seedsPath = $databasePath . DIRECTORY_SEPARATOR . 'seeds';
        if ($isDel) {
            if (!$this->output->confirm($this->input, "确认删除[$name]应用，删除后无法恢复? [y]/n")) {
                return false;
            }
            Console::call('migrate:eadmin', ['cmd' => 'rollback', 'path' => $migrationsPath]);
            $filesystem->remove($appPath);
            $output->writeln("<info>delete $name app success</info>");
            return false;
        }
        if (is_dir($appPath)) {
            if (!$this->output->confirm($this->input, "确认覆盖目录[$appPath]? [y]/n")) {
                return false;
            }
            $filesystem->remove($appPath);
        }
        $filesystem = new Filesystem();
        $filesystem->mirror(__DIR__ . '/../assets/admin', $appPath);
        //控制器
        $controllPath = $appPath . DIRECTORY_SEPARATOR . 'controller';
        $finder = new Finder();
        foreach ($finder->in($controllPath)->files() as $file) {
            $content = file_get_contents($file->getRealPath());
            $content = str_replace([
                'admin.',
                'namespace app\admin\controller;',
                'system_web_',
            ], [
                $name . '.',
                "namespace app\\$name\\controller;",
                $name.'_web_'
            ], $content);
            file_put_contents($file->getRealPath(), $content);
        }
        //模型
        $modelPath = $appPath . DIRECTORY_SEPARATOR . 'model';
        $filesystem->mirror(__DIR__ . '/../model', $modelPath);
        $finder = new Finder();
        $namespaces = '';
        foreach ($finder->in($modelPath)->files() as $file) {
            if (Str::startsWith($file->getFilename(), ['BaseModel', 'SystemQueue'])) {
                unlink($file->getRealPath());
                continue;
            }
            $content = file_get_contents($file->getRealPath());
            $content = str_replace([
                'admin.',
                'System',
                'AdminModel',
                'namespace Eadmin\model;',
                '\model;',
            ], [
                $name . '.',
                ucfirst($name),
                ucfirst($name) . 'User',
                "namespace app\\$name\\model;",
                '\model;' . PHP_EOL . 'use app\model\BaseModel;',
            ], $content);
            file_put_contents($file->getRealPath(), $content);

            if (Str::startsWith($file->getFilename(), 'AdminModel')) {
                $path = str_replace('AdminModel', ucfirst($name) . 'User', $file->getRealPath());
            } else {
                $path = str_replace('System', ucfirst($name), $file->getRealPath());
            }
            $filesystem->rename($file->getRealPath(), $path);
            $class = str_replace('.php', '', basename($path));
            $namespaces .= "use app\\$name\\model\\$class;" . PHP_EOL;
        }
        //语言包
        $langPath = $appPath . DIRECTORY_SEPARATOR . 'lang';
        $finder = new Finder();
        foreach ($finder->in($langPath)->files() as $file) {
            $path = str_replace('admin.php', "{$name}.php", $file->getRealPath());
            $filesystem->rename($file->getRealPath(), $path);
        }
        //数据库迁移

        $filesystem->mirror(__DIR__ . '/../database/migrations', $migrationsPath);
        $finder = new Finder();
        $i = 0;
        foreach ($finder->in($migrationsPath)->files() as $file) {
            if (strpos($file->getFilename(), 'system_jobs') || strpos($file->getFilename(), 'system_queue') || strpos($file->getFilename(), 'system_config')) {
                unlink($file->getRealPath());
                continue;
            }
            $content = file_get_contents($file->getRealPath());
            $content = str_replace([
                'system_',
                'CreateSystem',

            ], [
                $name . '_',
                'Create' . ucfirst($name),
            ], $content);
            file_put_contents($file->getRealPath(), $content);
            $path = str_replace('_system_', "_{$name}_", $file->getRealPath());
            $i++;
            $path = preg_replace("/[\d]+/", date('Ymdhi') . $i, $path, 1);
            $filesystem->rename($file->getRealPath(), $path, true);
        }
        //数据库迁移
        $filesystem->mirror(__DIR__ . '/../database/seeds', $seedsPath);
        $finder = new Finder();
        foreach ($finder->in($seedsPath)->files() as $file) {
            $content = file_get_contents($file->getRealPath());
            $content = str_replace([
                "system_user'",
                "system_menu'",
                'System',
                'admin/',
                'system_web_',
            ], [
                $name . "_user'",
                $name . "_menu'",
                ucfirst($name),
                $name . '/',
                $name.'_web_'
            ], $content);
            file_put_contents($file->getRealPath(), $content);
            $path = str_replace('System', ucfirst($name), $file->getRealPath());
            $filesystem->rename($file->getRealPath(), $path, true);
        }
        //配置文件
        $content = file_get_contents(__DIR__ . '/stubs/app.stub');

        $content = str_replace([
            'System',
            'system_',
            'AdminModel',
            '{%namespace%}',
            '{%name%}',
            'QsoYEClMJsgOSWUBkSCq26yWkApqSuH1',
        ], [
            ucfirst($name),
            $name . '_',
            ucfirst($name) . 'User',
            $namespaces,
            $name,
            Str::random(32)
        ], $content);
        $configFile = app()->getConfigPath() . $name . '.php';
        if (is_file($configFile)) {
            if (!$this->output->confirm($this->input, "确认覆盖文件[$configFile]? [y]/n")) {
                return false;
            }
        }
        file_put_contents($configFile, $content);

        Console::call('migrate:eadmin', ['cmd' => 'run', 'path' => $migrationsPath]);
        Console::call('seed:eadmin', ['path' => $seedsPath]);
        $output->writeln("<info>create $name app success</info>");
    }
}
