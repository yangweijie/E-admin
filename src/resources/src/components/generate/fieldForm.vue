<template>
    <div>
      <div class="tools">
        <el-button size="mini" type='warning' @click="syncTable">同步全表</el-button>
        <el-button size="mini" @click="addTable">新建表</el-button>
        <el-button size="mini" @click="editTable" v-show="selectTableIndex > -1">修改表</el-button>
        <el-popconfirm
            title="确认删除表？"
            @confirm="delTable()"
        >
          <template #reference>
            <el-button size="mini" type="danger" v-show="selectTableIndex > -1">删除表</el-button>
          </template>
        </el-popconfirm>
        <el-button @click="addField" size="mini" v-show="selectTableIndex > -1">添加字段</el-button>
        <el-button type="primary" @click="saveField" size="mini" v-show="selectTableIndex > -1">保存</el-button>
      </div>
      <div class="flex">

        <a-list bordered :data-source="tables" style="width: 150px;height: 565px;overflow-y: auto">
          <template #renderItem="{ item ,index}">
            <div :class="['listItem',selectTableIndex==index?'active':'']">
              <a-list-item @click="selectTable(index)">
                <a-list-item-meta>
                  <template #title>
                    <spam :class="['listItem',selectTableIndex==index?'active':'']">{{item.name}}</spam>
                  </template>
                  <template #description>
                    <span :class="['listItem',selectTableIndex==index?'active':'']">{{item.desc}}</span>
                  </template>
                </a-list-item-meta>
              </a-list-item>
            </div>
          </template>
        </a-list>
        <el-popover
            placement="right"
            :width="200"
            v-model:visible="tableVisible"
            trigger="manual"
        >
          <el-form :model="formTable">
            <el-form-item prop="name" :rules="[{ required: true, message: '请输入表名', trigger: 'blur' },{pattern: /^[a-zA-Z][0-9a-zA-Z_]*$/g, message: '只允许f字母开头，字母数字下划线', trigger: 'blur'}]">
              <el-input v-model="formTable.name" placeholder="表名"></el-input>
            </el-form-item>
            <el-form-item prop="desc" :rules="{ required: true, message: '请输入表注释', trigger: 'blur' }">
              <el-input v-model="formTable.desc" placeholder="表注释"></el-input>
            </el-form-item>
            <el-form-item>
              <el-button type="primary" size="mini" @click="saveTable" v-loading="loading">确定</el-button>
              <el-button type="primary" size="mini" @click="tableVisible = false">取消</el-button>
            </el-form-item>
          </el-form>
          <template #reference>
            <span> </span>
          </template>
        </el-popover>
        <a-table size="small" :loading="loading" ref="dragTable" :dataSource="dataSource" :scroll="{y:520}" :pagination="false" :custom-row="customRow" row-key="row_key" :locale="{emptyText:'暂无字段'}" style="flex: 1">

          <a-table-column title="字段标题" data-index="desc" :width="150">
            <template #default="{ record }">
              <el-input v-model="record.desc" placeholder="字段注释" size="mini"></el-input>
            </template>
          </a-table-column>
          <a-table-column title="字段名" data-index="field" :width="150">
            <template #default="{ record }">
              <el-input v-model="record.field" placeholder="字段名" size="mini"></el-input>
            </template>
          </a-table-column>
          <a-table-column title="字段类型" data-index="form_type" :width="140">
            <template #default="{ record }">
              <el-select v-model="record.form_type" size="mini" filterable>
                <el-option-group v-for="group in components" :label="group.label">
                  <el-option v-for="item in group.options" :label="item.label" :value="item.value"></el-option>
                </el-option-group>

              </el-select>
            </template>
          </a-table-column>
          <a-table-column title="默认值" data-index="value" :width="150">
            <template #default="{ record }">
              <el-autocomplete :fetch-suggestions="querySearch"  v-model="record.value" placeholder="默认值" size="mini"></el-autocomplete>
            </template>
          </a-table-column>
          <a-table-column title="数据类型" data-index="type" :width="110">
            <template #default="{ record }">
              <el-select v-model="record.type" size="mini">
                <el-option v-for="item in typeOptions" :label="item" :value="item"></el-option>
              </el-select>
            </template>
          </a-table-column>
          <a-table-column title="长度" data-index="length" :width="100">
            <template #default="{ record }">
              <el-input v-model="record.length" placeholder="长度" type="number" size="mini"></el-input>
            </template>
          </a-table-column>
          <a-table-column title="列表" data-index="grid" :width="60" align="center">
            <template #default="{ record }">
              <el-checkbox v-model="record.grid" size="mini"></el-checkbox>
            </template>
          </a-table-column>
          <a-table-column title="表单" data-index="form" :width="60" align="center">
            <template #default="{ record }">
              <el-checkbox v-model="record.form" size="mini"></el-checkbox>
            </template>
          </a-table-column>
          <a-table-column title="详情" data-index="detail" :width="60" align="center">
            <template #default="{ record }">
              <el-checkbox v-model="record.detail" size="mini"></el-checkbox>
            </template>
          </a-table-column>
          <a-table-column title="不为空" data-index="nullable" :width="60" align="center">
            <template #default="{ record }">
              <el-checkbox v-model="record.nullable" size="mini"></el-checkbox>
            </template>
          </a-table-column>

          <a-table-column title="索引" data-index="index_type" :width="100">
            <template #default="{ record }">
              <el-select v-model="record.index_type" clearable placeholder="索引" style="width:90px" size="mini">
                <el-option v-for="item in indexOptions" :label="item.label" :value="item.id"></el-option>
              </el-select>
            </template>
          </a-table-column>

          <a-table-column :width="80">
            <template #default="{ record ,index}">
              <div style="display: flex;align-items: center" v-show="hoverIndex == index" @click="copyField(record)">
                <i class="el-icon-rank sortHandel" style="font-weight:bold;cursor: grab"></i>
                <i class="el-icon-copy-document" style="margin-left: 10px;color: #409EFF;cursor: pointer"></i>
                <el-popconfirm
                    title="确认删除字段？"
                    @confirm="removeField(index)"
                >
                  <template #reference>
                    <div style="margin-left: 10px" v-show="hoverIndex == index">
                      <i class="el-icon-error" style="color: red;cursor: pointer"></i>
                    </div>
                  </template>
                </el-popconfirm>
              </div>
            </template>
          </a-table-column>
        </a-table>
      </div>

    </div>
</template>

<script>
    import {defineComponent, toRefs, reactive,ref,nextTick} from "vue"
    import {useHttp} from '@/hooks'
    import {genId} from '@/utils'
    import {components} from './config'

    import contoller from "@/components/generate/contoller";
    import Sortable from "sortablejs";
    export default defineComponent({
        name: "fieldForm",

        setup() {
            const {http,loading} = useHttp()
            const dragTable = ref('')
            const state = reactive({
                tableVisible:false,
                tables: [],
                dataSource: [],
                selectTableIndex:-1,
                formTable: {
                    name:'',
                    desc:'',
                },
                hoverIndex: '',
                rules: {
                    name: [
                        {required: true, message: '请填写字段名称', trigger: 'blur'},
                        {pattern: /^[a-zA-Z][0-9a-zA-Z_]*$/g, message: '只允许f字母开头，字母数字下划线', trigger: 'blur'},
                    ],
                    label: [
                        {required: true, message: '请填写字段标题', trigger: 'blur'}
                    ]
                },
                restaurants:[
                    {value:'NULL'},
                    {value:'empty'},
                ]
            })
            getTable()
            function getTable() {
                http('api/plugin/curd/table').then(res=>{
                    state.tables = res.data
                })
            }
            //字段类型
            const typeOptions = ['string','char', 'integer', 'decimal','float', 'text', 'longText','tinyint','time', 'datetime', 'timestamp','date','json']
            //索引
            const indexOptions = [
                {
                    id:1,
                    label:'index'
                },
                {
                    id:2,
                    label:'unique'
                }
            ]
            //添加字段
            function addField() {
                state.dataSource.push({
                    row_key: genId(),
                    field: '',
                    desc: '',
                    type: 'string',
                    length: '',
                    value: '',
                    index_type: '',
                    form_type:'',
                    grid: true,
                    form: true,
                    detail: true,
                    nullable: false,
                })
            }
            //复制字段
            function copyField(row){
              const data = JSON.parse(JSON.stringify(row))
              data.row_key = genId()
              state.dataSource.push(data)
            }
            nextTick(()=>{
              dragSort()
            })
            //拖拽排序
            function dragSort(){
              if(dragTable.value){
                const el = dragTable.value.$el.querySelectorAll('.ant-table-body > table > tbody')[0]
                Sortable.create(el, {
                  handle:'.sortHandel',
                  ghostClass: 'sortable-ghost', // Class name for the drop placeholder,
                  onEnd: evt => {
                    const targetRow = state.dataSource.splice(evt.oldIndex, 1)[0]
                    state.dataSource.splice(evt.newIndex, 0, targetRow)
                    console.log(state.dataSource)
                  }
                })
              }
            }
            //移除字段
            function removeField(index) {
                state.dataSource.splice(index, 1)
            }
            //选中表
            function selectTable(index) {
                state.selectTableIndex = index
                http({
                    url:'api/plugin/curd/field',
                    params:{
                        table_id: state.tables[state.selectTableIndex].id
                    }
                }).then(res=>{
                    state.dataSource = res.data
                    if(res.data.length == 0){
                        addField()
                    }
                })
            }
            //添加表
            function saveTable() {
                http({
                    url:'api/plugin/curd/table',
                    method:'post',
                    data:state.formTable
                }).then(res=>{
                    if(res.data.result == 1){
                        state.formTable.id = res.data.id
                        state.tables.push(JSON.parse(JSON.stringify(state.formTable)))
                        selectTable(state.tables.length-1)
                    }else{
                        state.tables[state.selectTableIndex].name = state.formTable.name
                        state.tables[state.selectTableIndex].desc = state.formTable.desc
                    }
                    state.formTable.name = ''
                    state.formTable.desc = ''
                    state.tableVisible = false
                    getTable()
                })
            }
            //添加表
            function addTable() {
                state.tableVisible = true
                delete state.formTable.id
            }
            //编辑表
            function editTable() {
                state.tableVisible = true
                state.formTable.name = state.tables[state.selectTableIndex].name
                state.formTable.old_name = state.tables[state.selectTableIndex].old_name
                state.formTable.desc = state.tables[state.selectTableIndex].desc
                state.formTable.id = state.tables[state.selectTableIndex].id
            }
            //保存字段
            function saveField() {
                http({
                    url:'api/plugin/curd/field',
                    method:'post',
                    data:{
                        table_id:state.tables[state.selectTableIndex].id,
                        table_name:state.tables[state.selectTableIndex].name,
                        data:state.dataSource
                    }
                }).then(res=>{
                    selectTable(state.selectTableIndex)
                })
            }
            //删除表
            function delTable() {
                http({
                    url:'api/plugin/curd/table',
                    method:'delete',
                    data:{
                        id:state.tables[state.selectTableIndex].id,
                        name:state.tables[state.selectTableIndex].name
                    }
                }).then(res=>{
                    state.tables.splice(state.selectTableIndex,1)
                    if(state.tables.length == 0){
                        state.dataSource = []
                        state.selectTableIndex = -1
                    }else{
                        selectTable(state.tables.length-1)
                    }
                })
            }
            function syncTable() {
                http('api/plugin/curd/table/sync')
            }
            function customRow(record,index) {
                return {
                    onMouseenter:e=>{
                        state.hoverIndex = index
                    },
                    onMouseleave:e=>{
                        state.hoverIndex = -1
                    }
                }
            }
            const querySearch = (queryString, cb) => {
                var results = queryString
                    ? state.restaurants.filter(item=>{
                        return item.value.toLowerCase().indexOf(queryString.toLowerCase()) === 0
                    })
                    : state.restaurants
                // 调用 callback 返回建议列表的数据
                cb(results);
            };
            return {
                copyField,
                querySearch,
                ...toRefs(state),
                customRow,
                editTable,
                loading,
                selectTable,
                syncTable,
                indexOptions,
                typeOptions,
                removeField,
                addField,
                addTable,
                saveTable,
                saveField,
                delTable,
                dragTable,
                components
            }
        }
    })
</script>

<style scoped>
    .tools{
        margin: 10px;
    }
    .flex {
        display: flex;
    }
    .listItem{
        cursor: pointer;
    }
    .listItem:hover{
        background: #409eff;
        color:#FFFFFF;
    }
    .active{
        background: #409eff;
        color:#FFFFFF;
    }
    .form .el-form-item--small.el-form-item {
        margin-bottom: 3px;
    }
</style>
