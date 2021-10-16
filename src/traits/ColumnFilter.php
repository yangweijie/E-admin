<?php

namespace Eadmin\traits;

use Eadmin\component\basic\Html;
use Eadmin\grid\Filter;

trait ColumnFilter
{
    protected $filterDropdown = [];

    protected function addFilterDropdown(\Closure $closure)
    {
        $filter = $this->grid->getFilter();
        call_user_func($closure, $filter, $this->attr('prop'));
        $this->filterDropdown[] = $filter->form()->getLastItem()->getContent('default');
        if (count($this->filterDropdown) > 0) {
            $prop = $this->attr('prop');
            $this->attr('eadminFilterDropdown', Html::div()->content($this->filterDropdown)->style(['padding' => '8px']));
            $this->attr('slots', array_merge($this->attr('slots'), ['filterIcon' => 'filterIcon_' . $prop, 'filterDropdown' => 'filterDropdown']));
        }

    }
    /**
     * 不等于查询
     * @param string $field 字段
     * @return $this
     */
    public function filterNeq($field = null)
    {
        $this->addFilterDropdown(function (Filter $filter, $prop) use($field) {
            $field = is_null($field)?$prop:$field;
            $filter->neq($field)->hide();
        });
        return $this;
    }
    /**
     * 大于
     * @param string $field 字段
     * @return $this
     */
    public function filterGt($field = null)
    {
        $this->addFilterDropdown(function (Filter $filter, $prop) use($field) {
            $field = is_null($field)?$prop:$field;
            $filter->gt($field)->hide();
        });
        return $this;
    }
    /**
     * 小于等于
     * @param string $field 字段
     * @return $this
     */
    public function filterElt($field = null)
    {
        $this->addFilterDropdown(function (Filter $filter, $prop) use($field) {
            $field = is_null($field)?$prop:$field;
            $filter->elt($field)->hide();
        });
        return $this;
    }
    /**
     * 小于
     * @param string $field 字段
     * @return $this
     */
    public function filterLt($field = null)
    {
        $this->addFilterDropdown(function (Filter $filter, $prop) use($field) {
            $field = is_null($field)?$prop:$field;
            $filter->lt($field)->hide();
        });
        return $this;
    }
    /**
     * 区间查询
     * @param string $field 字段
     * @return $this
     */
    public function filterBetween($field = null)
    {
        $this->addFilterDropdown(function (Filter $filter, $prop) use($field) {
            $field = is_null($field)?$prop:$field;
            $filter->between($field)->hide();
        });
        return $this;
    }
    /**
     * NOT区间查询
     * @param string $field 字段
     * @return $this
     */
    public function filterNotBetween($field = null)
    {
        $this->addFilterDropdown(function (Filter $filter, $prop) use($field) {
            $field = is_null($field)?$prop:$field;
            $filter->notBetween($field)->hide();
        });
        return $this;
    }
    /**
     * 大于等于
     * @param string $field 字段
     * @return $this
     */
    public function filterGqt($field = null)
    {
        $this->addFilterDropdown(function (Filter $filter, $prop) use($field) {
            $field = is_null($field)?$prop:$field;
            $filter->egt($field)->hide();
        });
        return $this;
    }
    /**
     * 等于查询
     * @param string $field 字段
     * @return $this
     */
    public function filter($field = null)
    {
        $this->addFilterDropdown(function (Filter $filter, $prop) use($field) {
            $field = is_null($field)?$prop:$field;
            $filter->eq($field)->hide();
        });
        return $this;
    }

    /**
     * 模糊查询
     * @param string $field 字段
     * @return $this
     */
    public function filterLike($field)
    {
        $this->addFilterDropdown(function (Filter $filter, $prop) use($field) {
            $field = is_null($field)?$prop:$field;
            $filter->like($field)->hide();
        });
        return $this;
    }

    /**
     * 模糊查询
     * @param string $field 字段
     * @param string $node json属性字段
     * @return $this
     */
    public function filterJson($field,$node)
    {
        $this->addFilterDropdown(function (Filter $filter, $prop) use($field) {
            $field = is_null($field)?$prop:$field;
            $filter->like($field, $node)->hide();
        });
        return $this;
    }
    /**
     * 模糊查询
     * @param string $field 字段
     * @param string $node json属性字段
     * @return $this
     */
    public function filterJsonLike($field,$node)
    {
        $this->addFilterDropdown(function (Filter $filter, $prop) use($field) {
            $field = is_null($field)?$prop:$field;
            $filter->jsonLike($field, $node)->hide();
        });
        return $this;
    }
    /**
     * json数组模糊查询
     * @param string $field 字段
     * @param string $node json属性字段
     * @return $this
     */
    public function filterJsonArrLike($field,$node)
    {
        $this->addFilterDropdown(function (Filter $filter, $prop) use($field) {
            $field = is_null($field)?$prop:$field;
            $filter->jsonArrLike($field, $node)->hide();
        });
        return $this;
    }
    /**
     * in查询
     * @param string $field 字段
     * @return $this
     */
    public function filterIn($field)
    {
        $this->addFilterDropdown(function (Filter $filter, $prop) use($field) {
            $field = is_null($field)?$prop:$field;
            $filter->in($field)->hide();
        });
        return $this;
    }
    /**
     * not in查询
     * @param string $field 字段
     * @return $this
     */
    public function  filterNotIn($field)
    {
        $this->addFilterDropdown(function (Filter $filter, $prop) use($field) {
            $field = is_null($field)?$prop:$field;
            $filter->in($field)->hide();
        });
        return $this;
    }
    /**
     * findIn查询
     * @param string $field 字段
     * @return $this
     */
    public function  filterFindIn($field)
    {
        $this->addFilterDropdown(function (Filter $filter, $prop) use($field) {
            $field = is_null($field)?$prop:$field;
            $filter->findIn($field)->hide();
        });
        return $this;
    }
    /**
     * 日期筛选
     * @param string $field 字段
     * @return $this
     */
    public function filterDate($field = null)
    {
        $this->addFilterDropdown(function (Filter $filter, $prop) use($field) {
            $field = is_null($field)?$prop:$field;
            $filter->date($field)->hide();
        });
        return $this;
    }
    /**
     * 时间筛选
     * @param string $field 字段
     * @return $this
     */
    public function filterTime($field = null)
    {
        $this->addFilterDropdown(function (Filter $filter, $prop) use($field) {
            $field = is_null($field)?$prop:$field;
            $filter->time($field)->hide();
        });
        return $this;
    }
    /**
     * 日期时间筛选
     * @param string $field 字段
     * @return $this
     */
    public function filterDatetime($field = null)
    {
        $this->addFilterDropdown(function (Filter $filter, $prop) use($field) {
            $field = is_null($field)?$prop:$field;
            $filter->datetime($field)->hide();
        });
        return $this;
    }
    /**
     * 日期时间范围筛选
     * @param string $field 字段
     * @return $this
     */
    public function filterDatetimeRange($field = null)
    {
        $this->addFilterDropdown(function (Filter $filter, $prop) use($field) {
            $field = is_null($field)?$prop:$field;
            $filter->datetimeRange($field)->hide();
        });
        return $this;
    }
    /**
     * 日期时间范围筛选
     * @param string $field 字段
     * @return $this
     */
    public function filterDateRange($field = null)
    {
        $this->addFilterDropdown(function (Filter $filter, $prop) use($field) {
            $field = is_null($field)?$prop:$field;
            $filter->dateRange($field)->hide();
        });
        return $this;
    }
    /**
     * 时间范围筛选
     * @param string $field 字段
     * @return $this
     */
    public function filterTimeRange($field = null)
    {
        $this->addFilterDropdown(function (Filter $filter, $prop) use($field) {
            $field = is_null($field)?$prop:$field;
            $filter->timeRange($field)->hide();
        });
        return $this;
    }
    /**
     * 年日期筛选
     * @param string $field 字段
     * @return $this
     */
    public function filterYear($field = null)
    {
        $this->addFilterDropdown(function (Filter $filter, $prop) use($field) {
            $field = is_null($field)?$prop:$field;
            $filter->year($field)->hide();
        });
        return $this;
    }
    /**
     * 月日期筛选
     * @param string $field 字段
     * @return $this
     */
    public function filterMonth($field = null)
    {
        $this->addFilterDropdown(function (Filter $filter, $prop) use($field) {
            $field = is_null($field)?$prop:$field;
            $filter->month($field)->hide();
        });
        return $this;
    }

    /**
     * 级联筛选
     * @param string $field 字段
     * @return $this
     */
    public function filterCascader(...$field)
    {
        $this->addFilterDropdown(function (Filter $filter, $prop) use($field) {
            $filter->cascader($field)->hide();
        });
        return $this;
    }
}
