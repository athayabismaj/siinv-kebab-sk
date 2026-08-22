<?php

return [
    'payment_expiry_seconds' => max(60, (int) env('QRIS_PAYMENT_EXPIRY_SECONDS', 300)),
];
