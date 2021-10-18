<?php
/**
 * Created by PhpStorm.
 * User: rocky
 * Date: 2021-10-18
 * Time: 19:53
 */

namespace Eadmin\traits;

use Eadmin\form\traits\ComponentForm;
use Eadmin\component\basic\Html;
trait ColumnEditable
{
    use ComponentForm;
    protected $editable = null;

    public function editable($type='text')
    {
        $this->editable = $type;
        return $this;
    }
    protected function editableCall($value,$data){
        $params = $this->grid->getCallMethod();
        $id = $this->grid->drive()->getPk();
        $params['eadmin_ids'] = [$data[$id]];
        $params['field'] = $this->prop;
        $params['editable'] = true;
        $component = self::$component[$this->editable];
        $component = $component::create(null,$data[$this->prop])->changeAjax('/eadmin/batch.rest', $params, 'put');
        $field = 'eadmin_editable'.$data[$id];
        $component->bind($field,0);
        $component->where($field,1)->directive('focus',$field)->attr('ref',$field);
        $component->event('blur',[$field=>0]);
        $html = Html::div()->content($value)->where($field,0)->event('click',[$field=>1]);
        return [$html,$component];
    }
}