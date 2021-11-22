<template>
  <span @click="exec"><slot></slot></span>
  <teleport to="body">
  <div class="el-notification right" role="alert" style="bottom: 20px;" v-if="visible">
    <div class="el-notification__group" style="width: 100%">
      <h2 class="el-notification__title" v-html="title"></h2>
      <div class="el-notification__content"><el-progress :percentage="progress" :status="status"></el-progress></div>
      <div class="el-notification__closeBtn el-icon-close" @click="close"></div></div>
  </div>
  </teleport>
</template>

<script>
import {defineComponent, reactive, toRefs,onBeforeUnmount,onDeactivated} from "vue";
import request from '@/utils/axios'
export default defineComponent({
  name: "EadminQueue",
  props: {
    queueId: [String,Number],
    title: String,
  },
  setup(props) {
    const state = reactive({
      progress: 0,
      status: '',
      visible:false,
    })
    let timer = null
    function exec(){
      if(timer){
        state.progress=0
        state.status= ''
        clearInterval(timer)
      }
      state.visible = true
      timer = setInterval(() => {
        queue()
      }, 500)
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
    function queue(){
      request({
        url: 'queue/progress',
        params: {
          id: props.queueId
        }
      }).then(result=>{
        state.progress = result.data.progress
        if(result.data.status == 4){
          state.status = 'exception'
          clearInterval(timer)
        }
        if(result.data.status == 3){
          clearInterval(timer)
          state.status = 'success'
        }
      })
    }
    return {
      exec,
      close,
      ...toRefs(state)
    }
  }
})
</script>

<style scoped>

</style>
