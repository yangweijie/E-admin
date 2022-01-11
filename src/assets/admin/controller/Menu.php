<?php
/**
 * Created by PhpStorm.
 * User: rocky
 * Date: 2020-04-13
 * Time: 20:28
 */

namespace app\admin\controller;


use Eadmin\Admin;
use Eadmin\component\basic\Button;
use Eadmin\component\basic\Dropdown;
use Eadmin\component\basic\DropdownItem;
use Eadmin\component\basic\Html;
use Eadmin\component\basic\Link;
use Eadmin\component\basic\Tip;
use Eadmin\component\layout\Content;
use Eadmin\component\layout\Row;
use Eadmin\Controller;
use Eadmin\grid\Actions;
use Eadmin\form\Form;
use Eadmin\detail\Detail;
use Eadmin\grid\Filter;
use Eadmin\grid\Grid;
use Eadmin\model\SystemAuthMenu;
use Eadmin\model\SystemMenu;
use Eadmin\service\MenuService;
use think\facade\Filesystem;


/**
 * 系统菜单管理
 * Class Menu
 * @package app\admin\controller
 */
class Menu extends Controller
{
    /**
     * 系统菜单管理
     * @auth true
     * @login true
     * @return Grid
     */
    public function index()
    {
        $model = config('admin.database.menu_model');
        return Grid::create(new $model,function (Grid $grid){
            $grid->treeTable();
            $grid->title(admin_trans('menu.title'));
            $grid->column('name', admin_trans('menu.fields.name'))->display(function ($val, $data) {
                return "<i class='{$data['icon']}'></i> " . admin_trans('menu.titles.'.$val,$val);
            });
            $grid->column('url',  admin_trans('menu.fields.url'))->display(function ($val) {
                if(empty($val) || $val == '#'){
                    return $val;
                }
                return Link::create($val)->href("#/$val");
            });
            $grid->column('status', admin_trans('menu.fields.status'))->switch();
            $grid->column('open', admin_trans('menu.fields.open'))->switch();
            $grid->column('admin_visible', admin_trans('menu.fields.super_status'))->switch(admin_trans('menu.options.admin_visible'));
            $grid->actions(function (Actions $action, $data) {
                $action->prepend(
                    Button::create(admin_trans('menu.add'))
                        ->plain()
                        ->sizeSmall()
                        ->typePrimary()
                        ->dialog()
                        ->form($this->form($data['id']))
                );
                $action->hideDetail();

            });
            $grid->sortInput();
            $grid->setForm($this->form())->dialog();
            $grid->quickSearch();
            $grid->updated(function (){
                cache('eadmin_menu_all',null);
                admin_success(admin_trans('admin.operation_complete'), admin_trans('admin.save_success'))->refreshMenu();
            });
            $grid->deleted(function (){
                cache('eadmin_menu_all',null);
                admin_success(admin_trans('admin.operation_complete'), admin_trans('admin.delete_complete'))->refreshMenu();
            });
        });
    }

    /**
     * 系统菜单
     * @auth true
     * @login true
     * @return Form
     */
    public function form($pid=0): Form
    {
        $model = config('admin.database.menu_model');
        return Form::create(new $model,function (Form $form) use($pid,$model){
            $menus = Admin::menu()->listOptions();
            $form->select('pid', admin_trans('menu.fields.pid'))->default($pid)
                ->options([0 => admin_trans('menu.fields.top')] + array_column($menus, 'label', 'id'))
                ->required();
            $form->text('name', admin_trans('menu.fields.name'))->required();
            $form->text('url', admin_trans('menu.fields.url'));
            $form->icon('icon', admin_trans('menu.fields.icon'));
            $form->number('sort', admin_trans('menu.fields.sort'))->default($model::where('pid',$pid)->max('sort')+1);
            $form->saved(function (){
                cache('eadmin_menu_all',null);
                admin_success(admin_trans('admin.operation_complete'), admin_trans('admin.save_success'))->refreshMenu();
            });
        });
    }
}
