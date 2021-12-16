<template>
    <el-divider content-position='left' v-if="title && !table">{{title}}</el-divider>
    <el-form-item :label="title" v-if="table">
        <a-table row-key="id" v-if="value.length > 0" :data-source="value" size="small" :pagination="false" :custom-row="customRow" class="manyItemEadminTable">
            <a-table-column v-for="(column,index) in columns" :data-index="column.prop">
                <template #title>
                    <render :data="column.title"></render>
                    <i class="el-icon-edit-outline" style="cursor: pointer" v-if="column.component.content.default[0].name != 'EadminDisplay' && !column.component.content.default[0].attribute.disabled" @click="openBatch(index)"></i>
                    <el-dialog
                        :title="trans('spec.batch')"
                        v-model="dialogs[index].dialog"
                        width="30%">
                      <render :data="column.component.content.default[0]" v-model="dialogs[index].value"></render>
                      <template #footer>
                        <el-button size="small" @click="dialogs[index] = false">{{trans('spec.cancel')}}</el-button>
                        <el-button size="small" type="primary" @click="()=>{batch(index,column.prop,dialogs[index].value)}">
                          {{ trans('spec.save') }}</el-button>
                      </template>
                    </el-dialog>
                </template>
                <template #default="{ record , index}">
                    <render :slot-props="propParam(record,index)" :data="column.component"></render>
                </template>
            </a-table-column>
            <a-table-column :width="70" v-if="!disabled">
                <template #default="{ record , index}">
                    <el-space size="5">
                        <i class="el-icon-arrow-up" style="cursor: pointer;" @click="handleUp(index)" v-show='hoverIndex == index && value.length > 1 && index > 0'></i>
                        <i class="el-icon-arrow-down" style="cursor: pointer;" v-show='hoverIndex == index && value.length > 1 && index < value.length-1' @click="handleDown(index)"></i>
                        <i class="el-icon-error" style="cursor: pointer;color: red" v-show='hoverIndex == index && value.length > 0' @click="remove(index)"></i>
                    </el-space>
                 </template>
            </a-table-column>
        </a-table>
        <div style="margin-top: 5px">
          <el-button size="mini" type='primary' plain @click="add" v-if="!disabled && (limit == 0 || limit > value.length)">{{ trans('manyItem.add') }}</el-button>
          <el-button size="mini" type='warning' @click="clear" v-if="!disabled && (limit == 0 || limit > value.length)">{{ trans('manyItem.clear') }}</el-button>
        </div>
    </el-form-item>
    <div class="hasMany" v-else>
        <template v-for="(item,index) in value">
            <slot :row="item" :$index="index" :prop-field="field" :validator="$attrs.validator"></slot>
            <el-form-item v-if="!disabled">
                <el-button size="mini" v-if="value.length - 1 == index && (limit == 0 || limit > value.length)" type='primary' plain @click="add">{{ trans('manyItem.add') }}</el-button>
                <el-button size="mini" type='danger' v-show='value.length > 0' @click="remove(index)">{{ trans('manyItem.remove') }}</el-button>
                <el-button size="mini" @click="handleUp(index)" v-show='value.length > 1 && index > 0'>{{ trans('manyItem.up') }}</el-button>
                <el-button size="mini" v-show='value.length > 1 && index < value.length-1' @click="handleDown(index)">{{ trans('manyItem.down') }}</el-button>
                <el-button size="mini" type='warning' v-if="value.length - 1 == index && (limit == 0 || limit > value.length)" @click="clear">{{ trans('manyItem.clear') }}</el-button>
            </el-form-item>
            <el-divider style="margin:10px 0"></el-divider>
        </template>
        <el-form-item v-if="value.length == 0 && !disabled">
            <el-button size="mini" type='primary' plain @click="add">{{ trans('manyItem.add') }}</el-button>
        </el-form-item>
    </div>
</template>

<script>
    import {trans} from '@/utils'
    import {defineComponent,reactive,watch,ref} from "vue";
    export default defineComponent({
        name: "EadminManyItem",
        inheritAttrs:false,
        props: {
            title:String,
            modelValue: Array,
            field:String,
            limit:{
                type:Number,
                default:0,
            },
            columns: Array,
            manyData:Object,
            disabled:Boolean,
            table:Boolean,
            slotProps:{
              type:Object,
              default:{},
            },
        },
        emits:['update:modelValue'],
        setup(props,ctx){
            const value = reactive(props.modelValue)
            const hoverIndex = ref(-1)
            const dialogs = ref([])
            props.columns.forEach(item=>{
              dialogs.value.push({
                  dialog:false,
                  value:'',
              })
            })
            watch(value,(val)=>{
                ctx.emit('update:modelValue',val)
            })
            function propParam(record,index){
              let param = { row:record ,$index:index ,propField:props.field,validator:ctx.attrs.validator}
              if(props.slotProps.propField && props.slotProps.row){
                param.parentIndex = props.slotProps.$index
                param.parentPropField = props.slotProps.propField
              }
              return param
            }
            // 上移
            function handleUp (index) {
                const len = value[index - 1]
                value[index - 1] = value[index]
                value[index] = len
            }
            // 下移
            function handleDown (index) {
                const len = value[index + 1]
                value[index + 1] = value[index]
                value[index] = len
            }
            //添加元素
            function add(){
                value.push({...props.manyData})
            }
            //移除元素
            function remove(index){
                value.splice(index, 1)
            }
            //清空
            function clear(){
                value.splice(0)
            }
            function batch(index,field,val) {
              dialogs.value[index].dialog = false
              value.forEach((item,index)=>{
                value[index][field] = val
              })

            }
            function openBatch(index){

                dialogs.value[index].dialog = true
            }
            function customRow(record,index) {
                return {
                    onMouseenter: (event) => {
                        hoverIndex.value = index
                    },
                    onMouseleave: (event) => {
                        hoverIndex.value = -1
                    }
                };
            }
            return {
                propParam,
                openBatch,
                batch,
                clear,
                trans,
                value,
                add,
                remove,
                handleUp,
                handleDown,
                customRow,
                hoverIndex,
                dialogs,
            }
        }
    })
</script>

<style>
.manyItemEadminTable .ant-table{
    border-left: 1px solid #ededed;
    border-right: 1px solid #ededed;
    clear: none;
}
.manyItemEadminTable .el-form-item{
  margin-bottom: 0;
}
.hasMany .el-col .el-form-item{
  margin-bottom: 0;
}

</style>
