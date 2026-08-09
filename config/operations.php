<?php

return [
    // Jam operasional lokal aplikasi untuk membatasi proses berat.
    'timezone' => env('OPS_TIMEZONE', env('APP_TIMEZONE', 'Asia/Jakarta')),
    'start_hour' => (int) env('OPS_START_HOUR', 9),
    'end_hour' => (int) env('OPS_END_HOUR', 22),

    // Sesi hari sebelumnya masih dapat ditutup kasir sebelum jam ini.
    'daily_stock_close_grace_hour' => (int) env('DAILY_STOCK_CLOSE_GRACE_HOUR', 3),

    // Jika true, export berat yang diminta di jam operasional akan dijadwalkan
    // otomatis setelah jam operasional berakhir.
    'defer_heavy_exports_during_ops' => (bool) env('OPS_DEFER_HEAVY_EXPORTS', true),

    // Delay tambahan (menit) setelah jam operasional selesai.
    'defer_buffer_minutes' => (int) env('OPS_DEFER_BUFFER_MINUTES', 5),

    // Jika export ditunda saat jam operasional, tunda beberapa detik saja.
    // Cocok untuk single-hosting agar antrean tetap cepat diproses.
    'defer_seconds_during_ops' => (int) env('OPS_DEFER_SECONDS_DURING_OPS', 5),
];
