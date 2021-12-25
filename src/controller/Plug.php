<?php


namespace Eadmin\controller;

use Eadmin\Admin;
use Eadmin\component\basic\Button;
use Eadmin\component\basic\Card;
use Eadmin\component\basic\Dropdown;
use Eadmin\component\basic\Html;
use Eadmin\component\basic\Image;
use Eadmin\component\basic\Space;
use Eadmin\component\basic\Tabs;
use Eadmin\component\basic\Tag;
use Eadmin\component\basic\TimeLine;
use Eadmin\component\form\FormAction;
use Eadmin\component\layout\Content;
use Eadmin\constant\Style;
use Eadmin\Controller;
use Eadmin\form\drive\Config;
use Eadmin\grid\Actions;
use Eadmin\grid\Grid;
use Symfony\Component\Process\Process;
use think\facade\Console;
use think\facade\Db;
use Eadmin\controller\BaseAdmin;
use Eadmin\form\Form;
use Eadmin\grid\Column;
use Eadmin\grid\Table;
use Eadmin\service\PlugService;

/**
 * 插件管理
 * Class Plug
 * @package app\admin\controller
 */
class Plug extends Controller
{
    /**
     * 插件列表
     * @auth false
     * @login true
     */
    public function index(Content $content)
    {
        $tabs = Tabs::create();
        $cates = Admin::plug()->getCate();
        $tabs->pane('全部', $this->grid());
        $tabs->pane('已安装', $this->grid(0, 1));
        foreach ($cates as $cate) {
            $tabs->pane($cate['name'], $this->grid($cate['id']));
        }
        return
            $content->title('插件管理')->content(
                Card::create($tabs)
            );

    }

    public function grid($cate_id = 0, $type = 0)
    {
        $search = $this->request->get('quickSearch');
        $page = $this->request->get('page', 1);
        $size = $this->request->get('size', 20);
        if ($type == 1) {
            $data = Admin::plug()->installed($search);
        } else {
            $data = Admin::plug()->all($search, $cate_id, $page, $size);
        }
        $grid = new Grid($data);
        $grid->drive()->setTotal(Admin::plug()->total());
        $grid->title('插件管理');
        $grid->hideSelection();
        $grid->column('cate.name', '分类')->tag('info', 'plain');
        $grid->column('title', '名称')->display(function ($val, $data) {

            return Html::div()->content([
                Image::create()
                    ->style(['width'=>'60px','height'=>'60px','marginRight'=>'10px',"borderRadius" => '5px'])
                    ->src(Admin::plug()->getLogin($data['name'])),
                Html::div()->content([
                    Html::div()->content(Tag::create($data['title'])->size('mini')->effect('dark')),
                    Html::div()->content($data['name']),
                    Html::div()->content($data['description'])
                ])
            ])->style(Style::FLEX_CENTER);
        });

        $grid->column('version', '版本');
        $grid->actions(function (Actions $actions, $rows) {
            $actions->hideDel();
            $timeLine = TimeLine::create();
            foreach ($rows['versions'] as $version) {
                $descs = explode("\n", $version['desc']);
                $html = Html::div();
                foreach ($descs as $desc) {
                    $html->content(Html::div()->content($desc));
                }
                if (count($version['requires']) > 0) {
                    $html->content(Html::div()->content('依赖插件：')->style(['marginTop' => '5px']));
                }
                $space = Space::create();
                foreach ($version['requires'] as $require) {
                    $space->content(Tag::create($require['title'])->size('mini'));
                }
                $html->content($space);
                $timeLine->item($html)->timestamp($version['version']);
            }
            $actions->append(
                Button::create('历史版本')
                    ->dialog()
                    ->content($timeLine)
            );
            if (isset($rows['setting'])) {
                $actions->append(
                    Button::create('设置')
                        ->plain()
                        ->typePrimary()
                        ->dialog()->content($rows['setting'])->whenShow(!empty($rows['setting']))
                );
            }
            if ($rows['install']) {
                if ($rows['status']) {
                    $actions->append(
                        Button::create('禁用')->sizeSmall()->typeInfo()->save(['id' => $rows['name'], 'status' => 0], 'plug/enable', '确认禁用？')
                    );
                } else {
                    $actions->append(
                        Button::create('启用')->sizeSmall()->typeSuccess()->save(['id' => $rows['name'], 'status' => 1], 'plug/enable', '确认启用？')
                    );
                }
                $actions->append(
                    Button::create('卸载')
                        ->sizeSmall()
                        ->typeDanger()
                        ->save(['name' => $rows['name']], 'plug/uninstall', '确认卸载？')
                );
            } else {
                $dropdown = Dropdown::create(
                    Button::create('安装'. ' <i class="el-icon-arrow-down" />')
                    ->sizeSmall()
                    ->typePrimary()
                );
                foreach ($rows['versions'] as $row){
                    $dropdown->item($row['version'])->save(['name'=>$rows['name'],'version'=>$row['version']],'plug/install','确认安装？');
                }

                $actions->append($dropdown);
            }
        });
        $grid->hideDeleteButton();
        $grid->tools([
            Button::create('创建扩展')
                ->typeWarning()
                ->sizeSmall()
                ->dialog()
                ->form($this->add()),
            Button::create('本地安装')->upload('plug/zipInstall')->ext('zip'),
            Button::create('登录')
                ->typeDanger()
                ->whenShow(!Admin::plug()->isLogin())
                ->sizeSmall()
                ->dialog()
                ->form($this->login()),
        ]);
        $grid->quickSearch();
        return $grid;
    }

    /**
     * 登录
     * @return Form
     */
    public function login()
    {
        return Form::create([], function (Form $form) {
            $form->text('username', '账号')->required();
            $form->password('password', '密码')->required();
            $form->actions()->submitButton()->content('登录');
            $form->saving(function ($post) {
                $res = Admin::plug()->login($post['username'], $post['password']);
                if ($res) {
                    admin_success_message('登录成功');
                } else {
                    admin_error_message('登录失败，账号密码错误');
                }
            });
        });
    }

    /**
     * 本地安装
     */
    public function zipInstall(){
        $file = $this->request->file('file');
        $res = Admin::plug()->install($file->getRealPath());
        if($res){
            admin_success_message('安装完成')->refreshMenu()->refresh();
        }else{
            admin_error_message('安装失败');
        }
    }

    /**
     * 安装
     * @param $name 插件名称
     * @param $version 版本
     */
    public function install($name, $version)
    {
        if(!Admin::plug()->isLogin()){
            admin_error_message('请先登陆');
        }
        $res = Admin::plug()->onlineInstall($name,$version);
        if($res){
            admin_success_message('安装完成')->refreshMenu()->refresh();
        }
        admin_error_message('安装失败');
    }

    /**
     * 卸载
     * @auth false
     * @login true
     */
    public function uninstall()
    {
        $name = $this->request->put('name');
        Admin::plug()->uninstall($name);
        admin_success_message('卸载完成')->refreshMenu()->refresh();
    }



    /**
     * 创建扩展
     * @auth false
     * @login true
     */
    public function add()
    {
        $form = new Form(new Config());
        $form->text('name', '扩展标识')->alphaDashRule()->required();
        $form->text('title', '名称')->required();
        $form->text('description', '描述');
        $form->saving(function ($post) {
            if(!Admin::plug()->isLogin()){
                admin_error_message('请先登陆');
            }
            if(Admin::plug()->exist($post['name'])){
                admin_error_message('插件标识已存在');
            }
            $name = $post['name'];
            $cmd['name'] = $name;
            $cmd['title'] = $post['title'];
            $description = $post['description'];
            if (!empty($description)) {
                array_push($cmd, "--description={$description}");
            }
            array_push($cmd, "--namespace={$name}");
            Console::call('eadmin:plug', $cmd);
            Admin::plug()->buildIde();
            admin_success_message('添加成功');
        });
        return $form;
    }

    /**
     * 启用/禁用
     * @auth false
     * @login true
     */
    public function enable($id, $status)
    {
        Admin::plug()->setInfo($id,['status'=>(bool)$status]);
        admin_success_message('操作完成')->refreshMenu();
    }

}
