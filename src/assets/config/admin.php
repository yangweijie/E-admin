<?php
/**
 * Created by PhpStorm.
 * User: rocky
 * Date: 2020-04-25
 * Time: 16:33
 */

use Eadmin\model\AdminModel;

return [
    //超级管理员id
    'admin_auth_id' => 1,
    //token
    'token'=>[
        'default'=>'admin',
        //配置列表
        'admin'=>[
            //令牌key
            'key' => 'QsoYEClMJsgOSWUBkSCq26yWkApqSuH1',
            //令牌过期时间
            'expire' => 86400,
            //是否唯一登陆
            'unique' => false,
            //系统用户模型
            'model' => \Eadmin\model\AdminModel::class,
            //验证字段
            'auth_field'=>[
                'password'
            ]
        ],
        //api使用
        'api'=>[
            //密钥
            'key' => env('TOKEN.KEY','QsoYEClMJsgOSWUBkSCq26yWkApqSuH3'),
            //过期时间
            'expire' => env('TOKEN.EXPIRE',7200),
            //是否唯一登录
            'unique' => env('TOKEN.UNIQUE',false),
            //用户模型
            'model' => \app\model\User::class,
            //验证字段
            'auth_field'=>[
                'password123'
            ],
            //是否调试
            'debug' => env('AUTH.DEBUG',false),
            //调试用户id
            'uid' => env('AUTH.DEBUG_UID',null),
        ]
    ],
    //系统用户表
    'system_user_table' => 'system_user',
    //权限模块
    'authModule' => [
        'admin' => '系统模块',
    ],
    //上传filesystem配置中的disk, local本地,qiniu七牛云,oss阿里云
    'uploadDisks' => 'local',

    //地图
    'map'=>[
        'default' => 'amap',
        //高德地图key
        'amap'=>[
          'api_key'=>'7b89e0e32dc5eb583c067edb5491c4d3'
        ],
    ],
    //开关顶部菜单
    'topMenu'=> true,
    //开关标签菜单（缓存）
    'tagMenu'=> true,
    //主题
    'theme'=>[
        //主题皮肤
        //light primary
        'skin'=>'light',
        //主题色
        'color'=>'#409EFF',
    ],
    //多语言设置
    'lang'=>[
        //开启多语言
        'enable'=>false,
        //支持语言列表
        'list'=>[
            'zh-cn'=>'中文',
            'en'=>'English',
        ]
    ]
];
