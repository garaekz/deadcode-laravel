<?php

return [
    'supervisor_binary' => env('DEADCODE_SUPERVISOR_BINARY', env('DEADCODE_SUPERVISOR_INSTALL_PATH', 'bin/deadcode-supervisor')),
    'supervisor_install_path' => env('DEADCODE_SUPERVISOR_INSTALL_PATH', 'bin/deadcode-supervisor'),
    'supervisor_timeout' => (int) env('DEADCODE_SUPERVISOR_TIMEOUT', 300),
    'supervisor_release' => [
        'repository' => env('DEADCODE_SUPERVISOR_RELEASE_REPOSITORY', 'oxhq/go-supervisor'),
        'base_url' => env('DEADCODE_SUPERVISOR_RELEASE_BASE_URL', 'https://github.com'),
        'version' => env('DEADCODE_SUPERVISOR_RELEASE_VERSION'),
    ],
];
