<?php

declare(strict_types=1);

return [
    'deadcore' => [
        'binary' => env('DEADCORE_BINARY', 'deadcore'),
        'install_path' => env('DEADCORE_INSTALL_PATH', 'bin/deadcore'),
        'source_root' => env('DEADCORE_SOURCE_ROOT'),
        'working_directory' => env('DEADCORE_WORKING_DIRECTORY'),
        'timeout' => (int) env('DEADCORE_TIMEOUT', 120),
        'release' => [
            'repository' => env('DEADCORE_RELEASE_REPOSITORY', 'oxhq/deadcore'),
            'base_url' => env('DEADCORE_RELEASE_BASE_URL', 'https://github.com'),
            'version' => env('DEADCORE_RELEASE_VERSION'),
        ],
    ],

    'analysis' => [
        'composer' => 'composer.json',
        'composer_lock' => 'composer.lock',
        'scan' => [
            'targets' => ['app', 'routes'],
            'globs' => ['app/**/*.php', 'routes/**/*.php'],
            'vendor_whitelist' => [],
        ],
        'limits' => [
            'max_workers' => 8,
            'max_files' => 500,
            'max_depth' => 6,
        ],
        'cache' => [
            'enabled' => true,
            'kind' => 'sha256+mtime',
        ],
        'features' => [
            'http_status' => true,
            'request_usage' => true,
            'resource_usage' => true,
            'with_pivot' => true,
            'attribute_make' => true,
            'scopes_used' => true,
            'polymorphic' => true,
            'broadcast_channels' => true,
        ],
        'packages' => [
            'spatie' => [
                'laravelData' => 'spatie/laravel-data',
                'laravelQueryBuilder' => 'spatie/laravel-query-builder',
                'laravelPermission' => 'spatie/laravel-permission',
                'laravelMedialibrary' => 'spatie/laravel-medialibrary',
                'laravelTranslatable' => 'spatie/laravel-translatable',
            ],
        ],
    ],
];
