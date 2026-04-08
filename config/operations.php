<?php

return [
    // Jam operasional lokal aplikasi untuk membatasi proses berat.
    'timezone' => env('OPS_TIMEZONE', env('APP_TIMEZONE', 'Asia/Jakarta')),
    'start_hour' => (int) env('OPS_START_HOUR', 9),
    'end_hour' => (int) env('OPS_END_HOUR', 22),

    // Jika true, export berat yang diminta di jam operasional akan dijadwalkan
    // otomatis setelah jam operasional berakhir.
    'defer_heavy_exports_during_ops' => (bool) env('OPS_DEFER_HEAVY_EXPORTS', true),

    // Delay tambahan (menit) setelah jam operasional selesai.
    'defer_buffer_minutes' => (int) env('OPS_DEFER_BUFFER_MINUTES', 5),
];
