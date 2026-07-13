<?php

return [
    /*
     * Empty means Laravel uses the active default cache store. Multi-server
     * coordination is deliberately disabled unless explicitly requested.
     */
    'lock_store' => env('SCHEDULER_CACHE_STORE'),

    'multi_server' => filter_var(env('SCHEDULER_MULTI_SERVER', false), FILTER_VALIDATE_BOOL),

    'shared_atomic_stores' => [
        'database',
        'redis',
        'memcached',
        'dynamodb',
    ],

    'critical_event_names' => [
        'system-heartbeat',
        'sales-summary-current',
        'sales-summary-rebuild',
        'daily-stock-integrity-audit',
        'daily-stock-auto-close',
        'exports-cleanup',
        'backup-daily',
        'backup-weekly',
        'backup-monthly',
    ],
];
