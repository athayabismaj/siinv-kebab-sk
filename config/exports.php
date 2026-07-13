<?php

return [
    'queue_connection' => env('EXPORT_QUEUE_CONNECTION', 'database'),
    'queue_name' => env('EXPORT_QUEUE', 'exports'),
    'stale_after_seconds' => (int) env('EXPORT_STALE_PROCESSING_SECONDS', 300),
];
