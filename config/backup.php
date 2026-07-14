<?php

return [
    'enabled' => env('BACKUP_ENABLED', true),
    'disk' => env('BACKUP_DISK', 'local'),
    'directory' => env('BACKUP_DIRECTORY', 'backups'),
    'temporary_directory' => env('BACKUP_TEMPORARY_DIRECTORY', 'backups/.tmp'),
    'database_connection' => env('BACKUP_DATABASE_CONNECTION', env('DB_CONNECTION', 'pgsql')),
    'pg_dump_path' => env('PG_DUMP_PATH', 'pg_dump'),
    'pg_restore_path' => env('PG_RESTORE_PATH', 'pg_restore'),
    'psql_path' => env('PSQL_PATH', 'psql'),
    'timeout' => (int) env('BACKUP_TIMEOUT_SECONDS', 900),
    'restore_allowed_environments' => array_filter(explode(',', env('BACKUP_RESTORE_ALLOWED_ENVIRONMENTS', 'local,testing'))),
    'restore_database_prefix' => env('FASE4D_PG_DATABASE_PREFIX', 'siinv_restore_test_'),
    'maintenance_database' => env('FASE4D_PG_MAINTENANCE_DATABASE', 'postgres'),
    'encryption' => [
        'enabled' => env('BACKUP_ENCRYPTION_ENABLED', false),
        'key' => env('BACKUP_ENCRYPTION_KEY'),
    ],
    'retention' => [
        'daily' => (int) env('BACKUP_RETENTION_DAILY', 7),
        'weekly' => (int) env('BACKUP_RETENTION_WEEKLY', 4),
        'monthly' => (int) env('BACKUP_RETENTION_MONTHLY', 12),
        'minimum_valid' => (int) env('BACKUP_RETENTION_MINIMUM_VALID', 1),
    ],
];
