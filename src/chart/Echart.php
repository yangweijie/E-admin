<?php
/**
 * Created by PhpStorm.
 * User: rocky
 * Date: 2020-06-23
 * Time: 20:07
 */

namespace Eadmin\chart;


use Carbon\Carbon;
use Eadmin\chart\echart\MapChart;
use Eadmin\component\basic\Button;
use Eadmin\component\Component;
use Eadmin\grid\Filter;
use Eadmin\traits\CallProvide;
use think\db\Query;
use think\facade\Db;
use think\facade\Event;
use think\facade\Request;
use think\helper\Str;
use think\Model;
use Eadmin\chart\echart\FunnelChart;
use Eadmin\chart\echart\LineChart;
use Eadmin\chart\echart\PieChart;
use Eadmin\chart\echart\RadarChart;

/**
 * Class Echarts
 * @package
 * @method $this count($text, \Closure $query = null, \Closure $after = null) 统计数量
 * @method $this max($text, $filed, \Closure $query = null, \Closure $after = null) 统计最大值
 * @method $this avg($text, $field, \Closure $query = null, \Closure $after = null) 统计平均值
 * @method $this sum($text, $field, \Closure $query = null, \Closure $after = null) 统计总和
 * @method $this min($text, $field, \Closure $query = null, \Closure $after = null) 统计最小值
 */
class Echart extends Component
{
    use CallProvide;

    protected $name = 'EadminEchartCard';
    protected $db;
    protected $chart;
    protected $dateField;
    protected $filter = null;
    protected $chartType = 'line';
    protected $seriesData = [];
    protected $title = '';
    protected $radarData = [];
    protected $groupSeries = [];
    protected $radarMaxKey = -1;
    protected $date_type = null;
    protected $pkField = '';
    protected $groupMode = false;
    protected $xAxis = [];
    /**
     * Echart constructor.
     * @param string $title 标题
     * @param string $type 图表类型 line折线，bar柱状 pie饼图 radar雷达图 funnel
     * @param string $height 图表高度
     */
    public function __construct($title, $type = 'line', $height = "350px")
    {
        $this->title = $title;
        $this->parseCallMethod();
        $this->attr('params', $this->getCallMethod());
        $this->attr('title', $title);
        $this->chartType = $type;
        $this->date_type = Request::get('date_type', 'today');
        if ($this->chartType == 'line' || $this->chartType == 'bar') {
            $this->chart = new LineChart($height, "100%", $this->chartType);
        } elseif ($this->chartType == 'pie') {
            $this->chart = new PieChart($height, '100%');
        } elseif ($this->chartType == 'funnel') {
            $this->chart = new FunnelChart($height, '100%');
        } elseif ($this->chartType == 'radar') {
            $this->chart = new RadarChart($height, '100%');
        } elseif ($this->chartType == 'map') {
            $this->chart = new MapChart($height, '100%');
        }
    }

    /**
     * 当前图表
     * @return EchartAbstract
     */
    public function chart()
    {
        return $this->chart;
    }

    /**
     * 隐藏工具栏
     */
    public function hideTools()
    {
        $this->attr('hideTools', true);
    }

    /**
     * 设置工具栏默认筛选
     * @param string|array $type yesterday-昨天 today-今天 week-本周 month-本月 year-今年 ['2020-02-02','2020-02-02']-范围
     * @param string $start_date range范围 开始日期
     * @param string $end_date range范围 开始日期
     * $this->toolDefault(['2020-02-02','2020-02-02']) 范围
     */
    public function toolDefault($type)
    {
        if (is_array($type)) {
            $this->attr('rangeDate', $type);
            $this->attr('start_date',$type[0]);
            $this->attr('end_date',$type[1]);
            $type = 'range';
        }
        if (!Request::has('date_type')) {
            $this->date_type = $type;
        }
        $params = $this->attr('params');
        $this->attr('params', $params + ['date_type' => $type]);
    }

    /**
     * 头部内容
     * @param $content
     */
    public function header($content)
    {
        $this->attr('header', $content);
    }

    /**
     * 底部内容
     * @param $content
     */
    public function footer($content)
    {
        $this->attr('footer', $content);
    }

    /**
     * 查询过滤
     * @param mixed $callback
     */
    public function filter($callback)
    {
        if ($callback instanceof \Closure) {
            $field = Str::random(15, 3);
            $this->bind($field, false);
            $this->bindAttr('modelValue', $field, true);
            $this->filter = new Filter($this->db);
            call_user_func($callback, $this->filter);
            $form = $this->filter->render();
            $form->eventSuccess([$field => true]);
            $this->attr('filterField', $form->bindAttr('model'));
            $this->attr('filter', $form);
        }
    }

    /**
     * 设置表名数据源
     * @param mixed $table 模型或表名
     * @param string $dateField 日期字段
     * @param string $pkField 主键字段
     */
    public function table($table, $dateField = 'create_time', $pkField = '')
    {
        $this->template = 'echart';
        if ($table instanceof Model) {
            $this->db = $table->db();
        } elseif ($table instanceof Query) {
            $this->db = $table;
        } else {
            $this->db = Db::name($table);
        }
        $this->dateField = $dateField;
        $this->pkField = $pkField ?: $this->db->getPk();
    }

    public function __call($name, $arguments)
    {
        if ($name == 'count') {
            $text = array_shift($arguments);
            $query = array_shift($arguments);
            $after = array_shift($arguments);
            if ($this->chartType == 'line' || $this->chartType == 'bar') {
                if ($this->groupMode) {
                    $this->lineAnalyzeGroup($name, $this->pkField, $text, $query, $after);
                } else {
                    $this->lineAnalyze($name, $this->pkField, $text, $query, $after);
                }

            } elseif ($this->chartType == 'pie' || $this->chartType == 'funnel' || $this->chartType == 'map') {
                $this->pieAnalyze($name, $this->pkField, $text, $query, $after);
            } elseif ($this->chartType == 'radar') {
                $max = array_shift($arguments);
                if ($max instanceof \Closure) {
                    $max = 100;
                }
                $this->radarAnalyze($name, $this->pkField, $text, $max, $query, $after);
            }

        } else {
            $text = array_shift($arguments);
            $field = array_shift($arguments);
            $query = array_shift($arguments);
            $after = array_shift($arguments);
            if ($this->chartType == 'line' || $this->chartType == 'bar') {
                if ($this->groupMode) {
                    $this->lineAnalyzeGroup($name, $field, $text, $query, $after);
                } else {
                    $this->lineAnalyze($name, $field, $text, $query, $after);
                }
            } elseif ($this->chartType == 'pie' || $this->chartType == 'funnel') {
                $this->pieAnalyze($name, $field, $text, $query, $after);
            } elseif ($this->chartType == 'radar') {
                if (isset($arguments[2])) {
                    $max = $arguments[2];
                } else {
                    $max = 100;
                }
                $this->radarAnalyze($name, $field, $text, $max, $query, $after);
            }
        }
        return $this;
    }

    /**
     * 线形柱状图按数据分组
     */
    public function groupMode()
    {
        $this->groupMode = true;
    }

    /**
     * 分组
     * @param string $name 组名
     * @param \Closure $closure
     */
    public function group($name, \Closure $closure)
    {
        $this->groupMode = true;
        call_user_func($closure, $this);
        $data = array_values($this->seriesData);
        if ($this->chart instanceof RadarChart) {
            $this->groupSeries[] = [
                'name' => $name,
                'value' => $data,
            ];
        }elseif ($this->chart instanceof LineChart) {

            $this->groupSeries[] = [
                'name' => $name,
                'value' => $this->seriesData,
            ];
        } else {
            $this->chart->series($name, $data);
        }
        $this->seriesData = [];
    }

    protected function radarAnalyze($type, $field, $name, $max = 100, $query = null, $after = null)
    {
        $value = $this->parse($type, $field, $query, $after);

        $this->chart->indicator($name, $max);
        $key = $this->chart()->getIndicatorKey($name);
        if ($this->radarMaxKey < $key) {
            $this->radarMaxKey = $key;
        }
        $this->seriesData[$key] = $value;
    }

    protected function pieAnalyze($type, $field, $name, $query = null, $after = null)
    {
        $value = $this->parse($type, $field, $query, $after);
        $this->seriesData[] = [
            'name' => $name,
            'value' => $value
        ];
    }

    protected function lineAnalyzeGroup($type, $field, $name, $query = null, $after = null)
    {
        $value = $this->parse($type, $field, $query, $after);
        $this->xAxis[] = $name;
        $this->xAxis = array_unique($this->xAxis);
        $this->chart->xAxis($this->xAxis);

        $this->seriesData[$name] = $value;
    }


    protected function lineAnalyze($type, $field, $name, $query = null, $after = null)
    {
        $series = [];
        $xAxis = [];
        switch ($this->date_type) {
            case 'yesterday':
            case 'today':
                if ($this->date_type == 'yesterday') {
                    $date = date('Y-m-d', strtotime(' -1 day'));
                } else {
                    $date = date('Y-m-d');
                }
                for ($i = 0; $i < 24; $i++) {
                    $j = $i + 1;
                    $hour = $i < 10 ? '0' . $i : $i;
                    $xAxis[] = "{$i}".admin_trans('admin.dian').admin_trans('admin.to').$j.admin_trans('admin.dian');
                    $db = clone $this->db;
                    if ($query instanceof \Closure) {
                        call_user_func($query, $db);
                    }
                    $value = $db->whereBetween($this->dateField, ["{$date} {$hour}:00:00", "{$date} {$hour}:59:59"])->$type($field);
                    if ($after instanceof \Closure) {
                        $value = call_user_func($after, $value);
                    }
                    $series[] = $value;

                }
                break;
            case 'week':
                $start_week = Carbon::now()->startOfWeek()->addDays(-1)->toDateString();
                for ($i = 0; $i <= 6; $i++) {
                    $week = Carbon::make($start_week)->addDays($i)->toDateString();
                    $xAxis[] = $week;
                    $db = clone $this->db;
                    if ($query instanceof \Closure) {
                        call_user_func($query, $db);
                    }
                    $value = $db->whereDay($this->dateField, $week)->$type($field);
                    if ($after instanceof \Closure) {
                        $value = call_user_func($after, $value);
                    }
                    $series[] = $value;
                }
                break;
            case 'month':
                $now = Carbon::today();
                $months = Carbon::parse($now->firstOfMonth()->toDateString())->daysUntil($now->endOfMonth()->toDateString())->toArray();
                foreach ($months as $month) {
                    $xAxis[] = $month->toDateString();
                    $db = clone $this->db;
                    if ($query instanceof \Closure) {
                        call_user_func($query, $db);
                    }
                    $value = $db->whereDay($this->dateField, $month)->$type($field);
                    if ($after instanceof \Closure) {
                        $value = call_user_func($after, $value);
                    }
                    $series[] = $value;
                }
                break;
            case 'year':
                for ($i = 1; $i <= 12; $i++) {
                    $xAxis[] = $i . admin_trans('admin.month');
                    $db = clone $this->db;
                    if ($query instanceof \Closure) {
                        call_user_func($query, $db);
                    }
                    $value = $db->whereMonth($this->dateField, date("Y-{$i}"))->$type($field);
                    if ($after instanceof \Closure) {
                        $value = call_user_func($after, $value);
                    }
                    $series[] = $value;
                }
                break;
            case 'range':
                $start_date = Request::get('start_date',$this->attr('start_date'));
                $end_date = Request::get('end_date',$this->attr('end_date'));
                $dates = Carbon::parse($start_date)->daysUntil($end_date)->toArray();
                foreach ($dates as $date) {
                    $xAxis[] = $date->toDateString();
                    $db = clone $this->db;
                    if ($query instanceof \Closure) {
                        call_user_func($query, $db);
                    }
                    $value = $db->whereDay($this->dateField, $date)->$type($field);
                    if ($after instanceof \Closure) {
                        $value = call_user_func($after, $value);
                    }
                    $series[] = $value;
                }
                break;
        }
        $total = array_sum($series);
        // $this->chart->xAxis($xAxis)->series($name . " ($total)", $series);
        $this->chart->xAxis($xAxis)->series($name, $series);
    }

    public function jsonSerialize()
    {

        if ($this->chart instanceof RadarChart) {
            $seriesData[] = [
                'name' => $this->title,
                'value' => $this->seriesData
            ];
            $this->seriesData = $seriesData;
            if (count($this->groupSeries) > 0) {
                $groupSeries = $this->groupSeries;
                $series = [];
                foreach ($groupSeries as $key => $groupSerie) {
                    $series[$key]['name'] = $groupSerie['name'];
                    for ($i = 0; $i <= $this->radarMaxKey; $i++) {
                        if (isset($groupSerie['value'][$i])) {
                            $series[$key]['value'][$i] = $groupSerie['value'][$i];
                        } else {
                            $series[$key]['value'][$i] = 0;
                        }
                    }
                }
                $this->seriesData = $series;
            }
        }elseif ($this->chart instanceof LineChart){
            $values = array_column($this->groupSeries,'value');
            $this->chart->xAxis(array_column($this->groupSeries,'name'));
            foreach ($this->xAxis as $xAxi){
                $this->chart->series($xAxi, array_column($values,$xAxi));
            }
        }

        if (count($this->seriesData) > 0) {
            $this->chart->series($this->title, $this->seriesData);
        }

        if (Request::has('ajax')) {
            return ['header' => $this->attr('header'), 'footer' => $this->attr('footer'), 'content' => $this->chart];
        }

        $this->attr('echart', $this->chart);
        return parent::jsonSerialize();
    }

    /**
     * @param string $type
     * @param string $field
     * @param mixed $query
     * @return mixed
     */
    protected function parse($type, $field, $query, $after)
    {
        $db = clone $this->db;
        if ($query instanceof \Closure) {
            call_user_func($query, $db);
        }
        switch ($this->date_type) {
            case 'yesterday':
            case 'today':
                $value = $db->whereDay($this->dateField, $this->date_type)->$type($field);
                break;
            case 'week':
                $value = $db->whereWeek($this->dateField)->$type($field);
                break;
            case 'month':
                $value = $db->whereMonth($this->dateField)->$type($field);
                break;
            case 'year':
                $value = $db->whereYear($this->dateField)->$type($field);
                break;
            case 'range':
                $start_date = Request::get('start_date');
                $end_date = Request::get('end_date');
                $value = $db->whereBetweenTime($this->dateField, $start_date, $end_date)->$type($field);
                break;
            default:
                $value = $db->$type($field);
        }
        if ($after instanceof \Closure) {
            $value = call_user_func($after, $value);
        }
        return $value;
    }
}
