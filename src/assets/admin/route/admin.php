<?php
use think\facade\Route;
Route::get('/',function (){
    cookie('multi-app',app('http')->getName());
    return view('/index',['web_name'=>sysconf('web_name')]);
});
