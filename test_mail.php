<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;

echo "=== SMTP CONFIG DEBUG ===\n";
echo "Mailer: " . config('mail.default') . "\n";
echo "Host: " . config('mail.mailers.smtp.host') . "\n";
echo "Port: " . config('mail.mailers.smtp.port') . "\n";
echo "Scheme: " . config('mail.mailers.smtp.scheme') . "\n";
echo "Username: " . config('mail.mailers.smtp.username') . "\n";
echo "Password: " . (config('mail.mailers.smtp.password') ? '***SET***' : '***EMPTY***') . "\n";
echo "From: " . config('mail.from.address') . "\n";
echo "\n=== SENDING TEST EMAIL ===\n";

// Ganti dengan email tujuan yang ingin Anda test
$targetEmail = config('mail.from.address'); // kirim ke diri sendiri dulu

try {
    Mail::raw('Ini adalah email TEST OTP dari Sistem Inventory Kebab SK. Jika Anda menerima ini, berarti SMTP berfungsi dengan baik.', function ($message) use ($targetEmail) {
        $message->to($targetEmail)
                ->subject('[TEST] OTP Reset Password - ' . now()->format('H:i:s'));
    });
    echo "✅ SMTP accepted the email (no exception thrown)\n";
    echo "📧 Sent to: $targetEmail\n";
    echo "\nCek inbox DAN folder SPAM pada email $targetEmail\n";
} catch (\Throwable $e) {
    echo "❌ GAGAL!\n";
    echo "Error Class: " . get_class($e) . "\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "\nFull trace:\n" . $e->getTraceAsString() . "\n";
}
