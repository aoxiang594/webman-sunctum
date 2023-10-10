<?php
return [
    'enable' => true,
    'guard'  => [
        'user' => [
            'key'   => 'id',
            'num'   => 0, //-1为不限制终端数量 0为只支持一个终端在线 大于0为同一账号同终端支持数量 建议设置为1 则同一账号同终端在线1个
            'model' => app\model\User::class,
        ],
    ],
];