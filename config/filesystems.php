<?php

return [

    'default' => env('FILESYSTEM_DISK', 'local'),

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root'   => storage_path('app'),
            'throw'  => false,
        ],

        'public' => [
            'driver'     => 'local',
            'root'       => storage_path('app/public'),
            'url'        => env('APP_URL') . '/storage',
            'visibility' => 'public',
            'throw'      => false,
        ],

        'vod' => [
            'driver'     => 'local',
            'root'       => storage_path('vod'),
            'url'        => env('APP_URL') . '/vod',
            'visibility' => 'public',
            'throw'      => false,
        ],

        'streams' => [
            'driver' => 'local',
            'root'   => storage_path('streams'),
            'throw'  => false,
        ],

    ],

    'links' => [
        public_path('storage') => storage_path('app/public'),
        public_path('vod')     => storage_path('vod'),
    ],

];
