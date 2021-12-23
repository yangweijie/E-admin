<?php
/**
 * Created by PhpStorm.
 * User: rocky
 * Date: 2020-04-25
 * Time: 16:38
 */

namespace app\admin\controller;

use Eadmin\component\basic\Button;
use Eadmin\component\form\FormAction;
use Eadmin\Controller;
use Eadmin\form\Form;
use Eadmin\grid\Actions;
use Eadmin\grid\Grid;
use Eadmin\model\SystemAuth;
use Eadmin\model\SystemAuthData;
use Eadmin\model\SystemAuthMenu;
use Eadmin\model\SystemAuthNode;
use Eadmin\Admin;
use think\facade\Cache;
/**
 * 系统角色管理
 * Class Auth
 * @package app\admin\controller
 */
class Auth extends Controller
{
    /**
     * 系统角色列表
     * @auth true
     * @login true
     * @return Grid
     */
    public function index(): Grid
    {
        $model = config('admin.database.auth_model');
        return Grid::create(new $model, function (Grid $grid) {
            $grid->title(admin_trans('auth.title'));
            $grid->treeTable();
            $grid->column('name', admin_trans('auth.fields.name'));
            $grid->column('desc', admin_trans('auth.fields.desc'));
            $grid->column('status', admin_trans('auth.fields.status'))->switch();
            $grid->actions(function (Actions $action, $data) {
                $dropdown = $action->dropdown();
                $dropdown->prepend(admin_trans('auth.field_grant'),'fa fa-universal-access')->dialog()
                    ->width('70%')
                    ->title(admin_trans('auth.field_title_grant'))
                    ->form($this->field($data['id']));
                $dropdown->prepend(admin_trans('auth.data_grant'),'fa fa-database')
                    ->dialog()
                    ->width('50%')
                    ->title(admin_trans('auth.data_grant'))
                    ->form($this->dataAuth($data['id'],1));
                $dropdown->prepend(admin_trans('auth.auth_grant'),'el-icon-s-check')
                    ->dialog()
                    ->width('70%')
                    ->title(admin_trans('auth.auth_grant'))
                    ->form($this->authNode($data['id']));
                $dropdown->prepend(admin_trans('auth.menu_grant'),'el-icon-menu')->dialog()
                    ->title(admin_trans('auth.menu_grant'))
                    ->form($this->menu($data['id']));
            });
            $grid->hideDeleteButton();
            $grid->hideDeleteSelection();
            $grid->setForm($this->form())->dialog();
        });
    }

    /**
     * 系统角色
     * @auth true
     * @login true
     * @return Form
     */
    public function form(): Form
    {
        $model = config('admin.database.auth_model');
        return Form::create(new $model, function (Form $form) use($model){
            $options = $model::field('id,name,pid')->select()->toArray();
            $form->hidden('admin_id')->default(Admin::id());
            $form->select('pid',admin_trans('auth.parent'))
                ->treeOptions($options);
            $form->text('name', admin_trans('auth.fields.name'))->required();
            $form->textarea('desc', admin_trans('auth.fields.desc'))->rows(4)->required();
            $form->number('sort', admin_trans('auth.fields.sort'))->default($model::max('sort')+1);
        });
    }
    /**
     * 字段权限
     * @param $id
     * @auth true
     * @login true
     * @return Form
     */
    public function field($id){
        $model = config('admin.database.auth_model');
        return Form::create(new $model, function (Form $form) use ($id) {
            $form->edit($id);
            $form->labelPosition('top');
            $fieldAuthModel = config('admin.database.field_auth_model');
            $nodes = $fieldAuthModel::where('auth_id', $id)->column('key');
            $form->tree('fields')
                ->data(Admin::node()->fields(true))
                ->showCheckbox()
                ->horizontal()
                ->value($nodes)
                ->defaultExpandAll();
            $form->saving(function ($post) use($fieldAuthModel){
                Cache::tag('eadmin_auth_field')->clear();
                $fields = Admin::node()->fields();
                $data = [];
                $fieldAuthModel::where('auth_id', $post['id'])->delete();
                foreach ($fields as $field){
                    if(in_array($field['id'],$post['fields'],true) && !empty($field['field'])){
                        $data[] = [
                            'auth_id' => $post['id'],
                            'field' => $field['field'],
                            'class' => $field['class'],
                            'key' => $field['id'],
                        ];
                    }
                }
                (new $fieldAuthModel)->saveAll($data);
            });
        });
    }
    /**
     * 数据权限
     * @auth true
     * @login true
     * @return Form
     */
    public function dataAuth($id,$type){
        return Form::create([], function (Form $form) use ($id,$type) {
            $authDataModel = config('admin.database.auth_data_model');
            $data = $authDataModel::where('auth_type',$type)
                ->where('auth_id',$id)
                ->where('data_type',1)
                ->column('data_id');
            $form->selectTable('group_data',admin_trans('auth.select_group'))
                ->from($this->index())
                ->tip(admin_trans('auth.select_group_tip'))
                ->multiple()
                ->value($data)
                ->options(function ($ids){
                    return SystemAuth::whereIn('id',$ids)->column('name','id');
                });
            $data = $authDataModel::where('auth_type',$type)
                ->where('auth_id',$id)
                ->where('data_type',2)
                ->column('data_id');
            $form->selectTable('user_data',admin_trans('auth.select_user'))
                ->from(url('admin/index'))
                ->value($data)
                ->multiple()
                ->options(function ($ids){
                    $model = config('admin.database.user_model');
                    return $model::whereIn('id',$ids)->column('nickname','id');
                })->tip(admin_trans('auth.select_user_tip'));
            $form->saving(function ($data) use($type,$authDataModel) {
                $authDataModel::where('auth_type',$type)->where('auth_id', $data['id'])->delete();
                $insertData = [];
                foreach ($data['group_data'] as $data_id) {
                    $insertData[] = [
                        'auth_type'=>$type,
                        'auth_id' => $data['id'],
                        'data_type' => 1,
                        'data_id' => $data_id,
                    ];
                }
                foreach ($data['user_data'] as $data_id) {
                    $insertData[] = [
                        'auth_type'=>$type,
                        'auth_id' => $data['id'],
                        'data_type' => 2,
                        'data_id' => $data_id,
                    ];
                }
                (new $authDataModel)->saveAll($insertData);
            });
        });
    }
    /**
     * 菜单权限
     * @auth true
     * @login true
     * @return Form
     */
    public function menu($id)
    {
        $model = config('admin.database.auth_model');
        return Form::create(new $model, function (Form $form) use ($id) {
            $form->edit($id);
            $form->labelPosition('top');
            $authMenuModel = config('admin.database.auth_menu_model');
            $menus = $authMenuModel::where('auth_id', request()->get('id'))->column('menu_id');
            $form->tree('menu_nodes')
                ->data([['name' => admin_trans('auth.all'), 'id' => 0, 'children' => Admin::menu()->tree()]])
                ->showCheckbox()
                ->value($menus)
                ->props(['children' => 'children', 'label' => 'name'])
                ->defaultExpandAll();
            $form->saving(function ($data) use($authMenuModel){
                $authMenuModel::where('auth_id', $data['id'])->delete();
                if (!empty($data['menu_nodes']) && count($data['menu_nodes']) > 0) {
                    $menuData = [];
                    foreach ($data['menu_nodes'] as $menuId) {
                        $menuData[] = [
                            'auth_id' => $data['id'],
                            'menu_id' => $menuId,
                        ];
                    }
                    (new $authMenuModel)->saveAll($menuData);
                }
            });
        });
    }

    /**
     * 功能权限
     * @auth true
     * @login true
     */
    public function authNode($id)
    {
        $model = config('admin.database.auth_model');
        return Form::create(new $model, function (Form $form) use ($id) {
            $form->edit($id);
            $form->labelPosition('top');
            $authNodeModel = config('admin.database.auth_node_model');
            $nodes = $authNodeModel::where('auth_id', $id)->column('node_id');
            $form->tree('auth_nodes')
                ->data(Admin::node()->tree())
                ->showCheckbox()
                ->horizontal()
                ->value($nodes)
                ->defaultExpandAll();
            $form->saving(function ($data) use($authNodeModel){
                $authNodeModel::where('auth_id', $data['id'])->delete();
                if (!empty($data['auth_nodes']) && count($data['auth_nodes']) > 0) {
                    $authData = [];
                    $nodes = Admin::node()->all();
                    foreach ($nodes as $node) {
                        if (in_array($node['id'], $data['auth_nodes'])) {
                            $authData[] = [
                                'auth_id' => $data['id'],
                                'node_id' => $node['id'],
                                'method' => $node['method'],
                                'class' => $node['class'],
                                'action' => $node['action'],
                            ];
                        }
                    }
                    if (count($authData) > 0) {
                        (new $authNodeModel)->saveAll($authData);
                    }
                }
            });
        });
    }
}
