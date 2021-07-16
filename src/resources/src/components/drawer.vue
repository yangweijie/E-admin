<template>
    <component :is="drawer" v-model="visible" v-bind="$attrs">
        <template #title>
            <slot name="title"></slot>
        </template>
        <slot></slot>
        <render :data="content" :slot-props="slotProps" @success="hide"></render>
    </component>
    <span @click="open">
        <slot name="reference"></slot>
    </span>
</template>

<script>
    import {computed, defineComponent, watch} from "vue";
    import {useVisible, useHttp} from '@/hooks'
    import render from '@/components/render.vue'
    import {ElMessage} from "element-plus";
    export default defineComponent({
        name: "EadminDrawer",
        components: {
            render,
        },
        inheritAttrs: false,
        gridBatch:Boolean,
        props: {
            modelValue: {
                type: Boolean,
                default: false,
            },
            url: String,
            params:Object,
            addParams:{
                type:Object,
                default:{}
            },
            //请求method
            method: {
                type: String,
                default: 'get'
            },
            slotProps:Object
        },
        emits:['update:modelValue','update:show'],
        setup(props,ctx){
            if(ctx.attrs.eadmin_popup){
                props.slotProps.eadmin_popup = ctx.attrs.eadmin_popup
            }

            const {visible,hide,useHttp} = useVisible(props,ctx)
            const {content,http} = useHttp()
            watch(()=>props.modelValue,(value)=>{
                if(visible.value && !value){
                    hide()
                }
            })
            watch(()=>props.show,(value)=>{
                if(value){
                    open()
                }
            })
            function open(){
                if(props.gridBatch  && props.addParams.eadmin_ids.length == 0){
                    return ElMessage('请勾选操作数据')
                }

                http(props)
            }
            const drawer = computed(()=>{
                if(visible.value ){
                    return 'ElDrawer'
                }else{
                    return null
                }
            })
            return {
                drawer,
                open,
                visible,
                content,
                hide
            }
        }
    })
</script>

<style scoped>

</style>
