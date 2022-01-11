<template>
    <el-submenu v-if="menu.children" :index="menu.id+''">
        <template #title>
            <i :class="menu.icon" v-if="menu.icon"></i><span>{{menu.name}}</span>
        </template>
        <template v-for="item in menu.children" :key="item.id">
            <menu-item :menu="item"></menu-item>
        </template>
    </el-submenu>
    <el-menu-item :index="menu.id+''" v-else>
        <i :class="menu.icon" v-if="menu.icon"></i>
        <span>{{menu.name}}</span>
    </el-menu-item>
</template>
<script>

    import {defineComponent,watch,ref} from 'vue'

    export default defineComponent({
        name: "menuItem",

        props: {
            menu: [Object, Array]
        },
        setup(props) {

        }
    })
</script>

<style scoped>

</style>
