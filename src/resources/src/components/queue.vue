<template>
  <span @click="clickHandel"><slot></slot></span>
  <teleport to="body">
    <div class="el-notification right" role="alert" style="bottom: 20px;" v-if="visible">
      <div class="el-notification__group" style="width: 100%">
        <div style="display: flex;align-items: center">
          <h2 class="el-notification__title" v-html="title"></h2>
          <span class="time-text" v-if="status != 'success'">剩余<span class="time">{{remain_time}}</span></span>
          <span class="time-text" v-if="file"><a target="_blank" :href="file">下载文件</a></span>
        </div>
        <div class="el-notification__content"><el-progress :percentage="progress" :status="status"></el-progress></div>
        <div class="el-notification__closeBtn el-icon-close" @click="close"></div></div>
    </div>
  </teleport>
</template>

<script>
import {defineComponent, reactive, toRefs,onBeforeUnmount,onDeactivated} from "vue";
import {ElMessageBox} from 'element-plus';
import {ElMessage} from 'element-plus'
import request from '@/utils/axios'
import {trans} from '@/utils'
export default defineComponent({
  name: "EadminQueue",
  props: {
    title: String,
    url:String,
    confirm:String,
    params: {
      type:Object,
      default:{}
    },
  },
  setup(props) {
    console.log(props)
    const state = reactive({
      progress: 0,
      status: '',
      file: '',
      remain_time: '',
      visible:false,
    })
    let timer = null
    let alert = 0
    function clickHandel(){
      if(props.confirm){
        ElMessageBox.confirm(props.confirm, '提示', {
            type:'warning',
            confirmButtonText:'确定',
            cancelButtonText:'取消',
        }).then(()=>{
          exec()
        })
      }else{
        exec()
      }
    }
    function exec(){
      alert = 0
      request({
        url:props.url,
        method:'post',
        data:props.params
      }).then(res=>{
        if(timer){
          state.progress=0
          state.status= ''
          state.file= ''
          state.remain_time= ''
          clearInterval(timer)
        }
        state.visible = true
        timer = setInterval(() => {
          queue(res.data)
        }, 500)
      })

    }
    onBeforeUnmount(()=>{
      close()
    })
    onDeactivated(()=>{
      close()
    })
    function close(){
      state.visible = false
      clearInterval(timer)
    }
    function queue(id){
      request({
        url: 'queue/progress',
        params: {
          id: id
        }
      }).then(result=>{
        if(result.data.status === 0 && alert === 0){
          alert = 1
          ElMessage.warning(trans('queue_message'))
        }
        state.progress = result.data.progress
        state.remain_time = result.data.remain_time
        if(result.data.status == 4){
          state.status = 'exception'
          clearInterval(timer)
        }
        if(result.data.status == 3){
          clearInterval(timer)
          state.file = result.data.history.slice(-2)[0].message
          state.status = 'success'
        }
      })
    }
    return {
      clickHandel,
      close,
      ...toRefs(state)
    }
  }
})
</script>

<style scoped>
.time-text{
  font-size: 12px;
  margin-left: 10px;
  align-self: flex-end;
}
.time{
  font-size: 12px;
  color: red;
  margin-left:2px;
}
</style>
