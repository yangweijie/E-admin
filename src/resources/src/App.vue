<template>
  <el-config-provider :locale="locale">
    <router-view/>
    <eadmin-error-page/>
  </el-config-provider>
</template>

<script>
    import { store,action} from '@/store'
    import { ElConfigProvider } from 'element-plus'
    import EadminErrorPage from './layout/EadminErrorPage.vue'
    import {defineComponent, inject,computed} from "vue";
    import resize from "@/hooks/use-resize"
    export default defineComponent({
        name: 'App',
        components:{
            EadminErrorPage,
            ElConfigProvider
        },
        setup() {
            const state = inject(store)
            const locale = computed(()=>{
              return state.lang
            })
            resize()
            return {
              locale
            }
        }
    })
</script>
