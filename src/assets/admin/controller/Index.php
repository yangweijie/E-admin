<?php


namespace app\admin\controller;

use app\admin\example\BarCard;
use app\admin\example\LineCard;
use app\admin\example\PieCard;
use app\admin\example\ProgressCard;
use Eadmin\chart\Echart;


use Eadmin\chart\echart\LineChart;
use Eadmin\component\basic\Badge;
use Eadmin\component\basic\Card;
use Eadmin\component\basic\Html;
use Eadmin\component\basic\QuickLink;
use Eadmin\component\basic\Statistic;
use Eadmin\component\basic\Tabs;
use Eadmin\component\form\field\Select;
use Eadmin\component\layout\Content;
use Eadmin\component\layout\Row;
use Eadmin\constant\Style;
use Eadmin\Controller;
use Eadmin\grid\Filter;
use Eadmin\grid\Grid;
use mapleAccount\model\Account;
use mapleGoods\common\Enum;
use mapleGoods\model\Good;
use mapleOrder\model\Order;
use think\db\Query;
use think\facade\Db;


/**
 * 控制台
 * Class Index
 * @package app\admin\controller
 */
class Index extends Controller
{
    /**
     * 仪表盘
     * @auth true
     * @login true
     * @method get
     */
    public function dashboard(Content $content)
    {
        $content->title('概览');

        $content->row(function (Row $row) {
            $row->gutter(10);
            $money = 1000;
            $row->column(Statistic::card('销售额', $money, 'el-icon-collection', '#884add'), 6);
            $count = 2345;
            $row->column(Statistic::card('用户', $count, 'el-icon-user-solid'), 6);
            $count = 5588;
            $row->column(Statistic::card('商品', $count, 'el-icon-goods', '#fbb016'), 6);
            $count = 12545;
            $row->column(Statistic::card('订单', $count, 'el-icon-s-order', '#34c17c'), 6);

        });

        $content->row(function (Row $row) {
            $row->gutter(10);
            $row->column(
                QuickLink::create('用户管理','el-icon-user-solid')
                    ->redirect('/mapleGoods')
                ,3);
            $row->column(
                QuickLink::create('商品管理','fa fa-shopping-cart','rgb(255, 156, 110)')
                    ->redirect('/mapleGoods')
                ,3);
            $row->column(
                QuickLink::create('订单管理','el-icon-s-order','rgb(179, 127, 235)')
                    ->redirect('/mapleOrder')
                ,3);
            $row->column(
                QuickLink::create('售后退款','fa fa-balance-scale','rgb(255, 214, 102)')
                    ->redirect('/mapleOrder/mapleRefund')
                ,3);
            $row->column(
                QuickLink::create('文章管理','fa fa-file-text-o','rgb(92, 219, 211)')
                    ->redirect('/m3lonyArticle')
                ,3);
            $row->column(
                QuickLink::create('优惠券','fa fa-money','rgb(255, 192, 105)')
                    ->redirect('/rockysCoupon')
                ,3);
            $row->column(
                QuickLink::create('短信记录','el-icon-message','rgb(255, 133, 192)')
                    ->redirect('/admin/oplog/sms')
                ,3);
            $row->column(
                QuickLink::create('系统配置','el-icon-s-tools','rgb(149, 222, 100)')
                    ->redirect('/admin/system/config')
                ,3);

        });


        $content->row(function (Row $row) {
            $row->gutter(10);
            $row->column($this->lineEchart('line'), 18);
            $row->column($this->rank(), 6);
        });


        $content->row(function (Row $row) {
            $row->gutter(10);
            $row->column($this->userEchart(), 12);
            $row->column($this->pieUserEchart(), 12);

        });


        return $content;
    }


    //商品销量排行
    public function rank()
    {
        $model = config('admin.database.user_model');
        $grid = new Grid(new $model);

        $grid->column('id', '销量排名')->display(function ($val) {
            static $i=0;
            $i++;
            $badge = Badge::create()->value($i);
            if ($i == 1) {
                $badge->type('danger');
            } elseif ($i == 2) {
                $badge->type('warning');
            } elseif ($i == 3) {
                $badge->type('success');
            } elseif ($i == 4) {
                $badge->type('primary');
            } else {
                $badge->type('info');
            }
            return $badge;
        })->width(100);
        $grid->column('title', '商品')->tip();
        $grid->column('sale_num', '销量')->align('center');
        $grid->tableMode();
        return $grid;
    }





    /**
     * 用户饼图
     */
    public function pieUserEchart()
    {
        $echart = new Echart('购买用户统计', 'pie');
        //不分组直接调用
        $echart->table(config('admin.database.user_table'), 'create_time');
        $echart->count('未消费用户',function (Query $query){

        },function ($result){
            if(env('APP_DEBUG')){
                return rand(1,10000);
            }else{
                return $result;
            }
        });
        $echart->count('消费用户',function (Query $query){

        },function ($result){
            if(env('APP_DEBUG')){
                return rand(1,10000);
            }else{
                return $result;
            }
        });

        return $echart;
    }

    /**
     * 折线图
     */
    public function lineEchart($type)
    {
        $echart = new Echart('订单', $type,'297px');
        $contentChart = new Content();
        $echart->header($contentChart);
        $echart->table(config('admin.database.user_table'));
        $echart->sum('订单金额', 'id',null,function ($result){
            if(env('APP_DEBUG')){
                return rand(1,10000);
            }else{
                return $result;
            }
        });
        $echart->count('订单数',null,function ($result){
            if(env('APP_DEBUG')){
                return rand(1,10000);
            }else{
                return $result;
            }
        });
        return $echart;

    }

    /**
     * 新增用户
     */
    public function userEchart()
    {
        $echart = new Echart('新增用户', 'bar');
        $echart->table(config('admin.database.user_table'));
        $echart->count('人数',null,function ($result){
            if(env('APP_DEBUG')){
                return rand(1,10000);
            }else{
                return $result;
            }
        });
        return $echart;
    }
}
