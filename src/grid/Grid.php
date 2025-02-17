<?php


namespace Eadmin\grid;


use app\common\facade\Token;
use Eadmin\Admin;
use Eadmin\component\basic\Button;
use Eadmin\component\basic\Html;
use Eadmin\component\basic\Image;
use Eadmin\component\basic\Router;
use Eadmin\component\Component;
use Eadmin\component\grid\Column;
use Eadmin\component\grid\Custom;
use Eadmin\component\grid\Pagination;
use Eadmin\component\layout\Content;
use Eadmin\contract\GridInterface;
use Eadmin\detail\Detail;
use Eadmin\form\Form;
use Eadmin\grid\event\Deleted;
use Eadmin\grid\event\Deling;
use Eadmin\grid\event\Updated;
use Eadmin\grid\event\Updateing;
use Eadmin\grid\excel\Csv;
use Eadmin\grid\excel\Excel;
use Eadmin\grid\excel\ExcelQueue;
use Eadmin\traits\CallProvide;
use think\db\Query;
use think\facade\Event;
use think\facade\Filesystem;
use think\facade\Log;
use think\facade\Request;
use think\helper\Arr;
use think\helper\Str;
use think\Model;

/**
 * 表格
 * Class Grid
 * @package Eadmin\grid
 * @method $this fontSize(int $size)    表格字体大小
 * @method $this size(string $size)    表格大小 default | middle | small
 * @method $this tableLayout(string $value) auto / fixed
 * @method $this scroll(array $height) {
 * x: number | true, y: number
 * }
 * @method $this bordered(bool $bool = true) 是否展示外边框和列边框
 * @method $this quickSearch(bool $bool = true) 快捷搜索
 * @method $this quickSearchText(string $string) 快捷提示文本内容
 * @method $this hideDeleteButton(bool $bool = true) 隐藏删除按钮
 * @method $this hideTrashed(bool $bool = true) 隐藏回收站
 * @method $this hideTools(bool $bool = true) 隐藏工具栏
 * @method $this autoLayout(bool $bool = true) 布局自适
 * @method $this autoHeight(bool $bool = true) 自适应高度
 * @method $this hideSelection(bool $bool = true) 隐藏选择框
 * @method $this hideDeleteSelection(bool $bool = true) 隐藏删除选中按钮
 * @method $this hideTrashedDelete(bool $bool = true) 隐藏回收站删除按钮
 * @method $this hideTrashedRestore(bool $bool = true) 隐藏回收站恢复按钮
 * @method $this hideExportAll(bool $bool = true) 隐藏导出全部
 * @method $this queueExport(bool $bool = true) 是否启动队列导出
 * @method $this expandFilter(bool $bool = true) 展开筛选
 * @method $this defaultExpandAllRows(bool $bool = true) 是否默认展开所有行
 * @method $this static (bool $bool) 静态表格
 * @method $this expandRowByClick(bool $bool = true) 通过点击行来展开子行
 * @method $this stripe(bool $bool = true) 斑马纹表格
 * @method $this showHeader(bool $bool = true) 是否显示表头
 * @method $this initLoad(bool $bool = true) 初始化加载数据
 * @method $this loadDataUrl(string $value) 设置加载数据url
 * @method $this params(array $value) 加载数据附加参数
 * @method $this selectionType(string $value) 多选checkbox 单选radio
 * @method $this sumText(string $value) 合计行第一列的文本
 * @property Filter $filter
 */
class Grid extends Component
{
    use CallProvide;

    protected $name = 'EadminGrid';

    protected $column = [];
    protected $childrenColumn = [];

    protected $pagination;
    //是否隐藏分页
    protected $hidePage = false;
    //操作列
    protected $actionColumn;
    //是否隐藏操作列
    protected $hideAction = false;
    //是否隐藏添加按钮
    protected $hideAddButton = false;
    //查询过滤
    protected $filter = null;
    protected $filterClosure = null;

    //是否开启树形表格
    protected $isTree = false;

    //树形上级id
    protected $treeParent = 'pid';
    protected $treeId = 'id';

    protected $drive;

    //导出文件名
    protected $exportFileName = null;

    protected $formAction = null;

    protected $detailAction = null;
    //工具栏
    protected $tools = [];
    //展开行
    protected $expandRow = null;

    //自定义列表元素
    protected $customClosure = null;

    protected $get = [];

    public function __construct($data)
    {
        if ($data instanceof Model) {
            $this->drive = new \Eadmin\grid\drive\Model($data);
        } elseif ($data instanceof GridInterface) {
            $this->drive = $data;
        } else {
            $this->drive = new \Eadmin\grid\drive\Arrays($data);
        }
        $this->bindAttValue('modelValue', false, true);
        $this->bindAttValue('addParams', []);
        $this->attr('eadmin_grid_param', $this->bindAttr('addParams'));
        $this->attr('eadmin_grid', $this->bindAttr('modelValue'));
        $this->scroll(['x' => 'max-content']);
        $this->attr('locale', ['emptyText' => admin_trans('admin.empty')]);
        $this->loadDataUrl('eadmin.rest');
        $this->parseCallMethod();
        $this->bind('eadmin_description', admin_trans('admin.list'));
        $this->hideTrashed(!$this->drive->trashed());
        //分页初始化
        $this->pagination = new Pagination();
        $this->pagination->pageSize(20);
        //操作列
        $this->actionColumn = new Actions($this);
        $this->get = request()->get();
        parent::__construct();
    }

    public static function create($data, \Closure $closure)
    {
        $self = new static($data);
        $self->parseCallMethod(true, 2);
        $self->setExec($closure);
        return $self;
    }

    /**
     * 设置标题
     * @param string $title
     * @return string
     */
    public function title(string $title)
    {
        return $this->bind('eadmin_title', $title);
    }
    protected function componentContent($content){
        if ($content instanceof Component || is_string($content)) {
            $content = [$content];
        }
        foreach ($content as &$item) {
            if (!($item instanceof Component)) {
                $item = Html::create($item);
            }
        }
        return $content;
    }
    /**
     * 头部
     * @param $content
     */
    public function header($content)
    {
        $this->attr('header', $this->componentContent($content));
    }

    /**
     * 表格尾部
     * @param $content
     */
    public function footer(){
        $this->attr('footer', $this->componentContent($content));
    }
    /**
     * 设置高度
     * @param int $height 高度
     */
    public function height($height)
    {
        $this->scroll(['x' => 'max-content', 'y' => $height]);
    }

    /**
     * 静态表格模式
     */
    public function tableMode()
    {
        $this->hideTools();
        $this->hideAction();
        $this->hidePage();
        $this->hideSelection();
        $this->static();
    }

    /**
     * 展开行
     * @param \Closure $closure
     * @param bool $defaultExpandAllRow 默认是否展开
     */
    public function expandRow(\Closure $closure, bool $defaultExpandAllRow = false)
    {
        $this->expandRow = $closure;
        $this->attr('expandedRow', true);
        $this->attr('defaultExpandAllRows', $defaultExpandAllRow);
    }

    /**
     * 头像昵称咧
     * @param string $avatar 头像
     * @param string $nickname 昵称
     * @param string $label 标签
     * @return Column
     */
    public function userInfo($avatar = 'headimg', $nickname = 'nickname', $label = null)
    {
        if (is_null($label)) {
            $label = admin_trans('admin.user_info');
        }
        $column = $this->column($nickname, $label);
        return $column->display(function ($val, $data) use ($column, $avatar) {
            $avatarValue = Arr::get($data, $avatar);
            $image = Image::create()
                ->fit('cover')
                ->attr('style', ['width' => '50px', 'height' => '50px', "borderRadius" => '50%'])
                ->previewSrcList([$avatarValue])->src($avatarValue);
            return Html::create()->content($image)->content("<br>{$val}");
        })->align('center')->export(function ($val) {
            return $val;
        });
    }

    public function formAction()
    {
        return $this->formAction;
    }

    public function detailAction()
    {
        return $this->detailAction;
    }

    /**
     * 设置from表单
     * @param Form $form
     */
    public function setForm(Form $form)
    {
        $this->formAction = new ActionMode();
        $this->formAction->form($form);
        return $this->formAction;
    }

    /**
     * 设置detail详情
     * @param Detail $detail
     */
    public function setDetail($detail)
    {
        $this->detailAction = new ActionMode();
        $this->detailAction->detail($detail);
        return $this->detailAction;
    }

    public function drive()
    {
        return $this->drive;
    }

    /**
     * 获取当前模型
     * @return \think\Db
     */
    public function model()
    {
        return $this->drive->db();
    }

    /**
     * 查询过滤
     * @param \Closure $callback
     */
    public function filter(\Closure $callback)
    {
        if ($callback instanceof \Closure) {
            $this->filterClosure = $callback;
            $this->getFilter();
        }
    }

    public function getFilter()
    {
        if (is_null($this->filter)) {
            $this->filter = new Filter($this->drive->db());
        }
        return $this->filter;
    }

    //更新前回调
    public function updateing(\Closure $closure)
    {
        Event::listen(Updateing::class, function ($params) use ($closure) {
            call_user_func_array($closure, $params);
        });
    }

    //更新后回调
    public function updated(\Closure $closure)
    {
        Event::listen(Updated::class, function ($params) use ($closure) {
            call_user_func_array($closure, $params);
        });
    }

    //删除前回调
    public function deling($closure)
    {
        Event::listen(Deling::class, function ($id) use ($closure) {
            $trueDelete = Request::delete('trueDelete');
            call_user_func_array($closure, [$id, $trueDelete]);
        });
    }

    //删除后回调
    public function deleted(\Closure $closure)
    {
        Event::listen(Deleted::class, function ($id) use ($closure) {
            $trueDelete = Request::delete('trueDelete');
            call_user_func_array($closure, [$id, $trueDelete]);
        });
    }

    /**
     * 删除
     * @param int $id 删除的id
     * @return bool|int
     */
    public function destroy($id)
    {
        $this->exec();
        Event::until(Deling::class, $id);
        $result = $this->drive->destroy($id);
        Event::until(Deleted::class, $id);
        return $result;
    }

    /**
     * 更新
     * @param array $ids 更新的id
     * @param array $data 更新的数组
     * @return mixed
     */
    public function update($ids, $data)
    {

        $this->exec();
        Event::until(Updateing::class, [$ids, $data]);
        $result = $this->drive->update($ids, $data);
        Event::until(Updated::class, [$ids, $data]);
        if ($result !== false) {
            admin_success(admin_trans('admin.operation_complete'), admin_trans('admin.save_success'));
        } else {
            admin_error_message(admin_trans('admin.save_fail'));
        }
    }

    /**
     * 开启导出
     * @param string $fileName 导出文件名
     */
    public function export($fileName = '', $format = 'csv')
    {
        $this->attr('export', true);
        $this->attr('exportType', $format);
        $this->attr('Authorization', rawurlencode(Admin::token()->get()));
        $this->exportFileName = empty($fileName) ? date('YmdHis') : $fileName;
    }

    /**
     * 拖拽排序
     * @param string $field 排序字段
     * @param string $label 标题
     */
    public function sortDrag($field = 'sort', $label = null)
    {
        $this->drive->sortField($field);
        $column = $this->column($field, $label ?? admin_trans('admin.sort'))
            ->attr('slots', ['title' => $field, 'customRender' => 'sortDrag'])
            ->width(50)->align('center');
        return $column;
    }

    /**
     * 输入框排序
     * @param string $field 排序字段
     * @param string $label 标题
     * @return Column
     */
    public function sortInput($field = 'sort', $label = null)
    {
        $this->drive->sortField($field);
        $column = $this->column($field, $label ?? admin_trans('admin.sort'))
            ->attr('slots', ['title' => $field, 'customRender' => 'sortInput'])
            ->width(100)->align('center');
        return $column;
    }

    /**
     * 开启树形表格
     * @param string $pidField 父级字段
     * @param string $idField 下级字段
     * @param bool $expand 是否展开
     */
    public function treeTable($pidField = 'pid', $idField = 'id', $expand = true)
    {
        $this->treeParent = $pidField;
        $this->treeId = $idField;
        $this->isTree = true;
        $this->hidePage();
        $this->defaultExpandAllRows($expand);
    }

    /**
     * 操作列定义
     * @param \Closure|null $closure
     * @return Column
     */
    public function actions(\Closure $closure = null)
    {
        if (!is_null($closure)) {
            $this->actionColumn->setClosure($closure);
        }
        return $this->actionColumn->column();
    }

    /**
     * 隐藏添加按钮
     * @param bool $bool
     */
    public function hideAddButton(bool $bool = true)
    {
        $this->hideAddButton = $bool;
    }

    /**
     * 隐藏操作列
     * @param bool $bool
     */
    public function hideAction(bool $bool = true)
    {
        $this->hideAction = $bool;
    }

    public function tools($tools)
    {
        if (is_array($tools)) {
            foreach ($tools as $tool) {
                $this->tools($tool);
            }
        } elseif ($tools instanceof Component) {
            $this->tools[] = $tools;
        } else {
            $this->tools[] = Html::create()->content($tools);
        }
        return $this;
    }

    /**
     * 关闭分页
     * @param bool $bool
     */
    public function hidePage(bool $bool = true)
    {
        $this->hidePage = $bool;
    }

    /**
     * 分页组件
     * @return Pagination
     */
    public function pagination()
    {
        return $this->pagination;
    }

    /**
     * 设置分页每页限制
     * @Author: rocky
     * 2019/11/6 14:01
     * @param int $limit
     */
    public function setPageLimit(int $limit)
    {
        $this->pagination->pageSize($limit);
    }

    /**
     * 添加表格列
     * @param string|\Closure $field 字段
     * @param string $label 显示的标题
     * @return Column
     */
    public function column($field = '', $label = '')
    {
        $childrenColumns = [];
        if ($field instanceof \Closure) {
            $prop = 'group' . md5($label . time());
            $childrenColumns = $this->collectColumns($field);
            $column = new Column($prop, $label, $this);
            $column->attr('children', array_column($childrenColumns, 'attribute'));
            foreach ($childrenColumns as $childrenColumn) {
                $childrenColumn->attr('children_row', true);
                $this->childrenColumn[] = $childrenColumn;
            }
        } else {
            $column = new Column($field, $label, $this);
            $this->drive->realiton($field);
        }
        $class = $this->callClass . '\\' . $this->callFunction;
        if (Admin::checkFieldAuth($class, $field)) {
            $this->column[] = $column;
        }
        return $column;
    }

    public function getColumns()
    {
        return $this->column;
    }

    public function collectColumns(\Closure $closure)
    {
        $offset = count($this->column);
        call_user_func($closure, $this);
        $columns = array_slice($this->column, $offset);
        $this->column = array_slice($this->column, 0, $offset);
        return $columns;
    }

    /**
     * 自定义列表元素
     * @param \Closure $closure
     * @return Custom
     */
    public function custom(\Closure $closure)
    {
        $this->customClosure = $closure;
        $custom = new Custom();
        $this->attr('custom', $custom);
        return $custom;
    }

    /**
     * 解析列返回表格数据
     * @param array $datas 数据源
     * @param bool $export
     * @return array
     */
    protected function parseColumn($datas, $export = false)
    {

        $tableData = [];
        $columns = array_merge($this->column, $this->childrenColumn);
        //解析行数据
        foreach ($datas as $key => $data) {
            //主键
            $row = ['eadmin_id' => $data[$this->drive->getPk()] ?? $key];
            if (is_null($this->customClosure)) {
                //树形父级pid
                if ($this->isTree) {
                    $row['eadmin_tree_id'] = $data[$this->treeId];
                    $row['eadmin_tree_parent'] = $data[$this->treeParent];
                }
                foreach ($columns as $column) {
                    if ($column->attr('children')) {
                        continue;
                    }
                    $field = $column->attr('prop');
                    $row[$field] = $column->row($data);
                    if ($export) {
                        $row[$field] = $column->getExportData();
                    }
                }
                if (!is_null($this->expandRow)) {
                    $expandRow = call_user_func($this->expandRow, $data);
                    $row['EadminExpandRow'] = Html::create($expandRow);
                }
            } else {
                $row['custom'] = call_user_func($this->customClosure, $data);
            }
            if (!$this->hideAction && !$export) {
                $actionColumn = clone $this->actionColumn;
                $actionColumn->row($data);
                $row['EadminAction'] = $actionColumn;
            }
            $tableData[] = $row;
        }
        $isTotal = false;
        $row = ['eadmin_id' => 0];
        foreach ($this->column as $index=>$column) {
            $total = $column->getTotal();
            $field = $column->attr('prop');
            if($index==0){
                $row[$field] = Html::create($this->attr('sumText') ?? '合计');
            }elseif ($total === false) {
                $row[$field] = '';
            } else {
                $isTotal = true;
                $row[$field] = Html::create($total);
            }
        }
        if ($isTotal && !$export && count($tableData) > 0) {
            $row['eadmin_total_row'] = true;
            $tableData[] = $row;
        }
        return $tableData;
    }

    /**
     * 导出数据
     */
    public function exportData()
    {
        if (Request::has('eadmin_queue')) {
            $data = Request::get();
            $data['eadmin_domain'] = Request::domain();
            $id = sysqueue('导出excel', ExcelQueue::class, $data);
            return [
                'code' => 200,
                'data' => $id,
            ];
        }
        $this->exec();
        //快捷搜索
        $keyword = Request::get('quickSearch', '', ['trim']);
        $this->drive->quickFilter($keyword, $this->column);

        foreach ($this->column as $column) {
            $field = $column->attr('prop');
            $label = $column->attr('label');
            if (!$column->attr('closeExport')) {
                $columnTitle[$field] = $label;
            }
        }
        if ($this->attr('exportType') == 'csv') {
            $excel = new Csv();
        } else {
            $excel = new Excel();
        }
        $excel->file(date('YmdHis'));
        if (is_callable($this->exportFileName)) {
            $excel->callback($this->exportFileName);
        } else {
            $excel->file($this->exportFileName);
        }
        $excel->columns($columnTitle);
        if (Request::get('export_type') == 'all') {
            $count = $this->drive->getTotal();
            $this->drive->db()->chunk(500, function ($datas) use ($excel, $count) {
                $exportData = $this->parseColumn($datas, true);
                $excel->rows($exportData);
                Request::has('eadmin_queue_export') ? $excel->queueExport($count) : $excel->export();
                $this->exportData = [];
            });
            if (Request::has('eadmin_queue_export')) {
                return true;
            } else {
                exit;
            }
        } elseif (Request::get('export_type') == 'select') {
            $data = $this->drive->model()->whereIn($this->drive->getPk(), Request::get('eadmin_ids'))->select();
        } else {
            $page = Request::get('page', 1);
            $size = Request::get('size', (int)$this->pagination->attr('pageSize'));
            $data = $this->drive->getData($this->hidePage, $page, $size);
        }
        $exportData = $this->parseColumn($data, true);
        $excel->rows($exportData);
        if (Request::has('eadmin_queue_export')) {
            $count = count($exportData);
            $excel->queueExport($count);
            return true;
        } else {
            $excel->export();
            exit;
        }
    }

    public function parseData()
    {
        //总条数
        $this->pagination->total($this->drive->getTotal());
        //排序
        if (Request::has('eadmin_sort_field')) {
            $this->drive->db()->removeOption('order')->order(Request::get('eadmin_sort_field'), Request::get('eadmin_sort_by'));
        }

        $page = Request::get('page', 1);
        $size = Request::get('size', $this->pagination->attr('pageSize'));
        $data = $this->drive->getData($this->hidePage, $page, $size);

        //解析列
        $data = $this->parseColumn($data);
        //树形
        if ($this->isTree) {
            $data = Admin::tree($data, 'eadmin_tree_id', 'eadmin_tree_parent');
        }
        return $data;
    }

    public function jsonSerialize()
    {
        $this->exec();

        //添加按钮
        if (!$this->hideAddButton && !is_null($this->formAction)) {
            $form = $this->formAction->form();
            $callMethod = $form->getCallMethod();
            $form->eventSuccess([$form->bindAttr('model') => $callMethod]);
            $button = Button::create($this->formAction->addText())
                ->type('primary')
                ->size('small')
                ->icon('el-icon-plus');
            $action = clone $this->formAction->component();
            if ($action instanceof Html) {
                $button = $action->content($button)->redirect("eadmin/create.rest", ['eadmin_description' => $this->formAction->addText()] + $callMethod);
            } else {
                $button = $action->bindValue(false)->reference($button)->title($this->formAction->addText())->form($form)->url('/eadmin/create.rest');
            }
            //添加权限
            $action->auth($callMethod['eadmin_class'], $callMethod['eadmin_function'], 'post');
            $this->attr('addButton', $button);
        }
        //删除权限
        if (!Admin::check($this->callClass, $this->callFunction, 'delete')) {
            $this->hideDeleteButton();
            $this->hideDeleteSelection();
        }
        //工具栏
        $this->attr('tools', $this->tools);
        //快捷搜索
        $keyword = Request::get('quickSearch', '', ['trim']);
        $this->drive->quickFilter($keyword, $this->column);
        //params
        $params = (array)$this->attr('params');
        $eadmin_class = request()->get('eadmin_class');
        $eadmin_function = request()->get('eadmin_function');
        $this->params(array_merge($this->get, request()->get(), ['eadmin_grid' => $this->attr('eadmin_grid')], $this->getCallMethod(), $params));
        //查询视图
        if (!is_null($this->filter)) {
            if (!is_null($this->filterClosure)) {
                //获取form默认参数
                $initFilter = new Filter($this->drive->db());
                call_user_func($this->filterClosure, $initFilter);
                $form = $initFilter->form();
                json_encode($form);
                $filterData = $form->bind($form->bindAttr('model'));
                $get = $this->attr('params');
                $exceptField = $form->attr('exceptField');
                $exceptField = array_merge($exceptField);
                foreach ($filterData as $field=>$value){
                    if(!isset($get[$field]) && !in_array($field,$exceptField)){
                        $get[$field] = $value;
                    }
                }
                Request::withGet($get);
                //执行筛选
                call_user_func($this->filterClosure, $this->filter);
            }
            $form = $this->filter->render();
            $form->eventSuccess([$this->bindAttr('modelValue') => true]);
            //排除筛选多余字段
            $this->attr('formFilter', $this->filter->filterShow());
            $this->attr('filterExceptField', $form->attr('exceptField'));
            $this->attr('filter', $form);
            $this->attr('filterField', $form->bindAttr('model'));

        }

        //添加操作列
        if (!$this->hideAction) {
            $this->column[] = $this->actionColumn->column();
        }
        if (request()->isAjax() && !$this->attr('initLoad')) {
            $this->attr('data', $this->parseData());
        }
        //是否分页
        if (!$this->hidePage) {
            $this->attr('pagination', $this->pagination->attribute);
        }
        if (request()->has('ajax_request_data') && $eadmin_class == $this->callClass && $eadmin_function == $this->callFunction && !$this->attr('static')) {

            return [
                'code' => 200,
                'data' => $this->attr('data'),
                'header' => $this->attr('header'),
                'tools' => $this->attr('tools'),
                'total' => $this->pagination->attr('total')
            ];
        } else {
            $this->attr('columns', array_column($this->column, 'attribute'));
            return parent::jsonSerialize(); // TODO: Change the autogenerated stub
        }
    }
}
