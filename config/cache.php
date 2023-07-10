<?php

use think\facade\Env;

// +----------------------------------------------------------------------
// | 缓存设置
// +----------------------------------------------------------------------

return [
    // 默认缓存驱动
    'default' => Env::get('cache.driver', 'file'),

    // 缓存连接方式配置
    'stores'  => [
        'file' => [
            // 驱动方式
            'type'       => 'File',
            // 缓存保存目录
            'path'       => '',
            // 缓存前缀
            'prefix'     => '',
            // 缓存有效期 0表示永久缓存
            'expire'     => 0,
            // 缓存标签前缀
            'tag_prefix' => 'tag:',
            // 序列化机制 例如 ['serialize', 'unserialize']
            'serialize'  => [],
        ],
        // 更多的缓存连接
        'redis'   =>  [
            // 驱动方式
            'type'       => 'redis',
            // 服务器地址
            'host'       => env('redis.host', '127.0.0.1'),
            //前缀
            'prefix'     => env('redis.prefix', 'app_'),
            //密码
            'password'   => env('redis.password', ''),
            //有效时长
            'expire'     => env('redis.expire', 3600),
            //端口
            'port'       => env('redis.port', 6379),
            //redis库
            'select'     => env('redis.select', 0),
        ],
    ],
];
