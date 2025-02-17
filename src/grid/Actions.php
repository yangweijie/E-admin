<?php
/**
 * Created by PhpStorm.
 * User: rocky
 * Date: 2020-04-18
 * Time: 00:18
 */

namespace Eadmin\grid;

use Eadmin\Admin;
use Eadmin\component\basic\Button;
use Eadmin\component\basic\Confirm;
use Eadmin\component\basic\Dropdown;
use Eadmin\component\basic\DropdownItem;
use Eadmin\component\basic\Html;
use Eadmin\component\basic\Space;
use Eadmin\component\Component;
use Eadmin\component\grid\Column;

/**
 * Class Actions
 * @package Eadmin\grid
 * @property Grid $grid
 */
class Actions extends Html
{

    //隐藏详情按钮
    protected $hideDetailButton = false;
    //隐藏编辑按钮
    protected $hideEditButton = false;
    //隐藏删除按钮
    protected $hideDelButton = false;
    protected $closure = null;
    protected $prependArr = [];
    protected $appendArr = [];
    protected $row = [];
    protected $column;
    protected $id;
    protected $grid;
    //是否下拉菜单
    protected $isDropdown = false;
    protected $dropdown;
    protected $editText = '';
    protected $detailText = '';
    protected $delText = '';
    protected $space;

    public function __construct($grid)
    {
        parent::__construct();
        $this->grid = $grid;
        $this->attr('class', request()->get('eadmin_grid',$this->grid->bindAttr('modelValue')).'EadminAction');
        $this->column = new Column('EadminAction', '', $grid);
        $this->editText = admin_trans('admin.edit');
        $this->delText = admin_trans('admin.delete');
        $this->detailText =  admin_trans('admin.detail');
    }

    public function row($data)
    {
        $this->space = Space::create()->wrap()->size(10);
        $this->row = $data;
        //如果有id设置id标示
       $pk = $this->grid->drive()->getPk();
        if (isset($data[$pk])) {
            $this->id = $data[$pk];
        }
        //自定义内容显示处理
        if (!is_null($this->closure)) {
            call_user_func_array($this->closure, [$this, $data]);
        }
        //前面追加
        foreach ($this->prependArr as $content) {
            $this->space->content($content);
        }
        if (!is_null($this->grid->formAction())) {
            $this->editText = $this->grid->formAction()->editText();
            $this->delText = $this->grid->formAction()->delText();
        }
        if (!is_null($this->grid->detailAction())) {
            $this->detailText = $this->grid->detailAction()->detailText();
        }
        if ($this->isDropdown) {
            $this->actionDropdown();
        } else {
            $this->actionButton();
        }
        //追加尾部
        foreach ($this->appendArr as $content) {
            $this->space->content($content);
        }
    }

    protected function actionDropdown()
    {

        //是否隐藏详情
        if (!$this->hideDetailButton && !is_null($this->grid->detailAction())) {
            $text = '<i class="el-icon-info" /> ' . $this->detailText;
            $detail = $this->grid->detailAction()->detail();
            $callMethod = $detail->getCallMethod();
            if (Admin::check($callMethod['eadmin_class'], $callMethod['eadmin_function'])) {
                $action = clone $this->grid->detailAction()->component();
                if ($action instanceof Html) {
                    $button = $action->content($text)->redirect("eadmin/{$this->id}.rest", $detail->getCallMethod());
                    $this->dropdown->item($button);
                } else {
                    $button = $action->title($this->detailText)->bindValue(false)->url("/eadmin/{$this->id}.rest")->params(['eadmin_layout' => true] + $detail->getCallMethod());
                    $button->bindValue(false, 'show');
                    $visible = $button->bindAttr('show');
                    $this->dropdown->content($button, 'reference');
                    $item = $this->dropdown->item($text);
                    $item->event('click', [$visible => true]);
                }
            }
        }

        //是否隐藏编辑
        if (!$this->hideEditButton && !is_null($this->grid->formAction())) {
            $text = '<i class="el-icon-edit" /> ' . $this->editText;
            $form = $this->grid->formAction()->form()->renderable();
            $callMethod = $form->getCallMethod();
            if (Admin::check($callMethod['eadmin_class'], $callMethod['eadmin_function'], 'put')) {
                $action = clone $this->grid->formAction()->component();

                if ($action instanceof Html) {
                    $button = $action->content($text)->redirect("eadmin/{$this->id}/edit.rest", ['eadmin_description' => $this->editText] + $callMethod);
                    $this->dropdown->item($button);
                } else {

                    $button = $action->bindValue(false)->title($this->editText.$this->grid->formAction()->form()->bind('eadmin_title'))->url("/eadmin/{$this->id}/edit.rest")->params($callMethod);
                    $button->bindValue(false, 'show');
                    $visible = $button->bindAttr('show');
                    $this->dropdown->content($button, 'reference');
                    $item = $this->dropdown->item($text);
                    $item->event('click', [$visible => true]);
                }
            }
        }
        $params = $this->grid->getCallMethod();
        //是否隐藏删除
        if (!$this->hideDelButton && Admin::check($params['eadmin_class'], $params['eadmin_function'], 'delete')) {
            $text = '<i class="el-icon-delete" /> ' . $this->delText;

            if (request()->has('eadmin_deleted') && !$this->grid->attr('hideTrashedRestore')) {
                $text = '<i class="el-icon-help" /> ' . admin_trans('admin.recover_data');
                $url = "/eadmin/batch.rest";
                $confirm = Confirm::create($text)->message(admin_trans('admin.confim_recover'))
                    ->url($url)
                    ->type('warning')
                    ->params($params + ['delete_time' => 0, 'eadmin_ids' => [$this->id]])
                    ->method('put');
                $this->dropdown->item($confirm);
                $params['trueDelete'] = true;
                $text = '<i class="el-icon-delete" /> ' . admin_trans('admin.true_delete');
            }
            if (!$this->grid->attr('hideTrashedDelete') || !request()->has('eadmin_deleted')) {
                $url = '/eadmin/' . $this->id . '.rest';
                $confirm = Confirm::create($text)->message(admin_trans('admin.confim_delete'))
                    ->url($url)
                    ->type('error')
                    ->params($params)
                    ->method('DELETE');
                $this->dropdown->item($confirm);
            }

        }
        $this->content($this->dropdown);
    }

    protected function actionButton()
    {
        //是否隐藏详情
        if (!$this->hideDetailButton && !is_null($this->grid->detailAction())) {
            $button = Button::create($this->detailText)
                ->size('small')
                ->icon('el-icon-info');
            $detail = $this->grid->detailAction()->detail();
            $callMethod = $detail->getCallMethod();
            if (Admin::check($callMethod['eadmin_class'], $callMethod['eadmin_function'])) {
                $action = clone $this->grid->detailAction()->component();
                if ($action instanceof Html) {
                    $button = $action->content($button)->redirect("eadmin/{$this->id}.rest", $callMethod);
                } else {
                    $button = $action->bindValue(false)->title($this->detailText)->reference($button)->url("/eadmin/{$this->id}.rest")->params(['eadmin_layout' => true] + $callMethod);
                }
                $this->space->content($button);

            }
        }
        //是否隐藏编辑
        if (!$this->hideEditButton && !is_null($this->grid->formAction())) {
            $button = Button::create($this->editText)
                ->type('primary')
                ->size('small')
                ->icon('el-icon-edit');
            $form = $this->grid->formAction()->form();
            $callMethod = $form->getCallMethod();
            if (Admin::check($callMethod['eadmin_class'], $callMethod['eadmin_function'], 'put')) {
                $action = clone $this->grid->formAction()->component();

                if ($action instanceof Html) {
                    $button = $action->content($button)->redirect("eadmin/{$this->id}/edit.rest", ['eadmin_description' => $this->editText] + $callMethod);
                } else {

                    $button = $action->bindValue(false)->title($this->editText.$this->grid->formAction()->form()->bind('eadmin_title'))->reference($button)->url("/eadmin/{$this->id}/edit.rest")->params($callMethod);
                }

                $this->space->content($button);
            }

        }
        $params = $this->grid->getCallMethod();
        //是否隐藏删除
        if (!$this->hideDelButton && Admin::check($params['eadmin_class'], $params['eadmin_function'], 'delete')) {
            $text = $this->delText;
            if (request()->has('eadmin_deleted') && !$this->grid->attr('hideTrashedRestore')) {
                $this->space->content(
                    Button::create(admin_trans('admin.recover_data'))
                        ->size('small')
                        ->icon('el-icon-s-help')
                        ->save($params + ['delete_time' => 0, 'eadmin_ids' => [$this->id]], "/eadmin/batch.rest", admin_trans('admin.confim_recover'))->method('put')
                );
                $params['trueDelete'] = true;
                $text = admin_trans('admin.true_delete');
            }
            if (!$this->grid->attr('hideTrashedDelete') || !request()->has('eadmin_deleted')) {
                $url = '/eadmin/' . $this->id . '.rest';
                $button = Button::create($text)
                    ->type('danger')
                    ->size('small')
                    ->icon('el-icon-delete')
                    ->confirm(admin_trans('admin.confim_delete'), $url)
                    ->type('error')
                    ->params($params)
                    ->method('DELETE');
                $this->space->content($button);
            }
        }
    }

    /**
     * @return Dropdown
     */
    public function dropdown($text = null)
    {
        if (is_null($text)) {
            $text = admin_trans('admin.action');
        }
        $this->isDropdown = true;
        $this->dropdown = Dropdown::create(Button::create($text . ' <i class="el-icon-arrow-down" />')->size('mini'));
        return $this->dropdown;
    }

    public function column()
    {
        return $this->column;
    }

    public function __get($name)
    {
        return $this->row[$name];
    }

    public function setClosure(\Closure $closure)
    {
        $this->closure = $closure;
    }

    //隐藏详情按钮
    public function hideDetail()
    {
        $this->hideDetailButton = true;
    }

    //隐藏编辑按钮
    public function hideEdit()
    {
        $this->hideEditButton = true;
    }

    //隐藏删除按钮
    public function hideDel()
    {
        $this->hideDelButton = true;
    }

    /**
     * 前面追加
     * @param mixed $val
     */
    public function prepend($val)
    {
        $this->prependArr[] = $val;
        return $this;
    }

    /**
     * 追加尾部
     * @param mixed $val
     */
    public function append($val)
    {
        $this->appendArr[] = $val;
        return $this;
    }

    public function jsonSerialize()
    {
        if (count($this->space->content) > 0) {
            $this->content($this->space);
        }
        return parent::jsonSerialize(); // TODO: Change the autogenerated stub
    }
}
