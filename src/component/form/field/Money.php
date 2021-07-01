<?php
/**
 * Created by PhpStorm.
 * User: rocky
 * Date: 2021-02-09
 * Time: 10:48
 */

namespace Eadmin\component\form\field;


use Eadmin\component\basic\Html;
use Eadmin\component\form\FormItem;

class Money extends Input
{
    public function setFormItem(FormItem $formItem)
    {
        parent::setFormItem($formItem); // TODO: Change the autogenerated stub
        $this->type('number');
        $this->prefix(
            Html::create('￥')
                ->tag('div')
                ->style(['width' => '25px', 'textAlign' => 'center'])
        );
    }
}
