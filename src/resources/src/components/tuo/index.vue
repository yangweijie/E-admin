<template>
    <el-container style="height: 100%;background: #FFFFFF">
        <el-aside width="300px">
            <draggable
                    class="components-draggable"
                    :list="componentList"
                    :group="{ name: 'componentsGroup', pull: 'clone', put: false }"
                    :sort="false"
                    :clone="clone"
                    item-key="label"
            >
                <template #item="{element}">
                    <div class="components-item">
                        <div class="components-body">
                            {{ element.label }}
                        </div>
                    </div>
                </template>
            </draggable>
        </el-aside>
        <el-main>
            <el-form class="drawing" v-bind="formAttr">
            <draggable :list="generateComponentList"
                       group="componentsGroup"
                       item-key="id"
                       animation="300"
                       class="drawing"
                       @add="add"
                       @choose="choose"
            >
                <template #item="{element,index}">
                    <div :class="['drawing-item',chooseIndex==index?'active':'']">
                        <render :data="element.formItem" :proxy-data="proxyData"></render>
                    </div>
                </template>
            </draggable>
            </el-form>
        </el-main>
        <el-aside width="350px">
            <el-form label-width="100px" size="small">
                <el-tabs stretch>
                    <el-tab-pane label="组件属性">
                        <el-form-item label="标题" v-if="componentItem.formItem.attribute">
                            <el-input v-model="componentItem.formItem.attribute.label" placeholder="请输入标题"></el-input>
                        </el-form-item>
                        <el-form-item label="占位提示" v-if="isAttr()">
                            <el-input v-model="componentItem.component.attribute.placeholder" placeholder="请输入占位提示"></el-input>
                        </el-form-item>
                        <el-form-item label="默认值" v-if="isAttr()">
                            <el-input v-model="proxyData[componentItem.component.modelBind.modelValue]"  placeholder="请输入默认值"></el-input>
                        </el-form-item>
                        <el-row v-if="isAttr('type',['text','textarea'])">
                            <el-col :span="12">
                                <el-form-item label="长度限制">
                                    <el-input v-model="componentItem.component.attribute.maxlength" placeholder="请输入长度限制"></el-input>
                                </el-form-item>

                            </el-col>
                            <el-col :span="12">
                                <el-form-item label="字数统计">
                                    <el-switch v-model="componentItem.component.attribute.showWordLimit" ></el-switch>
                                </el-form-item>
                            </el-col>
                        </el-row>
                        <el-form-item label="禁用" v-if="isAttr()">
                            <el-switch v-model="componentItem.component.attribute.disabled" ></el-switch>
                        </el-form-item>
                        <el-form-item label="只读" v-if="isAttr()">
                            <el-switch v-model="componentItem.component.attribute.readonly" ></el-switch>
                        </el-form-item>
                        <el-form-item label="可清空" v-if="isAttr()">
                            <el-switch v-model="componentItem.component.attribute.clearable" ></el-switch>
                        </el-form-item>
                        <el-form-item label="最大值" v-if="isAttr('type','number')">
                            <el-input v-model="componentItem.component.attribute.max" placeholder="请输入设置最大值"></el-input>
                        </el-form-item>
                        <el-form-item label="最小值" v-if="isAttr('type','number')">
                            <el-input v-model="componentItem.component.attribute.min" placeholder="请输入设置最小值"></el-input>
                        </el-form-item>
                        <el-form-item label="数字间隔" v-if="isAttr('type','number')">
                            <el-input v-model="componentItem.component.attribute.step" placeholder="请输入设置间隔"></el-input>
                        </el-form-item>

                    </el-tab-pane>
                    <el-tab-pane label="表单属性">
                        <el-form-item label="表单尺寸">
                            <el-radio-group v-model="formAttr.size">
                                <el-radio-button label="medium" >中等</el-radio-button>
                                <el-radio-button label="small">较小</el-radio-button>
                                <el-radio-button label="mini" >迷你</el-radio-button>
                            </el-radio-group>
                        </el-form-item>
                        <el-form-item label="对齐方式">
                            <el-radio-group v-model="formAttr.labelPosition">
                                <el-radio-button label="right">左对齐</el-radio-button>
                                <el-radio-button label="left">右对齐</el-radio-button>
                                <el-radio-button label="top">顶部对齐</el-radio-button>
                            </el-radio-group>
                        </el-form-item>
                        <el-form-item label="标签宽度">
                            <el-input v-model="formAttr.labelWidth"></el-input>
                        </el-form-item>
                    </el-tab-pane>
                </el-tabs>
            </el-form>
        </el-aside>
    </el-container>
</template>

<script>
    import draggable from 'vuedraggable'
    import {defineComponent,reactive,toRefs,watch} from "vue";
    import {randomCoding,forEach} from '@/utils/index'
    export default defineComponent({
        name: "index",
        components: {
            draggable,
        },
        setup(){
            const proxyData = reactive({})
            const state = reactive({
                //左边组件
                componentList:[
                    {
                        label: "单行文本",
                        component:generateComponent('ElInput',{type:'text'})
                    },
                    {
                        label: "数字输入框",
                        component:generateComponent('ElInput',{type:'number'})
                    },
                    {
                        label: "密码框",
                        component:generateComponent('ElInput',{type:'password'})
                    },
                    {
                        label: "多行文本",
                        component:generateComponent('ElInput',{type:'textarea'})
                    },
                    {
                        label: "编辑器",
                        component:generateComponent('EadminEditor',{type:'textarea'})
                    },
                ],
                //中间生成组件
                generateComponentList:[],
                //当前选中中间的组件
                componentItem:{
                    formItem:{},
                    component:null,
                },
                //表单属性
                formAttr:{
                    size:'medium',
                    labelPosition:'right',
                    labelWidth:'100px',
                },
                //当前选中组件index
                chooseIndex:-1,
            })
            watch(()=>state.chooseIndex,value=>{
                state.componentItem = state.generateComponentList[value]
            })
            watch(()=>state.componentItem.component,value => {
                state.componentItem.id = randomCoding(20)
            },{deep:true})
            watch(()=>state.componentItem.formItem,value => {
                state.componentItem.id = randomCoding(20)
            },{deep:true})
            function generateComponent(name,attribute={},modelBind ={},content=[]) {
                return {
                    name: name,
                    attribute: attribute,
                    bindAttribute:modelBind,
                    modelBind:modelBind,
                    bind:[],
                    where:{
                        AND:[],
                        OR:[],
                    },
                    map:{
                        attribute: []
                    },
                    content:content,
                    directive:[],
                    event: []
                }
            }
            function clone(e) {
                const cloneJson = JSON.parse(JSON.stringify(e.component))
                const modelValue = randomCoding(20)
                cloneJson.bindAttribute.modelValue = modelValue
                cloneJson.modelBind.modelValue = modelValue
                const formItem = generateComponent('ElFormItem',{label:'标题'})
                formItem.content.default = [cloneJson]
                return {
                    id:modelValue,
                    formItem:formItem,
                    component:cloneJson
                };
            }
            function choose(e) {
                state.chooseIndex = e.oldIndex
            }
            function add(e) {
                state.chooseIndex = e.newIndex
            }
            function isAttr(attr='',value='') {
                if(!state.componentItem.component){
                    return false
                }
                if(attr){
                    if(Array.isArray(value)){
                        if(value.indexOf(state.componentItem.component.attribute[attr]) == -1){
                            return false
                        }
                    }else{
                        if(state.componentItem.component.attribute[attr] != value){
                            return false
                        }
                    }
                }
                return true
            }
            return {
                ...toRefs(state),
                proxyData,
                add,
                clone,
                choose,
                isAttr,
            }
        }
    })
</script>

<style scoped>
.drawing{
    height:100%;
}
.components-draggable .components-item {
    display: inline-block;
    width: 48%;
    margin: 1%;
}
.components-body {
    padding: 8px 10px;
    background: #f6f7ff;
    font-size: 12px;
    cursor: move;
    border: 1px dashed #f6f7ff;
    border-radius: 3px;
}
.drawing-item{
    cursor: move;
    position: relative;
    padding: 7.5px;

}
.active{
    background: #f6f7ff;
}
.drawing-item:hover{
    cursor: move;
    background: #f6f7ff;
}
</style>
