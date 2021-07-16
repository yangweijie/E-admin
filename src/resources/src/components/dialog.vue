<template>
    <component :is="dialog" v-model="visible" v-bind="$attrs">
        <template #title>
                        <slot name="title"></slot>
                    </template>
                    <slot></slot>
           <render :data="content" :slot-props="slotProps" @success="hide"></render>
    </component>
    <span @click.stop="open">
        <slot name="reference"></slot>
    </span>
</template>

<script>
    import {defineComponent, watch,computed} from "vue";
    import render from '@/components/render.vue'
    import {useVisible} from '@/hooks'
    import {ElMessage} from "element-plus";
    export default defineComponent({
        name: "EadminDialog",
        components: {
            render,
        },
        inheritAttrs: false,
        props: {
            modelValue: {
                type: Boolean,
                default: false,
            },
            show:Boolean,
            url: String,
            params:Object,
            addParams:{
                type:Object,
                default:{}
            },
            gridBatch:Boolean,
            //请求method
            method: {
                type: String,
                default: 'get'
            },
            slotProps:Object
        },
        emits: ['update:modelValue','update:show'],
        setup(props, ctx) {
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
                ctx.emit('update:show',value)
            })
            function open(){
                if(props.gridBatch  && props.addParams.eadmin_ids.length == 0){
                    return ElMessage('请勾选操作数据')
                }

                http(props)
            }
            const dialog = computed(()=>{
                if(visible.value ){
                    return 'ElDialog'
                }else{
                    return null
                }
            })
            return {
                dialog,
                hide,
                content,
                visible,
                open,
            }
        }
    })
</script>

<style scoped>

</style>
