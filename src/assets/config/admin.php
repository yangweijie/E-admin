<?php
/**
 * Created by PhpStorm.
 * User: rocky
 * Date: 2020-04-25
 * Time: 16:33
 */

use Eadmin\model\AdminModel;
use Eadmin\model\SystemAuthMenu;
use Eadmin\model\SystemAuth;
use Eadmin\model\SystemAuthNode;
use Eadmin\model\SystemMenu;
use Eadmin\model\SystemNotice;
use Eadmin\model\SystemAuthData;
use Eadmin\model\SystemFile;
use Eadmin\model\SystemFileCate;
use Eadmin\model\SystemUserAuth;
use Eadmin\model\SystemFieldAuth;

return [
    //超级管理员id
    'admin_auth_id' => 1,
    //token
    'token' => [
        'default' => 'admin',
        //配置列表
        'admin' => [
            //令牌key
            'key' => 'QsoYEClMJsgOSWUBkSCq26yWkApqSuH1',
            //令牌过期时间
            'expire' => 86400,
            //是否唯一登陆
            'unique' => false,
            //系统用户模型
            'model' => \Eadmin\model\AdminModel::class,
            //验证字段
            'auth_field' => [
                'password'
            ]
        ],
        //api使用
        'api' => [
            //密钥
            'key' => env('TOKEN.KEY', 'QsoYEClMJsgOSWUBkSCq26yWkApqSuH3'),
            //过期时间
            'expire' => env('TOKEN.EXPIRE', 7200),
            //是否唯一登录
            'unique' => env('TOKEN.UNIQUE', false),
            //用户模型
            'model' => \app\model\User::class,
            //验证字段
            'auth_field' => [
                'password'
            ],
            //是否调试
            'debug' => env('AUTH.DEBUG', false),
            //调试用户id
            'uid' => env('AUTH.DEBUG_UID', null),
        ]
    ],
    'database' => [
        //用户表
        'user_table' => 'system_user',
        'user_model' => AdminModel::class,
        //权限表
        'auth_table' => 'system_auth',
        'auth_model' => SystemAuth::class,
        //权限关联表
        'auth_node_table' => 'system_auth_node',
        'auth_node_model' => SystemAuthNode::class,
        //权限关联表
        'user_auth_table' => 'system_user_auth',
        'user_auth_model' => SystemUserAuth::class,
        //菜单
        'menu_table' => 'system_menu',
        'menu_model' => SystemMenu::class,
        //菜单权限关联表
        'auth_menu_table' => 'system_auth_menu',
        'auth_menu_model' => SystemAuthMenu::class,
        //通知表
        'notice_table' => 'system_notice',
        'notice_model' => SystemNotice::class,
        //数据权限表
        'auth_data_table' => 'system_auth_data',
        'auth_data_model' => SystemAuthData::class,
        //字段权限表
        'field_auth_table' => 'system_field_auth',
        'field_auth_model' => SystemFieldAuth::class,
        //文件
        'file_table' => 'system_file',
        'file_model' => SystemFile::class,
        'file_cate_table' => 'system_file_cate',
        'file_cate_model' => SystemFileCate::class,
    ],

    //权限模块
    'authModule' => [
        'admin' => '系统模块',
    ],
    //上传filesystem配置中的disk, local本地,qiniu七牛云,oss阿里云
    'uploadDisks' => 'local',

    //地图
    'map' => [
        'default' => 'amap',
        //高德地图key
        'amap' => [
            'api_key' => ''
        ],
    ],
    //开关顶部菜单
    'topMenu' => true,
    //开关标签菜单（缓存）
    'tagMenu' => true,
    //主题
    'theme' => [
        //主题皮肤
        //light primary
        'skin' => 'light',
        //主题色
        'color' => '#409EFF',
    ],
    //多语言设置
    'lang' => [
        //开启多语言
        'enable' => false,
        //支持语言列表
        'list' => [
            'zh-cn' => '中文',
            'en' => 'English',
        ]
    ],
    //跨域
    'cross'=>[
        //header
        'allow_headers'=>['corp_id']
    ]
];
