import app from  './app'
import form from './components/form/form.vue'
import manyItem from './components/form/manyItem.vue'
import dialog from './components/dialog.vue'
import tinymce from './components/tinymce.vue'
import upload from './components/upload.vue'
import drawer from './components/drawer.vue'
import switchs from './components/switchs.vue'
import tree from './components/tree.vue'
import button from './components/button.vue'
import grid from './components/grid/grid.vue'
import icon from './components/icon.vue'
import batchAction from './components/grid/batchAction.vue'
import confirm from './components/confirm.vue'
import eadminErrorPage from './components/EadminErrorPage.vue'
import render from './components/render.vue'
import DropdownItem from './components/dropdown/DropdownItem.vue'
import dropdown from './components/dropdown/dropdown.vue'
import video from './components/video.vue'
import downloadFile from './components/downloadFile.vue'
app.component(form.name,form)
app.component(manyItem.name,manyItem)
app.component(dialog.name,dialog)
app.component(drawer.name,drawer)
app.component(switchs.name,switchs)
app.component(grid.name,grid)
app.component(tree.name,tree)
app.component(button.name,button)
app.component(confirm.name,confirm)
app.component(upload.name,upload)
app.component(icon.name,icon)
app.component(render.name,render)
app.component(tinymce.name,tinymce)
app.component(downloadFile.name,downloadFile)
app.component(video.name,video)
app.component(DropdownItem.name,DropdownItem)
app.component(dropdown.name,dropdown)
app.component(batchAction.name,batchAction)
app.component(eadminErrorPage.name,eadminErrorPage)
