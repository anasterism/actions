<?php

return [
    'log_channel' => env('ACTIONS_LOG_CHANNEL', 'actions'),

    'default_log_channel_config' => [
        'driver' => 'daily',
        'path'   => storage_path('logs/actions.log'),
        'level'  => env('ACTIONS_LOG_LEVEL', 'debug'),
        'days'   => 14,
    ],
];
