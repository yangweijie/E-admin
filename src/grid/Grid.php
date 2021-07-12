<?php
/**
 * Created by PhpStorm.
 * User: rocky
 * Date: 2020-04-14
 * Time: 23:27
 */

namespace Eadmin\grid;


use think\facade\Db;
use think\helper\Str;
use think\model\relation\BelongsTo;
use think\model\relation\BelongsToMany;
use think\model\relation\HasMany;
use think\model\relation\HasOne;
use think\model\relation\MorphMany;
use think\model\relation\MorphOne;
use Eadmin\facade\Button;
use Eadmin\facade\Component;
use Eadmin\form\Dialog;
use think\facade\Request;
use think\Model;
use Eadmin\grid\excel\Csv;
use Eadmin\grid\excel\Excel;
use Eadmin\service\AdminService;
use Eadmin\service\TokenService;
use Eadmin\View;

class Grid extends View
{
    protected $title = '';
    //当前模型
    protected $model;

    //当前模型的数据库查询对象
    protected $db;
    //列
    protected $columns = [];

    //数据
    protected $data = [];

    //表字段
    protected $tableFields = [];

    //表格组件
    protected $table;

    //是否开启树形表格
    protected $treeTable = false;

    //树形上级id
    protected $treeParent = 'pid';

    //树形追加数据
    protected $treeAppendData = [];

    //是否开启分页
    protected $isPage = true;

    //分页大小
    protected $pageLimit = 20;

    //隐藏操作列
    protected $hideAction = false;

    //操作列
    protected $actionColumn;

    //软删除字段
    protected $softDeleteField = 'delete_time';

    //是否开启软删除
    protected $isSotfDelete = false;

    //删除前回调
    protected $beforeDel = null;
    //更新前回调
    protected $beforeUpdate = null;
    //是否显示回收站
    protected $trashedShow = false;
    //工具栏
    protected $toolsArr = [];
    //查询过滤
    protected $filter = null;
    //排序字段
    protected $sortField = null;
    //导出数据
    protected $exportData = [];
    //导出文件名
    protected $exportFileName = null;
    protected $relations = [];
    //表格对齐方式
    protected $headerAlign = 'left';
    //初始化
    protected static $init = null;

    public function __construct(Model $model)
    {
        $this->model = $model;
        $this->db = $this->model->db();
        $this->tableFields = $this->model->getTableFields();
        $this->actionColumn = new Actions('eadminColumnAction', '');
        $this->table = new Table($this->columns, []);
        $this->table->setAttr(':max-height', 'tableHeight');
        $this->table->setAttr('style', 'z-index:0');
        if (in_array($this->softDeleteField, $this->tableFields)) {
            $this->isSotfDelete = true;
            if (request()->has('is_deleted')) {
                $this->db->removeWhereField($this->softDeleteField);
                $this->db->whereNotNull($this->softDeleteField);
            } else {
                $this->db->whereNull($this->softDeleteField);
            }
            $this->trashed(true);
        }
        $this->table->setVar('grid', true);
        if (!is_null(self::$init)) {
            call_user_func(self::$init, $this);
        }
    }

    /**
     * 头部内容
     */
    public function header($html)
    {
        $this->table->setVar('header', $html);
    }

    /**
     * 双击编辑
     */
    public function dbclickEdit()
    {
        $this->table->setVar('dbclickEdit', true);
    }

    /**
     * 双击详情
     */
    public function dbclickDetail()
    {
        $this->table->setVar('dbclickDetail', true);
    }

    /**
     * 拖拽排序列
     * @param string $field 排序字段
     * @param string $label 显示标签
     */
    public function sortDrag($field = 'sort', $label = '排序')
    {
        $this->sortField = $field;
        $this->model()->order($field);
        $this->column($field, $label)->display(function ($val, $data) {
            $html = <<<EOF
<div style="text-align:center;">
<el-tooltip  effect="dark" content="置顶" placement="right-start"><i @click="sortTop(index,data)" class="el-icon-caret-top" style="cursor: pointer"></i></el-tooltip>
<el-tooltip effect="dark" content="拖动排序" placement="right-start"><i class="el-icon-rank sortHandel" style="font-weight:bold;cursor: grab"></i></el-tooltip>
<el-tooltip  effect="dark" content="置底" placement="right-start"><i @click="sortBottom(index,data)" class="el-icon-caret-bottom" style="cursor: pointer"></i></el-tooltip>
</div>
EOF;
            return $html;
        })->width(50)->align('center');
    }

    /**
     * input排序列
     * @param string $field 排序字段
     * @param string $label 显示标签
     */
    public function sortInput($field = 'sort', $label = '排序')
    {
        $this->sortField = $field;
        $this->column('sort', $label)->display(function ($val, $data) use ($field) {
            $html = <<<EOF
<el-input v-model="data.{$field}" @change="sortInput(data,'{$field}',data.{$field})"></el-input>
EOF;
            return $html;
        })->width(80)->align('center');
    }

    /**
     * 是否显示回收站
     * @param bool $bool true显示，false隐藏
     */
    public function trashed($bool)
    {
        $this->trashedShow = $bool;
        $this->table->setVar('trashed', $this->trashedShow);
    }

    /**
     * 返回表格组件，可设置属性
     * @return Table
     */
    public function table()
    {
        return $this->table;
    }

    /**
     * 获取当前模型的数据库查询对象
     * @return Model
     */
    public function model()
    {
        return $this->db;
    }

    /**
     * 设置添加按钮参数
     * @Author: rocky
     * 2019/11/27 16:50
     * @param array $params 格式：['id'=>1,'a'=>2]
     */
    public function setAddButtonParam(array $params)
    {
        $this->table->setVar('addButtonParam', $params);
    }

    /**
     * 对话框表单
     * @param bool $fullscreen 是否全屏
     * @param string  $width 宽度
     */
    public function setFormDialog($fullscreen = false, $width = '40%')
    {
        $this->table->setFormDialog('', $fullscreen, $width);
    }
    public function setFormWindow(){
        $this->table->setVar('newFormWindow', true);
    }
    /**
     * 快捷搜索
     */
    public function quickSearch()
    {
        $this->table->setVar('quickSearch', true);
    }

    /**
     * 对话框表单
     * @param string $direction 打开方向
     * @param string $size 宽度
     */
    public function setFormDrawer($direction = 'rtl', $size = '30%')
    {
        $this->table->setFormDrawer('', $direction, $size);
    }

    /**
     * 开启树形表格
     * @param string $pidField 父级字段
     * @param array $appendData 追加数据
     * @param bool $expand 是否展开
     */
    public function treeTable($pidField = 'pid', $appendData = [], $expand = true)
    {
        $this->treeParent = $pidField;
        $this->isPage = false;
        $this->treeTable = true;
        $this->treeAppendData = $appendData;
        if ($expand) {
            $this->table->setAttr('default-expand-all', true);
        }
    }

    protected function tree($list, $pid = 0)
    {
        $tree = [];
        if (!empty($list)) {
            $newList = [];
            foreach ($list as $k => $v) {
                $newList[$v['id']] = $v;
            }
            foreach ($newList as $value) {
                if ($pid == $value[$this->treeParent]) {
                    $tree[] = &$newList[$value['id']];
                } elseif (isset($newList[$value[$this->treeParent]])) {
                    $newList[$value[$this->treeParent]]['children'][] = &$newList[$value['id']];
                }
            }
        }
        return $tree;
    }

    //头像昵称列
    public function userInfo($headimg = 'headimg', $nickname = 'nickname', $label = '会员信息')
    {
        $column = $this->column($nickname, $label);
        return $column->display(function ($val, $data) use ($column, $headimg) {
            $headimgValue = $column->getValue($data, $headimg);
            return "<el-image style='width: 80px; height: 80px;border-radius: 50%' src='{$headimgValue}' fit='cover' :preview-src-list='[\"{$headimgValue}\"]'></el-image><br>{$val}";
        })->align('center');
    }

    /**
     * 设置标题
     * @param $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
        $this->table->setVar('title', $title);
    }
    public function title(){
        return $this->title;
    }
    /**
     * 操作列定义
     * @param \Closure $closure
     */
    public function actions(\Closure $closure)
    {
        $this->actionColumn->setClosure($closure);
    }

    /**
     * 隐藏操作列
     */
    public function hideAction()
    {
        $this->hideAction = true;
    }

    /**
     * 设置分页每页限制
     * @Author: rocky
     * 2019/11/6 14:01
     * @param $limit
     */
    public function setPageLimit($limit)
    {
        $this->pageLimit = $limit;
    }

    /**
     * 关闭分页
     */
    public function hidePage()
    {
        $this->isPage = false;
    }

    public function addTools($html)
    {
        if ($html instanceof \Eadmin\form\Button) {
            $this->toolsArr[] = $html->render();
        } else {
            $this->toolsArr[] = $html;
        }
        return $this;
    }


    /**
     * 设置列
     * @Author: rocky
     * 2019/7/25 16:20
     * @param string|callable $field 字段
     * @param string $label 标签
     * @return Column
     */
    public function column($field, $label)
    {
        if($field instanceof \Closure){
            //多级表头处理
            $f = md5(count($this->columns).$label);
            $column = new Column($f, $label, $this);
            $column->align($this->headerAlign);
            $gridColumn = $this->columns;
            $this->columns = [];
            call_user_func_array($field,[$this]);
            $column->setChild($this->columns);
            $this->columns = $gridColumn;
        }else{
            $column = new Column($field, $label, $this);
            $column->align($this->headerAlign);
            $fields = explode('.', $field);
            if (count($fields) > 1) {
                array_pop($fields);
                $relation = implode('.', $fields);
            } else {
                $relation = $field;
            }
            if (count($fields) > 1) {
                foreach ($fields as $field) {
                    $this->setRelation($field, $relation);
                }
            } else {
                $this->setRelation($relation, $relation);
            }
        }
        array_push($this->columns, $column);
        return $column;
    }

    private function setRelation($ifRelation, $relation)
    {
        if (method_exists($this->model, $ifRelation) &&
            ($this->model->$ifRelation() instanceof BelongsTo ||
                $this->model->$ifRelation() instanceof HasOne ||
                $this->model->$ifRelation() instanceof HasMany ||
                $this->model->$ifRelation() instanceof MorphOne ||
                $this->model->$ifRelation() instanceof MorphMany)) {
            $this->relations[] = $relation;
        }
    }

    /**
     * 设置索引列
     * @param string $type 列类型：selection 多选框 ， index 索引 ， expand 可展开的
     * @return Column
     */
    public function indexColumn($type = 'selection',$label='')
    {
        $column = $this->column('eadminColumnIndex' . $type, $label)->closeExport();
        $column->setAttr('type', $type);
        return $column;
    }


    /**
     * 解析列
     */
    protected function parseColumn()
    {
        //是否隐藏操作列
        if (!$this->hideAction) {
            array_push($this->columns, $this->actionColumn);
        }
        if (count($this->treeAppendData) > 0) {
            $this->data = $this->data->merge($this->treeAppendData);
            $this->data = $this->data->sort(function ($a, $b) {
                return $a['sort'] > $b['sort'];
            });
        }
        if (count($this->data) > 0) {
            foreach ($this->data as $key => &$rows) {
                foreach ($this->columns as $column) {
                    $field = $column->getField();
                    $column->setData($rows);
                    $this->exportData[$key][$column->field] = $column->getExportValue();
                    if ($column->isTotal()) {
                        $rows[$field . 'isTotalRow'] = true;
                        $rows[$field . 'totalText'] = $column->totalText;
                        $this->table->setAttr('show-summary', true);
                        $this->table->setAttr(':summary-method', 'columnSumHandel');
                    }
                }
            }
        } else {
            foreach ($this->columns as $column) {
                $column->setData([]);
            }
        }
        $this->table->setColumn($this->columns);
        $this->table->setVar('toolbar', implode('', $this->toolsArr));
    }

    /**
     * 更新数据
     * @param string $ids 更新条件id
     * @param array $data 更新数据
     * @return mixed
     */
    public function update($ids, $data)
    {
        if (!is_null($this->beforeUpdate)) {
            call_user_func($this->beforeUpdate, $ids, $data);
        }
        $action = isset($data['action']) ? $data['action'] : '';
        if ($action == 'buldview_drag_sort') {
            $sortable_data = $data['sortable_data'];
            $field = "id,(@rownum := @rownum+1),case when @rownum = {$sortable_data['sort']} then @rownum := @rownum+1 else @rownum := @rownum end AS rownum";
            $sortSql = $this->db->table("(SELECT @rownum := -1) r," . $this->model->getTable())
                ->fieldRaw($field)
                ->removeOption('order')
                ->order($this->sortField)
                ->where('id', '<>', $sortable_data['id'])
                ->buildSql();
            $this->model->where($this->model->getPk(), $sortable_data['id'])->update([$this->sortField => $sortable_data['sort']]);
            $res = Db::execute("update {$this->model->getTable()} inner join {$sortSql} a on a.id={$this->model->getTable()}.id set {$this->sortField}=a.rownum");
            if ($res) {
                Component::notification()->success('操作成功', '排序完成');
            }
        } else {
            $res = $this->model->removeWhereField($this->softDeleteField)->strict(false)->whereIn($this->model->getPk(), $ids)->update($data);
            if ($res && isset($data[$this->sortField])) {
                Component::notification()->success('操作成功', '排序完成');
            } elseif ($res) {
                return true;
            } else {
                return false;
            }
        }
    }

    /**
     * 隐藏工具栏
     */
    public function hideTools()
    {
        $this->table->setVar('hideTools', true);
    }

    /**
     * 隐藏添加按钮
     */
    public function hideAddButton()
    {
        $this->table->setVar('hideAddButton', true);
    }

    /**
     * 隐藏删除按钮
     */
    public function hideDeleteButton()
    {
        $this->table->setVar('hideDeletesButton', true);
    }

    //更新前回调
    public function updateing(\Closure $closure)
    {
        $this->beforeUpdate = $closure;
    }

    //删除前回调
    public function deling(\Closure $closure)
    {
        $this->beforeDel = $closure;
    }

    /**
     * 查询过滤
     * @param $callback
     */
    public function filter($callback)
    {
        if ($callback instanceof \Closure) {

            call_user_func($callback, $this->getFilter());
        }
    }

    public function getFilter()
    {
        if (is_null($this->filter)) {
            $this->filter = new Filter($this->db);
        }
        return $this->filter;
    }

    /**
     * 删除数据
     */
    public function destroy($id)
    {
        $trueDelete = Request::delete('trueDelete');
        if ($id == 'delete') {
            $ids = Request::delete('ids');
        } else {
            $ids = explode(',', $id);
        }
        if ($ids == 'true') {
            $ids = true;
        }
        if (!is_null($this->beforeDel)) {
            call_user_func($this->beforeDel, $ids, $trueDelete);
        }
        $res = false;
        Db::startTrans();
        try {
            $this->db->removeWhereField($this->softDeleteField);
            if ($ids === true) {
                if ($this->isSotfDelete && !$trueDelete) {
                    $res = $this->db->where('1=1')->update([$this->softDeleteField => date('Y-m-d H:i:s')]);
                } else {
                    if (in_array($this->softDeleteField, $this->tableFields)) {
                        $deleteDatas = $this->db->whereNotNull($this->softDeleteField)->select();
                        $this->deleteRelationData($deleteDatas);
                        $res = $this->db->whereNotNull($this->softDeleteField)->delete();
                    } else {
                        $deleteDatas = $this->model->select();
                        $this->deleteRelationData($deleteDatas);
                        $res = $this->db->where('1=1')->delete();
                    }
                }
            } else {
                if ($this->isSotfDelete && !$trueDelete) {
                    $res = Db::name($this->model->getTable())->whereIn($this->model->getPk(), $ids)->update([$this->softDeleteField => date('Y-m-d H:i:s')]);
                } else {
                    if ($ids === true) {
                        $this->deleteRelationData(true);
                    } else {
                        $deleteDatas = $this->model->removeOption('where')->whereIn($this->model->getPk(), $ids)->select();
                        $this->deleteRelationData($deleteDatas);
                    }
                    $res = Db::name($this->model->getTable())->delete($ids);
                }
            }
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            $res = false;
        }
        return $res;
    }

    /**
     * 删除关联数据
     * @param $deleteDatas
     * @throws \ReflectionException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    protected function deleteRelationData($deleteDatas)
    {
        $reflection = new \ReflectionClass($this->model);
        $methods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);
        $className = $reflection->getName();
        $relatonMethod = [];

        foreach ($methods as $method) {
            if ($method->class == $className) {
                $relation = $method->name;
                $p = new \ReflectionMethod($method->class, $relation);
                if ($p->getNumberOfParameters() == 0) {
                    if ($this->model->$relation() instanceof BelongsToMany) {
                        if ($deleteDatas === true) {
                            $deleteDatas = $this->model->select();
                        }
                        foreach ($deleteDatas as $deleteData) {
                            $deleteData->$relation()->detach();
                        }
                    } elseif ($this->model->$relation() instanceof HasOne) {
                        if ($deleteDatas === true) {
                            $deleteDatas = $this->model->select();
                        }
                        foreach ($deleteDatas as $deleteData) {
                            if (!is_null($deleteData->$relation)) {
                                $deleteData->$relation->delete();
                            }
                        }
                    }
                }
            }
        }
    }

    protected function getDataArray()
    {
        return $this->data->toArray();
    }

    /**
     * 开启导出
     * @param $fileName 导出文件名
     */
    public function export($fileName = '')
    {
        $this->table->setVar('exportOpen', true);
        $moudel = app('http')->getName();
        $node = $moudel . '/' . request()->pathinfo();
        $token = TokenService::instance()->get();
        $params = http_build_query(request()->param());
        $this->table->setVar('exportUrl', request()->domain() . '/' . $node . '?Authorization=' . rawurlencode($token) . '&' . $params);
        $this->exportFileName = empty($fileName) ? date('YmdHis') : $fileName;
    }

    //导出数据操作
    protected function exportData()
    {
        if (Request::get('build_request_type') == 'export') {
            foreach ($this->columns as $column) {
                $field = $column->field;
                if (!$column->closeExport && !empty($field && $field != 'actionColumn')) {
                    $columnTitle[$field] = $column->label;
                }
            }
            if (is_callable($this->exportFileName)) {
                $excel = new Excel();
                $excel->file(date('YmdHis'));
                $excel->callback($this->exportFileName);
            } else {
                $excel = new Csv();
                $excel->file($this->exportFileName);
            }
            $excel->columns($columnTitle);
            if (Request::get('export_type') == 'all') {
                set_time_limit(0);
                if ($excel instanceof Excel) {
                    $this->data = $this->db->select()->toArray();
                    $this->parseColumn();
                    $excel->rows($this->exportData)->export();
                } else {
                    $this->db->chunk(500, function ($datas) use ($excel) {
                        $this->data = $datas;
                        $this->parseColumn();
                        $excel->rows($this->exportData)->export();
                        $this->exportData = [];
                    });
                    exit;
                }
            } elseif (Request::get('export_type') == 'select') {
                $this->data = $this->model->whereIn($this->model->getPk(), Request::get('ids'))->select();
            }
            $this->parseColumn();
            $excel->rows($this->exportData)->export();
            exit;
        }
    }

    private function permissionCheck()
    {
        $node = $this->getRequestUrl();
        //添加权限判断
        if (!AdminService::instance()->check($node . '.rest', 'post')) {
            $this->hideAddButton();
        }
        //删除权限判断
        if (!AdminService::instance()->check($node . '/:id.rest', 'delete')) {
            $this->hideDeleteButton();
        }
    }

    //预关联加载
    protected function withRelations()
    {
        $this->relations = array_unique($this->relations);
        $with = $this->db->getOptions('with');
        if (is_null($with)) {
            $with = [];
        }
        $with = array_merge($with, $this->relations);
        if (count($with) > 0) {
            $this->db->with($with);
        }
    }

    //快捷搜索
    protected function quickFilter()
    {
        $keyword = Request::get('quickSearch', '', ['trim']);
        if ($keyword) {
            $whereFields = [];
            $whereOr = [];
            $relationWhereFields = [];
            $relationWhereOr = [];
            foreach ($this->columns as $column) {
                $fields = explode('.', $column->field);
                $field = $column->getField();
                $usings = $column->getUsings();
                if (count($fields) > 1) {
                    array_pop($fields);
                    $relation = array_pop($fields);;
                    if (empty($usings)) {
                        $relationWhereFields[$relation][] = $field;
                    } else {
                        foreach ($usings as $key => $value) {
                            if (strpos($value, $keyword) !== false) {
                                $relationWhereOr[$relation][$field] = $key;
                            }
                        }
                    }
                } else {
                    if (in_array($column->getField(), $this->tableFields)) {
                        if (empty($usings)) {
                            $whereFields[] = $field;
                        } else {
                            foreach ($usings as $key => $value) {
                                if (stripos($value, $keyword) !== false) {
                                    $whereOr[$field] = $key;
                                }
                            }
                        }
                    }
                }
            }
            //快捷搜索
            $relationWhereSqls = [];
            foreach ($this->relations as $relationName) {
                $relations = explode('.', $relationName);
                $Tmprelations = $relations;
                $relation = array_pop($Tmprelations);
                $relationFilter = implode('.', $relations);
                $model = get_class($this->model);
                $filter = new Filter(new $model);
                $filter->setIfWhere(false);
                $db = $this->model;
                foreach ($relations as $relation) {
                    $db = $db->getModel()->$relation();
                }
                $filter->relationLastDb($db,$relation);
                $relationName = $relation;
                $relationTableFields = $db->getTableFields();
                if (isset($relationWhereFields[$relationName])) {
					$relationWhereFields[$relationName] = array_intersect($relationWhereFields[$relationName], $relationTableFields);
					$fields = implode('|', $relationWhereFields[$relationName]);
					$relationWhereCondtion = $relationWhereOr[$relationName] ?? [];
					$db->where(function ($q) use ($fields, $keyword, $relationWhereCondtion) {
						foreach ($relationWhereCondtion as $field => $value) {
							$q->whereOr($field, $value);
						}
						$q->whereLike($fields, "%{$keyword}%", 'OR');
					});
					$filter->paseFilter(null, $relationFilter . '.');
					$wheres = $filter->db()->getOptions('where');
					foreach ($wheres['AND'] as $where){
						if($where[1] == 'EXISTS'){
							$relationWhereSqls[]  = $where[2];
							break;
						}
					}
                }
            }
            $fields = implode('|', $whereFields);
            $this->db->where(function ($q) use ($relationWhereSqls,$whereFields, $fields, $keyword, $whereOr) {
                //修复模糊查询，兼容mysql5.5以上
                foreach ($whereFields as $field) {
					$q->whereLike($field, "%{$keyword}%", 'OR');
                }
                foreach ($whereOr as $field => $value) {
                    $q->whereOr($field, $value);
                }
                foreach ($relationWhereSqls as $sql) {
                    $q->whereExists($sql, 'OR');
                }
            });
        }
    }

    //获取数据总条数
    private function getRowTotal()
    {
        $sql = $this->db->buildSql();
        $sql = "SELECT COUNT(*) FROM {$sql} userCount";
        $res = Db::query($sql);
        $count = $res[0]['COUNT(*)'];
        return $count;
    }

    /**
     * 视图渲染
     */
    public function view()
    {
        //预关联加载
        $this->withRelations();
        //快捷搜索
        $this->quickFilter();
        //排序
        if (Request::has('sort_field')) {
            $this->db->removeOption('order')->order(Request::get('sort_field'), Request::get('sort_by'));
        }
        //总记录条数
        $count = 0;
        $page = Request::get('page', 1);
        if ($page == 1) {
            $count = $this->getRowTotal();
            $this->table->setVar('pageTotal', $count);
        }
        //分页
        if ($this->isPage) {
            $this->table->setVar('pageHide', 'false');
            $this->table->setVar('pageSize', $this->pageLimit);
            $this->data = $this->db->page($page, Request::get('size', $this->pageLimit))->select();
        } else {
            $this->data = $this->db->select();
        }
        //软删除列
        if ($this->isSotfDelete) {
            if (request()->has('is_deleted')) {
                $this->column($this->softDeleteField, '删除时间')->closeExport();
                $this->hideAction();
                $this->column('eadminColumnActionDelete', '')->display(function ($val, $data) {
                    $button = Button::create('恢复数据', '', 'small', 'el-icon-zoom-in')
                        ->delete($data['id'], '此操作将恢复该数据, 是否继续?', 2);
                    $button .= Button::create('永久删除', 'danger', 'small', 'el-icon-delete')
                        ->delete($data['id'], '此操作将永久删除该数据, 是否继续?', 1);
                    return $button;
                });
            } else {
                $this->column($this->softDeleteField, '删除时间')->setAttr('v-if', 'deleteColumnShow')->closeExport();
            }
        }
        //如果是导出数据
        $this->exportData();
        //权限控制按钮
        $this->permissionCheck();
        //解析列
        $this->parseColumn();
        $this->table->setAttr('data', $this->getDataArray());
        $this->table->setAttr('row-key', $this->model->getPk());
        //查询过滤
        if (!is_null($this->filter)) {
            $this->table->setVar('filter', $this->filter->render());
            $this->table->setVar('filterMode', $this->filter->mode());
            $this->table->setScriptArr($this->filter->scriptArr);
        }
        //树形
        if ($this->treeTable) {
            $treeData = $this->tree($this->getDataArray());
            $this->data = $treeData;
            $this->table->setAttr('data', $treeData);
            $this->table->setAttr('tree-props', [
                'children' => 'children',
                'hasChildren' => 'hasChildren',
            ]);
        }
        $build_request_type = Request::get('build_request_type');
        $this->table->setVar('submitUrl', $this->getRequestUrl());
        $this->table->setVar('submitParams', request()->param());
        switch ($build_request_type) {
            case 'page':
                $this->table->view();
                $result['data'] = $this->data;
                if ($this->isPage && $page == 1) {
                    $result['total'] = $count;
                }
                $result['cellComponent'] = $this->table->cellComponent();
                Component::view($result);
                break;
            default:
                return $this->table->view();
        }
    }

    /**
     * 设置表格对其方式
     * @param string $align left/center/right
     */
    public function headerAlign($align = 'center')
    {
        $this->headerAlign = $align;
    }

    /**
     * 开启表格视图方案保存模式
     */
    public function onTableView()
    {
        $this->table->setVar('onTableView', true);
    }

    /**
     * 设置添加url
     * @param string $url
     * @param bool $rest
     */
    public function setAddUrl(string $url, bool $rest = false)
    {
        $this->table->setVar('addUrl', $url);
        $this->table->setVar('addRest', $rest);
    }

    /**
     * 设置编辑url
     * @param string $url
     * @param bool $rest
     */
    public function setEditUrl(string $url, bool $rest = false)
    {
        $this->table->setVar('editUrl', $url);
        $this->table->setVar('editRest', $rest);
    }

    /**
     * 设置详情url
     * @param string $url
     * @param bool $rest
     */
    public function setDetailUrl(string $url, bool $rest = false)
    {
        $this->table->setVar('detailUrl', $url);
        $this->table->setVar('detailRest', $rest);
    }

    /**
     * 初始化
     * @param \Closure $closure
     */
    public static function init(\Closure $closure)
    {
        self::$init = $closure;
    }
}
