(window["webpackJsonp"]=window["webpackJsonp"]||[]).push([["chunk-2d219fb9"],{ba26:function(e,t,c){"use strict";c.r(t);c("ac1f");var a=c("f2bf"),n=Object(a["withScopeId"])("data-v-a659aaba");Object(a["pushScopeId"])("data-v-a659aaba");var o={key:0,class:"el-notification right",role:"alert",style:{bottom:"20px"}},r={class:"el-notification__group",style:{width:"100%"}},s={class:"el-notification__content"};Object(a["popScopeId"])();var l=n((function(e,t,c,n,l,i){var u=Object(a["resolveComponent"])("el-progress");return Object(a["openBlock"])(),Object(a["createBlock"])(a["Fragment"],null,[Object(a["createVNode"])("span",{onClick:t[1]||(t[1]=function(){return e.exec&&e.exec.apply(e,arguments)})},[Object(a["renderSlot"])(e.$slots,"default",{},void 0,!0)]),(Object(a["openBlock"])(),Object(a["createBlock"])(a["Teleport"],{to:"body"},[e.visible?(Object(a["openBlock"])(),Object(a["createBlock"])("div",o,[Object(a["createVNode"])("div",r,[Object(a["createVNode"])("h2",{class:"el-notification__title",innerHTML:e.title},null,8,["innerHTML"]),Object(a["createVNode"])("div",s,[Object(a["createVNode"])(u,{percentage:e.progress,status:e.status},null,8,["percentage","status"])]),Object(a["createVNode"])("div",{class:"el-notification__closeBtn el-icon-close",onClick:t[2]||(t[2]=function(){return e.close&&e.close.apply(e,arguments)})})])])):Object(a["createCommentVNode"])("",!0)]))],64)})),i=c("5530"),u=c("78b1"),b=Object(a["defineComponent"])({name:"EadminQueue",props:{title:String,url:String},setup:function(e){var t=Object(a["reactive"])({progress:0,status:"",visible:!1}),c=null;function n(){Object(u["a"])(e.url).then((function(e){c&&(t.progress=0,t.status="",clearInterval(c)),t.visible=!0,c=setInterval((function(){r(e)}),500)}))}function o(){t.visible=!1,clearInterval(c)}function r(e){Object(u["a"])({url:"queue/progress",params:{id:e}}).then((function(e){t.progress=e.data.progress,4==e.data.status&&(t.status="exception",clearInterval(c)),3==e.data.status&&(clearInterval(c),t.status="success")}))}return Object(a["onBeforeUnmount"])((function(){o()})),Object(a["onDeactivated"])((function(){o()})),Object(i["a"])({exec:n,close:o},Object(a["toRefs"])(t))}});b.render=l,b.__scopeId="data-v-a659aaba";t["default"]=b}}]);