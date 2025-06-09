<?php

return [
    'notification' => [
        'slack' => [
            'outgoing_webhook_url' => env('CUSTOM_SLACK_NOTIFICATION_WEBHOOK'),
            'alert_max_count' => env('CUSTOM_SLACK_NOTIFICATION_ALERT_MAX')
        ]
    ],

    'currency' => [
        'exchange_rate_api' => [
            'base_url' => env('API_EXCHANGE_BASE_URL'),
            'api_key' => env('API_EXCHANGE_KEY'),
            'default_currency' => env('API_EXCHANGE_DEFAULT_CURRENCY'),
        ]
    ],

    'storage' => [
        's3' => [
            'payments' => [
                'max_file_size' => env('CUSTOM_STORAGE_PAYMENTS_MAX_FILE_SIZE'),
            ]
        ]
    ],

    'payments' => [
        'csv_process' => [
            'batch' => [
                'size' => env('CUSTOM_PAYMENTS_CSV_BATCH_SIZE')
            ]
        ]
    ]
];
