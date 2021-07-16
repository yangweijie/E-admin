import {ref,watch} from "vue";
import request from '@/utils/axios'
const useVisible = function(props:object,ctx:any){
    const visible = ref(false)
    watch(visible,(value)=>{
        ctx.emit('update:modelValue',value)
        ctx.emit('update:show',value)
    })
    function show(callback:any){
        visible.value = true
        if(callback){
            callback()
        }
    }
    function hide(callback:any){
        visible.value = false
        if(callback){
            callback()
        }
    }
    function useHttp() {
        const content = ref('')
        const http = function (props) {
            return new Promise((resolve, reject) =>{
                if (props.url) {
                    request({
                        url: props.url,
                        params:Object.assign(props.params,props.addParams),
                        method:props.method
                    }).then((res:any)=> {
                        resolve(res)
                        visible.value = true
                        content.value = res
                    }).catch((res:any)=>{
                        reject(res)
                    })
                }else{
                    visible.value = true
                    resolve()
                }
            })
        }
        return {content,http}
    }
    return {visible,show,hide,useHttp}
}
export default useVisible

