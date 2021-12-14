<?php
/**
 * Created by PhpStorm.
 * User: rocky
 * Date: 2020-04-28
 * Time: 21:38
 */

namespace app\admin\controller;


use Eadmin\component\basic\Badge;
use Eadmin\component\basic\Dialog;
use Eadmin\component\basic\DropdownItem;
use Eadmin\component\basic\Html;
use Eadmin\Controller;
use Eadmin\form\Form;
use Eadmin\grid\Actions;
use Eadmin\grid\Filter;
use Eadmin\grid\Grid;




/**
 * 系统用户管理
 * Class Admin
 * @package app\admin\controller
 */
class Admin extends Controller
{

    /**
     * 系统用户列表
     * @auth true
     * @login true
     * @method get
     */
    public function index(): Grid
    {
        $model = config('admin.database.user_model');
        return Grid::create(new $model, function (Grid $grid) {
            $grid->model()->when(config('admin.admin_auth_id') != \Eadmin\Admin::id(),[['id','<>',config('admin.admin_auth_id')]]);
            $grid->title(admin_trans('admin.system_user'));
            $grid->userInfo('avatar', 'nickname', admin_trans('admin.fields.avatar'));
            $grid->column('username', admin_trans('admin.fields.username'))->display(function ($val, $data) {
                if ($data['id'] == config('admin.admin_auth_id')) {
                    return Html::create()
                        ->content($val)
                        ->content(
                            Badge::create()->value(admin_trans('admin.super_admin'))->type('primary')->attr('style', ['marginTop' => '5px'])
                        );
                } else {
                    return $val;
                }
            });
            $grid->column('phone',  admin_trans('admin.fields.phone'));
            $grid->column('mail',  admin_trans('admin.fields.mail'));
            $grid->column('status', admin_trans('admin.fields.status'))->switch();
            $grid->column('create_time', admin_trans('admin.create_time'));
            $grid->actions(function (Actions $action, $data) {
                $dropdown = $action->dropdown();
                if ($data['id'] == config('admin.admin_auth_id') || $data['id'] == \Eadmin\Admin::id()) {
                    $action->hideDel();
                }
                if ($data['id'] != config('admin.admin_auth_id')){
                    $dropdown
                        ->prepend(admin_trans('auth.data_grant'),'fa fa-database')
                        ->dialog()
                        ->width('50%')
                        ->title(admin_trans('auth.data_grant'))
                        ->form(url('auth/dataAuth',['id'=>$data['id'],'type'=>2]));
                }
                $dropdown->prepend(admin_trans('admin.reset_password'),'el-icon-key')
                    ->dialog()
                    ->title(admin_trans('admin.reset_password'))
                    ->form($this->resetPassword($data['id']));
            });
            //删掉前回调
            $grid->deling(function ($ids) {
                if (is_array($ids) && in_array(config('admin.admin_auth_id'), $ids)) {
                    $this->errorCode(999, admin_trans('admin.super_admin_delete'));
                }
            });
            //更新前回调
            $grid->updateing(function ($ids, $data) {
                if (in_array(config('admin.admin_auth_id'), $ids)) {
                    if (isset($data['status']) && $data['status'] == 0) {
                        $this->errorCode(999, admin_trans('admin.super_admin_disabled'));
                    }
                }
            });
            $grid->filter(function (Filter $filter) {
                $filter->like('username', admin_trans('admin.fields.username'));
                $filter->like('phone',  admin_trans('admin.fields.phone'));
                $filter->eq('status', admin_trans('admin.fields.status'))->select([
                    1 => admin_trans('admin.normal'),
                    0 => admin_trans('admin.disable')
                ]);
                $filter->dateRange('create_time', admin_trans('admin.create_time'));
            });
            $grid->quickSearch();
            $grid->setForm($this->form())->dialog();
            $grid->hideDeleteButton();
        });

    }

    /**
     * 修改密码
     * @auth true
     * @login true
     */
    public function updatePassword()
    {
        $model = config('admin.database.user_model');
        return Form::create(new $model, function (Form $form) {
            $form->edit(\Eadmin\Admin::id());
            $form->password('old_password', admin_trans('admin.old_password'))->required();
            $form->password('new_password', admin_trans('admin.new_password'))->rule([
                'confirm' => admin_trans('admin.password_confim_validate'),
                'min:5' => admin_trans('admin.password_min_number')
            ])->required();
            $form->password('new_password_confirm', admin_trans('admin.confim_password'))->required();
            $form->saving(function ($data) use ($form) {
                if (!password_verify($data['old_password'], $form->getData('password'))) {
                    admin_error_message(admin_trans('admin.old_password_error'));
                }
                $data['password'] = $data['new_password'];
                return $data;
            });
        });

    }

    /**
     * 重置密码
     * @auth true
     * @login true
     * @param int $id 系统用户id
     */
    public function resetPassword($id)
    {
        $model = config('admin.database.user_model');
        return Form::create(new $model, function (Form $form) use ($id) {
            $form->edit($id);
            $form->password('new_password', admin_trans('admin.new_password'))->required()->rule([
                'confirm' => admin_trans('admin.password_confim_validate'),
                'min:5' => admin_trans('admin.password_min_number')
            ]);
            $form->password('new_password_confirm', admin_trans('admin.confim_password'))->required();
            $form->saving(function ($data) {
                $data['password'] = $data['new_password'];
                return $data;
            });
        });
    }

    /**
     * 系统用户
     * @auth true
     * @login true
     */
    public function form(): Form
    {
        $model = config('admin.database.user_model');
        return Form::create(new $model, function (Form $form) {
            $userInput = $form->text('username', admin_trans('admin.fields.username'))->rule([
                'chsDash' => admin_trans('admin.username_validate'),
                'unique:' . config('admin.database.user_model') => admin_trans('admin.username_exist')
            ])->required();
            if ($form->isEdit()) {
                $userInput->disabled();
            }
            $form->text('nickname', admin_trans('admin.fields.nickname'))->rule([
                'chsAlphaNum' => admin_trans('admin.nickname_validate'),
            ])->required();
            $form->image('avatar', admin_trans('admin.fields.avatar'))->required();
            if (!$form->isEdit()) {
                $form->password('password', admin_trans('admin.fields.password'))->rule(['min:5' => admin_trans('admin.password_min_number')])->default(123456)->help('初始化密码123456,建议密码包含大小写字母、数字、符号')->required();
            }
            $form->mobile('phone', admin_trans('admin.fields.phone'))
                ->rule([
                    'unique:'  . config('admin.database.user_model') => admin_trans('admin.phone_exist')
                ]);
            $form->text('mail', admin_trans('admin.fields.mail'))->rule([
                'email' => admin_trans('admin.please_email'),
            ]);
            if ($form->getData('id') != config('admin.admin_auth_id')) {
                $authModel = config('admin.database.auth_model');
                $auths = $authModel::where('status', 1)->select()->toArray();
                $auths = \Eadmin\Admin::tree($auths);
                $form->tree('roles','访问权限')
                    ->data($auths)
                    ->showCheckbox()
                    ->checkStrictly()
                    ->defaultExpandAll()
                    ->props(['children' => 'children', 'label' => 'name']);
            }
        });
    }

    /**
     * 获取个人信息
     * @auth false
     * @login true
     */
    public function info()
    {
        \Eadmin\Admin::token()->auth();
        $data['menus'] = \Eadmin\Admin::menu()->tree(true);
        $data['info'] = \Eadmin\Admin::user();
        $config = config('admin');
        $data['webLogo'] = sysconf('system_web_logo');
        $data['webName'] = sysconf('system_web_name');
        $data['topMenu'] = $config['topMenu'];
        $data['tagMenu'] = $config['tagMenu'];
        $data['theme'] = $config['theme']['skin'];
        $data['lang'] = $config['lang'];
        $data['lang']['cookie_var'] = config('lang.cookie_var');
        $data['lang']['element'] = admin_trans('element-plus.element');
        $data['dropdownMenu'] = [
            DropdownItem::create(Dialog::create(admin_trans('admin.my_info'))->title(admin_trans('admin.my_info'))->form($this->editInfo())->appendToBody(true)),
            DropdownItem::create(Dialog::create(admin_trans('admin.update_password'))->title(admin_trans('admin.update_password'))->form($this->updatePassword())->appendToBody(true)),
        ];
        $this->successCode($data);
    }

    /**
     * 编辑个人信息
     * @auth false
     * @login true
     */
    public function editInfo()
    {
        $model = config('admin.database.user_model');
        return Form::create(new $model, function (Form $form) {
            $form->edit(\Eadmin\Admin::id());
            $form->text('username', admin_trans('admin.fields.username'))->rule([
                'chsDash' => admin_trans('admin.username_validate')
            ])->disabled();
            $form->text('nickname',  admin_trans('admin.fields.nickname'))->rule([
                'chsAlphaNum' => admin_trans('admin.nickname_validate'),
            ])->required();
            $form->image('avatar', admin_trans('admin.fields.avatar'))->default($this->request->domain() . '/static/img/headimg.png')->size(80, 80);
            $form->text('phone', admin_trans('admin.fields.phone'))
                ->rule([
                    'mobile' => admin_trans('admin.please_phone'),
                    'unique:' . config('admin.database.user_model') => admin_trans('admin.phone_exist')
                ]);
            $form->text('mail', admin_trans('admin.fields.mail'))->rule([
                'email' => admin_trans('admin.please_email'),
            ]);
        });
    }

    /**
     * 刷新token
     * @auth false
     * @login true
     */
    public function refreshToken()
    {
        $tokens = \Eadmin\Admin::token()->refresh();
        $this->successCode($tokens);
    }


}
