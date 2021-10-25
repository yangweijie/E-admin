<?php
return [
    'add' => 'Add',
    'title' => 'System menu management',
    'fields' => [
        'top' => 'Top menu',
        'pid' => 'Superior menu',
        'name' => 'name',
        'url' => 'url',
        'icon' => 'icon',
        'sort' => 'sort',
        'status' => 'status',
        'super_status' => 'Super administrator status',
    ],
    'options' => [
        'admin_visible' => [
            [1 => 'show'],
            [0 => 'hidden']
        ]
    ],
    'titles' => [
        'index' => 'Index',
        'system_manage' => 'System management',
        'system_menu_manage' => 'System menu management',
        'system_config' => 'System configuration',
        'system_param_config' => 'System parameter configuration',
        'system_user_manage' => 'System user management',
        'auth_manage' => 'Authority management',
        'access_auth_manage' => 'Access rights management',
        'backup' => 'Database backup',
    ]
];
