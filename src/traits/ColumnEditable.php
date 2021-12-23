<?php
/**
 * Created by PhpStorm.
 * User: rocky
 * Date: 2021-10-18
 * Time: 19:53
 */

namespace Eadmin\traits;

use Eadmin\form\Form;
use Eadmin\form\traits\ComponentForm;
use Eadmin\component\basic\Html;
use Eadmin\grid\event\Updated;
use Eadmin\grid\event\Updateing;
use think\facade\Event;
use think\helper\Arr;
use think\helper\Str;

/**
 * 行内编辑
 */
trait ColumnEditable
{
    use ComponentForm;

    protected $editable = null;

    /**
     * 文本编辑一直显示
     * @return \Eadmin\component\grid\Column
     * @throws \Exception
     */
    public function editableText()
    {
        return $this->editable('text', [], true);
    }

    /**
     * @param string $type text输入框 textarea文本域 select下拉框 datetime日期时间 date日期 year年 month月 slider滑块 color颜色选择器 rate评分 checkbox多选框 radio单选框
     * @param array $options 选项数据 select|chexkbox|radio
     * @param bool $show 总是显示
     * @param callable $callback 回调函数
     * @return $this
     * @throws \Exception
     */
    public function editable($type = 'text', $options = [], $show = false,callable $callback = null)
    {
        $this->editable = [
            'type' => $type,
            'options' => $options,
            'show' => $show,
            'callback' => $callback,
        ];
        $prop = $this->prop;
        $this->grid->updateing(function ($ids, $data) use ($show, $prop) {
            $event = [$ids, $data];
            $id = array_pop($ids);
            $pk = $this->grid->drive()->getPk();
            $field = 'eadmin_editable' . str_replace('.', '_', $this->prop) . $id;
            if (isset($data['eadmin_editable']) && $field == $data['eadmin_editable_bind']) {
                $form = new Form($this->grid->drive()->model());
                $data[$pk] = $id;

                $result = $form->getDrive()->save($data);
                if ($result !== false) {
                    Event::until(Updated::class, $event);
                    $this->grid->model()->where($this->grid->drive()->getPk(), $id);
                    $this->grid->exec();
                    $data = $this->grid->parseData();
                    $row = array_pop($data);
                    $row['always_show'] = $show;

                    admin_success('操作完成', '数据保存成功')->data($row);
                } else {
                    admin_error_message('数据保存失败');
                }
            }
        });
        return $this;
    }

    protected function editableCall($value, $data)
    {
        if(is_callable($this->editable['callback'])){
            $result = call_user_func_array($this->editable['callback'],[$value, $data]);
            if($result !== true){
                if($result === false){
                    return $value;
                }
                return $result;
            }
        }
        $params = $this->grid->getCallMethod();
        $id = $this->grid->drive()->getPk();
        $field = 'eadmin_editable' . str_replace('.', '_', $this->prop) . $data[$id];
        $params['eadmin_ids'] = [$data[$id]];
        $params['eadmin_editable_bind'] = $field;
        $params['eadmin_field'] = $this->prop;
        $params['eadmin_editable'] = true;
        Arr::set($params, $this->prop, Arr::get($data, $this->prop));
        $type = $this->editable['type'];
        $component = self::$component[$type];
        $component = $component::create(null, Arr::get($data, $this->prop))
            ->size('small')
            ->type($type)->changeAjax('/eadmin/batch.rest', $params, 'put');
        $component->bind($field, (int)$this->editable['show']);
        if ($type == 'select') {
            $component->popperAppendToBody(true);
        } elseif ($type == 'checkbox' || $type == 'radio') {
            $component->horizontal()
                ->onCheckAll();
        }
        if (!empty($this->editable['options'])) {
            $component->options($this->editable['options']);
        }
        $component->where($field, 1)->attr('ref', $field);
        if (!$this->editable['show']) {
            $component->directive('focus', $field);
            if ($type != 'select') {
                $component->event('blur', [$field => 0]);
            }
        }
        $html = Html::div()->content([
            Html::create($value),
            Html::create()->tag('i')->attr('class', ['el-icon-edit', 'editable-cell-icon'])->event('click', [$field => 1])
        ])->attr('class', 'eadmin-editable-cell')->where($field, 0);
        return [$html, $component];
    }
}
