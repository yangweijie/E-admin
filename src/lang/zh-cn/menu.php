<?php
return [
    'add'=>'添加菜单',
    'title'=>'系统菜单管理',
    'fields'=>[
        'top'=>'顶级菜单',
        'pid'=>'上级菜单',
        'name'=>'菜单名称',
        'url'=>'菜单链接',
        'icon'=>'菜单图标',
        'sort'=>'排序',
        'status'=>'状态',
        'super_status'=>'超级管理员状态',
    ],
    'options'=>[
        'admin_visible'=>[
            [1 => '显示'], 
            [0 => '隐藏']
        ]
    ]
];
