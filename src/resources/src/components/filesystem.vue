<template>
  <div ref="filesystem">
    <el-card class="filesystem" shadow="never" :body-style="{padding:'0px'}">
      <template #header>
        <el-row style="display: flex;align-items: center;justify-content: space-between">
          <el-col :md="16" :xs="24" >
            <el-button-group style="display: flex">
<!--              <div v-if="!uploadFinder">-->
<!--                <el-button icon="el-icon-back" size="mini" @click="back"></el-button>-->
<!--                <div class="breadcrumb">-->
<!--                  <el-breadcrumb separator-class="el-icon-arrow-right" style="display: flex;white-space: nowrap;">-->
<!--                    <el-breadcrumb-item @click="changePath(initPath)" style="cursor: pointer">根目录</el-breadcrumb-item>-->
<!--                    <el-breadcrumb-item v-for="item in breadcrumb" @click="changePath(item.path)"-->
<!--                                        :style="item.path ? 'cursor: pointer':''">{{item.name}}-->
<!--                    </el-breadcrumb-item>-->
<!--                  </el-breadcrumb>-->
<!--                </div>-->
<!--              </div>-->
              <el-button icon="el-icon-refresh" size="mini" @click="loading = true"></el-button>
              <render :data="upload" :drop-element="filesystem"  :params="finderParam"  :on-progress="uploadProgress" @success="uploadSuccess"></render>
              <EadminSelect size="mini" :disabled="selectIds.length == 0" v-if="uploadFinder" :placeholder="trans('filesystem.move')" :options="finerCate" tree clearable v-model="selectCate"></EadminSelect>
              <el-button  size="mini" @click="mkdir" v-if="!uploadFinder">{{ trans('filesystem.mkdir') }}</el-button>
              <el-button  size="mini" type="danger" v-if="selectIds.length > 0" @click="delSelect">{{ trans('filesystem.delSelected') }}</el-button>
            </el-button-group>
          </el-col>
          <el-col :md="8" :xs="24" style="display: flex;">
            <!--快捷搜索-->
            <el-input class="hidden-md-and-down" v-model="quickSearch" clearable prefix-icon="el-icon-search"
                      size="mini" style="margin-right: 10px;flex: 1" :placeholder="trans('filesystem.searchKey')" @change="loading = true" ></el-input>
            <el-button class="hidden-md-and-down" type="primary" size="mini" icon="el-icon-search" @click="loading = true">
              {{ trans('filesystem.search') }}</el-button>
            <el-tooltip v-if="showType === 'grid'" :content="trans('filesystem.listSort')">
              <el-button icon="el-icon-s-grid"  size="mini" @click="showType='menu'"></el-button>
            </el-tooltip>
            <el-tooltip v-else :content="trans('filesystem.iconSort')">
              <el-button icon="el-icon-menu" size="mini" @click="showType='grid'"></el-button>
            </el-tooltip>
          </el-col>

        </el-row>
      </template>
      <div>
        <a-table v-if="showType === 'grid'" :scroll="{y:finderHeight}" :locale="{emptyText:trans('filesystem.empty')}"  row-key="id" :pagination="false" :row-selection="rowSelection" :columns="tableColumns" :data-source="tableData" :loading="loading" :custom-row="customRow">
          <template #name="{ text , record , index }">
            <div class="filename" @click="changePath(record.path,record.dir)">
              <el-image :src="record.url" :preview-src-list="[record.url]"
                        style="width: 32px;height: 32px;margin-right: 10px">
                <template #error >
                  <el-image :src="fileIcon(record.dir ? '.dir':text)"
                            style="width: 32px;height: 32px;margin-right: 10px">
                    <template #error >
                      <div style="display: flex; align-items: center;"><i class="el-icon-document" style="font-size: 32px"/></div>
                    </template>
                  </el-image>
                </template>
              </el-image>
              {{ text }}
            </div>
          </template>
          <template #action="{ record }" >
            <div v-show="mouseenterIndex == record.id">
              <el-button icon="el-icon-folder-opened" size="mini" round v-if="record.dir" @click="rename(record.path)">{{ trans('filesystem.rename') }}</el-button>
              <el-button icon="el-icon-download" size="mini" round v-else @click="link(record.url)">{{ trans('filesystem.download') }}</el-button>
              <el-button icon="el-icon-delete"  type="danger" size="mini" round @click="del(record.id)">{{ trans('filesystem.del') }}</el-button>
            </div>
          </template>
        </a-table>
        <div v-else class="menuGrid" :style="{height:finderHeight}">
          <el-row v-loading="loading" :gutter="15" style="padding: 0px 20px">

            <el-col class="menuBox" :lg="4" :md="6" :sm="6" :xs="12" v-for="(item,index) in tableData" @mouseenter="mouseenterIndex = item.id" @mouseleave="mouseenterIndex=''" @click="select(item)">

              <div :class="[selectIds.indexOf(item.id) !== -1?'selected':'','item']">
                <div class="zoom_action" v-show="mouseenterIndex == item.id && item.file_type.indexOf('image') > -1">
                  <span class="el-icon-zoom-in" @click.stop="preview(item,index)"></span>
                </div>
                <i class="el-icon-circle-check" v-if="selectIds.indexOf(item.id) !== -1"></i>
                <el-image :src="item.url" :preview-src-list="item.previewList" :ref="el=>setRef(el,index)" fit="contain"
                          style="width: 80px;height: 80px;" @click="changePath(item.path,item.dir)">
                  <template #error >
                    <el-image :src="fileIcon(item.dir ? '.dir':item.name)"
                              style="width: 80px;height: 80px;" @click="changePath(item.path,item.dir )">
                      <template #error >
                        <div style="display: flex; align-items: center;"><i class="el-icon-document" style="font-size: 80px"/></div>
                      </template>
                    </el-image>
                  </template>
                </el-image>

              </div>
              <div>
                <div class="textBox">
                  <el-tooltip :content="item.real_name" placement="bottom-start"  effect="dark"><div class="text">{{ item.real_name }}</div></el-tooltip>
<!--                  <i class="el-icon-edit" style="cursor: pointer"></i>-->
                </div>

              </div>

              <div class="tool" v-show="mouseenterIndex == item.id">
                <el-button icon="el-icon-folder-opened" size="mini" round v-if="item.dir" @click="rename(item.path)">{{ trans('filesystem.rename') }}</el-button>
                <el-button icon="el-icon-download" size="mini" round v-else @click="link(item.url)">{{ trans('filesystem.download') }}</el-button>
                <el-button icon="el-icon-delete"  type="danger" size="mini" round @click="del(item.id)">{{ trans('filesystem.del') }}</el-button>
              </div>
            </el-col>

          </el-row>
        </div>

      </div>
    </el-card>
    <!--分页-->
    <el-pagination style="padding:10px;background: #ffffff" @size-change="handleSizeChange"
                   @current-change="handleCurrentChange"
                   layout="total, sizes, prev, pager, next, jumper"
                   :total="total"
                   :page-size="size"
                   :current-page="page">
    </el-pagination>
  </div>
</template>

<script>
    import {computed, defineComponent, reactive, toRefs, onActivated, watch,ref,onBeforeUpdate,nextTick} from "vue";
    import {deleteArr, fileIcon, unique,link,trans,offsetTop,getPopupEl} from '@/utils'
    import request from '@/utils/axios'
    import {useHttp} from "@/hooks";
    import {ElMessageBox,ElLoading} from 'element-plus';
    export default defineComponent({
        name: "EadminFileSystem",
        props: {
            modelValue: [String, Array],
            data: Array,
            initPath: String,
            upload:Object,
            total:Number,
            limit: {
                type: Number,
                default: 0
            },
            multiple:{
                type:Boolean,
                default: true
            },
            height:{
                type:[String,Boolean,Number],
                default: false
            },
            selection:{
                type:Array,
                default:[]
            },
            display:{
                type: String,
                default: 'grid'
            },
            addParams:{
              type: Object,
              default: {}
            },
            uploadFinder:Boolean,
            sidebar:Object,
            cate:{
              type: Array,
              default: []
            },
        },
        emits: ['update:modelValue','update:selection'],
        setup(props,ctx) {

            const {loading, http} = useHttp()
            const filesystem = ref('')
            const previewImage = []
            const finerCate = computed(()=>{
                const arr = JSON.parse(JSON.stringify(props.cate))
                if(arr.length >0 && !arr[0].id){
                  arr.shift()
                }
                return arr
            })
            let finderParam =  ref({})
            finderParam.value = Object.assign(JSON.parse(JSON.stringify(props.addParams)),props.upload.attribute.params)
            watch(()=>props.addParams,value=>{
              finderParam.value = Object.assign(JSON.parse(JSON.stringify(props.addParams)),props.upload.attribute.params)
            })
            const state = reactive({
                tableColumns: [
                    {
                        title: '文件名',
                        dataIndex: 'real_name',
                        slots: {customRender: 'real_name'},
                    },
                    {
                        title: '大小',
                        width:100,
                        dataIndex: 'size',
                    },
                    {
                      title: '存储',
                      width:100,
                      dataIndex: 'uptype',
                    },
                    {
                        title: '所有者',
                        width:100,
                        dataIndex: 'author',
                    },
                    {
                        title: '修改时间',
                        width:180,
                        dataIndex: 'update_time',
                    },
                    {
                        title: '操作',
                        width:210,
                        align:'right',
                        dataIndex: 'action',
                        slots: {customRender: 'action'}
                    }
                ],
                tableData: [],
                path: props.initPath,
                quickSearch:'',
                mouseenterIndex:'',
                showType:props.display,
                page:1,
                size:100,
                total:props.total,
                selectIds:[],
                selectUrls:[],
                selectCate:'',
                finderHeight: props.height
            })
            loadData()
            watch(()=>state.selectCate,value=>{
                if(value){
                  request({
                    url:'filesystem/moveCate',
                    method:'post',
                    data:{
                      cate_id:value,
                      ids:state.selectIds
                    }
                  }).then(res=>{
                    state.selectCate = ''
                    state.selectIds = []
                    state.selectUrls = []
                    ctx.emit('update:selection',[])
                    loading.value = true
                  })
                }
            })
            watch(() => props.modelValue, (value) => {
                if (value) {
                    loading.value = true
                    ctx.emit('update:modelValue', false)
                }
            })
            watch(() => state.path, value => {
                loading.value = true
            })
            watch(loading, (value) => {
                if (value) {
                    loadData()
                }
            })
            watch(()=>state.showType,value=>{
              autoHeight()
            })
            autoHeight()
            function autoHeight(){
              nextTick(()=>{
                if(!props.height){
                  let height = window.innerHeight - offsetTop(filesystem.value)
                  if(state.showType == 'grid'){
                    height -= 180
                  }else{
                    height -= 130
                  }
                  state.finderHeight  = height + 'px'
                }
              })
            }
            function customRow(record){
                return {
                    onMouseenter:event=>{
                        state.mouseenterIndex = record.id
                    },
                    onMouseleave:event=>{
                        state.mouseenterIndex = ''
                    }
                }
            }
            function loadData() {
                const requestParams = {
                    page:state.page,
                    size:state.size,
                    search: state.quickSearch,
                    path: state.path,
                    ajax_request_data: 'page',
                }
                http({
                    url: '/filesystem',
                    params: Object.assign(requestParams,finderParam.value,{ext:props.upload.attribute.accept})
                }).then(res => {
                    state.tableData = res.data
                    state.total = res.total
                })
            }
            function delSelect() {
                del(state.selectIds)
            }
            //删除
            function del(ids) {
                if(!Array.isArray(ids)){
                  ids = [ids]
                }
                ElMessageBox.confirm('确认删除? 不可恢复操作!','警告',{type:'error'}).then(()=>{
                    http({
                        url:'filesystem/del',
                        method:'delete',
                        data:{
                            ids:ids
                        }
                    }).then(res=>{
                        loadData()
                    })
                })

            }
            //改变目录
            function changePath(path,isDir = true) {
                if (path && isDir) {
                    state.path = path
                }
            }
            //后退
            function back() {
                const arr = state.path.split('/').filter(item => {
                    return item
                })
                const initPath = props.initPath.split('/').filter(item => {
                    return item
                })
                arr.pop()
                if (arr.length >= initPath.length) {
                    state.path = '/' + arr.join('/')
                }
            }
            //新建文件夹
            function mkdir() {
                ElMessageBox.confirm('新建文件夹','',{showInput:true}).then(({value})=>{
                    const {http} = useHttp()
                    http({
                        url:'filesystem/mkdir',
                        method:'post',
                        data:{
                            path:state.path+'/'+value
                        }
                    }).then(res=>{
                        loadData()
                    })
                })
            }
            //重命名文件夹
            function rename(path) {
                ElMessageBox.confirm('重命名文件夹','',{showInput:true}).then(({value})=>{
                    const {http} = useHttp()
                    http({
                        url:'filesystem/rename',
                        method:'post',
                        data:{
                            name:value,
                            path:path,
                        }
                    }).then(res=>{
                        loadData()
                    })
                })
            }
            //选择
            function select(item) {

                if(props.multiple){
                    if(state.selectIds.indexOf(item.id)  === -1){
                        if(props.limit > 0 && state.selectIds.length >= props.limit){
                            return false
                        }
                        state.selectIds.push(item.id)
                        state.selectUrls.push(item.url)
                    }else{
                        deleteArr(state.selectIds,item.id)
                        deleteArr(state.selectUrls,item.url)
                    }
                }else{
                    state.selectIds = [item.id]
                    state.selectUrls = [item.url]
                }
                ctx.emit('update:selection',state.selectUrls)
            }

            //上传进度
            let uploadProgressLoading = null
            function uploadProgress(progress) {
                if(uploadProgressLoading){
                    uploadProgressLoading.setText('上传中 '+progress+'%')
                }else{
                    uploadProgressLoading = ElLoading.service({
                        target:'.filesystem',
                        text:'上传中 '+progress+'%',
                    })
                }
            }
            //上传成功
            function uploadSuccess() {
                loading.value  = true
                if(uploadProgressLoading){
                    uploadProgressLoading.close()
                }
            }

            const setRef = (el,index)=>{
              previewImage[index] = el
            }
            function preview(item,index){
                const image = previewImage[index]
                item.previewList = [item.url]
                image.showViewer = true
                watch(()=>image.showViewer,value=>{
                  if(!value){
                    item.previewList = []
                  }
                })
            }
            const breadcrumb = computed(() => {
                const arr = state.path.split('/').filter(item => {
                    return item
                })
                const initPath = props.initPath.split('/').filter(item => {
                    return item
                })
                const breadcrumb = []
                let paths = []
                let path = ''
                for (let i = 0; i < arr.length; i++) {
                    paths = []
                    for (let k = 0; k <= i; k++) {
                        paths.push(arr[k])
                    }
                    if (paths.length < initPath.length) {
                        path = ''
                    } else {
                        path = '/' + paths.join('/')
                    }
                    breadcrumb.push({
                        name: arr[i],
                        path: path,
                    })
                }
                return breadcrumb
            })

            const rowSelection = computed(() => {
                if (props.hideSelection) {
                    return null
                } else {
                    return {
                        selectedRowKeys: state.selectIds,
                        type:props.multiple?'checkbox':'radio',
                        //当用户手动勾选数据行的 Checkbox 时触发的事件
                        onSelect: (record, selected, selectedRows, nativeEvent) => {
                            const ids = selectedRows.map(item => {
                                return item.id
                            })
                            const urls = selectedRows.map(item => {
                              return item.url
                            })
                            if (selected) {
                                if(props.multiple){
                                    if(props.limit > 0 && state.selectIds.length >= props.limit){
                                        return false
                                    }

                                    state.selectIds = unique(state.selectIds.concat(ids))
                                    state.selectUrls = unique(state.selectUrls.concat(urls))
                                }else{
                                    state.selectIds = ids
                                    state.selectUrls = urls
                                }
                            } else {
                                deleteArr(state.selectUrls, record.url)
                                deleteArr(state.selectIds, record.id)
                            }
                            ctx.emit('update:selection',state.selectUrls)
                        },
                        onSelectAll: (selected, selectedRows, changeRows) => {
                            const ids = selectedRows.map(item => {
                                return item.id
                            })
                            const urls = selectedRows.map(item => {
                              return item.url
                            })
                            if (selected) {
                                if(props.limit > 0 && (state.selectIds.length+ids.length) >= props.limit){
                                    return false
                                }

                                state.selectIds = unique(state.selectIds.concat(ids))
                                state.selectUrls = unique(state.selectUrls.concat(urls))
                            } else {
                                changeRows.map(item => {
                                    deleteArr(state.selectIds, item.id)
                                    deleteArr(state.selectUrls, item.url)
                                })
                            }
                            ctx.emit('update:selection',state.selectUrls)
                        },
                    }
                }
            })

            //分页大小改变
            function handleSizeChange(val) {
                state.page = 1
                state.size = val
                loading.value = true
            }
            //分页改变
            function handleCurrentChange(val) {
                state.page = val
                loading.value = true
            }
            return {
                finderParam,
                handleSizeChange,
                handleCurrentChange,
                customRow,
                link,
                delSelect,
                del,
                uploadProgress,
                uploadSuccess,
                back,
                mkdir,
                select,
                rename,
                breadcrumb,
                changePath,
                loading,
                rowSelection,
                fileIcon,
                previewImage,
                ...toRefs(state),
                filesystem,
                setRef,
                finerCate,
                preview,
                trans
            }
        }
    })
</script>

<style scoped lang="scss">
    .filename {
        display: flex;
        align-items: center;
        height: 35px;
        cursor: pointer;
    }

    .breadcrumb {
        height:28px;
        background-color: #f3f3f3;
        padding: 0 10px;
        display: flex;
        align-items: center;
        border: solid 1px #cccccc;
        overflow: auto;

    }

    .menuGrid{
        width: 100%;
        height: calc(100vh - 280px);
        overflow-y: auto;
        overflow-x: hidden;
    }
    .menuBox .item{
        margin-top: 30px;
        text-align: center;
        cursor: pointer;
        transition: 0.3s all;
        position: relative;
    }
    .menuBox .textBox{
      display: flex;
      align-items: center;
      margin-bottom: 5px;
    }
    .menuBox .text{
        margin: 0 auto;
        text-align: center;
        overflow: hidden;
        white-space:nowrap;
        text-overflow: ellipsis;

    }
    .menuBox .tool{
        display: flex;

    }
    .menuBox .item:hover{
        background: #F8F9FB;
        border: solid 1px #A7D0FB;
        padding: 10px;
        margin-bottom: 5px;
        border-radius: 5px;
        margin-top: 20px;
    }
    .menuGrid .selected{
        margin-top: 20px;
        background: #F8F9FB;
        border: solid 1px #A7D0FB;
        padding: 10px;
        margin-bottom: 5px;
        border-radius: 5px;
    }
    .menuGrid .selected i{
        color: #13ce66;
        position: absolute;
        top: 10px;
        right: 10px;
    }
    .zoom_action {
      position: absolute;
      width: 100%;
      height: 30px;
      cursor: pointer;
      left: 0;
      bottom: 0;
      text-align: center;
      color: #fff;
      font-size: 18px;
      background-color: rgba(0,0,0,.3);
      border-radius: 5%;
      z-index: 999;
      transition: opacity .3s;
    }
</style>
