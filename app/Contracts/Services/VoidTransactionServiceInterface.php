<?php

namespace App\Contracts\Services;

use App\DTOs\VoidTransactionRequestDto;

interface VoidTransactionServiceInterface
{
    /**
     * Executes a transaction voiding process.
     * 
     * HUKUM MUTLAK BAGI IMPLEMENTATOR:
     * 1. WAJIB menerapkan prinsip Fail-Fast: Lakukan pengecekan validasi, otorisasi, dan idempotency di awal, sebelum memulai koneksi/lock ke database.
     * 2. WAJIB menyortir ID secara ascending sebelum melakukan proses database apa pun untuk mencegah deadlock.
     * 3. WAJIB menggunakan lockForUpdate() (Pessimistic Locking) pada saat query ke tabel transaksi, sesi kasir, dan bahan baku (ingredients).
     * 4. WAJIB melempar Exception terukur (misal: IdempotencyException) jika ditemukan $idempotencyKey yang bentrok.
     * 5. WAJIB melempar Exception terukur (misal: UnauthorizedException) jika otorisasi Instance-Level gagal untuk $actor yang diberikan.
     *
     * @param VoidTransactionRequestDto $requestDto
     * @return float
     * @throws \Exception
     */
    public function voidTransaction(VoidTransactionRequestDto $requestDto): float;
}
