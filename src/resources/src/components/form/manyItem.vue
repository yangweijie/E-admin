<template>
    <el-divider content-position='left' v-if="title">{{title}}</el-divider>
    <div v-for="(item,index) in value">
        <slot :row="item" :$index="index" :prop-field="field" :validator="$attrs.validator"></slot>
        <el-form-item v-if="!disabled">
            <el-button size="mini" v-if="value.length - 1 == index" type='primary' plain @click="add">新增</el-button>
            <el-button size="mini" type='danger' v-show='value.length > 0' @click="remove(index)">移除</el-button>
            <el-button size="mini" @click="handleUp(index)" v-show='value.length > 1 && index > 0'>上移</el-button>
            <el-button size="mini" v-show='value.length > 1 && index < value.length-1' @click="handleDown(index)">下移</el-button>
        </el-form-item>
        <el-divider></el-divider>
    </div>
    <el-form-item v-if="value.length == 0 && !disabled">
         <el-button size="mini" type='primary' plain @click="add">新增</el-button>
    </el-form-item>
</template>

<script>
    import {defineComponent,reactive,watch} from "vue";
    export default defineComponent({
        name: "EadminManyItem",
        props: {
            title:String,
            modelValue: Array,
            field:String,
            manyData:Object,
            disabled:Boolean,
        },
        emits:['update:modelValue'],
        setup(props,ctx){
            const value = reactive(props.modelValue)
            watch(value,(val)=>{
                ctx.emit('update:modelValue',val)
            })
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
            return {
                value,
                add,
                remove,
                handleUp,
                handleDown,
            }
        }
    })
</script>

<style scoped>

</style>
