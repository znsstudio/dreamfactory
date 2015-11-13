<?php

return [
    /** Default Store */
    'default' => env('CACHE_DRIVER', 'file'),
    /** Stores */
    'stores'  => [
        'apc'       => [
            'driver' => 'apc',
        ],
        'array'     => [
            'driver' => 'array',
        ],
        'database'  => [
            'driver'     => 'database',
            'table'      => 'cache',
            'connection' => null,
        ],
        'file'      => [
            'driver' => 'file',
            'path'   => env('DF_CACHE_PATH', base_path('bootstrap/cache')),
        ],
        'memcached' => [
            'driver'  => 'memcached',
            'servers' => [
                [
                    'host'   => '127.0.0.1',
                    'port'   => 11211,
                    'weight' => 100,
                ],
            ],
        ],
        'redis'     => [
            'driver'     => 'redis',
            'connection' => 'default',
        ],
    ],
    /** Key Prefix */
    'prefix'  => env('DF_CACHE_PREFIX', 'dreamfactory'),
];
