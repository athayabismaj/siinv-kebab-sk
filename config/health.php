<?php

return [
    'queue_oldest_pending_warning_seconds' => (int) env('HEALTH_QUEUE_OLDEST_PENDING_WARNING_SECONDS', 300),
    'failed_jobs_warning' => (int) env('HEALTH_FAILED_JOBS_WARNING', 1),
    'export_pending_warning_seconds' => (int) env('HEALTH_EXPORT_PENDING_WARNING_SECONDS', 900),
    'export_stale_processing_seconds' => (int) env('HEALTH_EXPORT_STALE_PROCESSING_SECONDS', 300),
    'export_file_check_limit' => (int) env('HEALTH_EXPORT_FILE_CHECK_LIMIT', 20),
    'scheduler_heartbeat_stale_seconds' => (int) env('HEALTH_SCHEDULER_HEARTBEAT_STALE_SECONDS', 180),
    'scheduler_heartbeat_ttl_seconds' => (int) env('HEALTH_SCHEDULER_HEARTBEAT_TTL_SECONDS', 600),
    'disk_warning_free_percent' => (int) env('HEALTH_DISK_WARNING_FREE_PERCENT', 10),
    'disk_critical_free_percent' => (int) env('HEALTH_DISK_CRITICAL_FREE_PERCENT', 5),
];
