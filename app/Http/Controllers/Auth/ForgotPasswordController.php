<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\PasswordOtp;
use App\Mail\OtpMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class ForgotPasswordController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | KIRIM OTP
    |--------------------------------------------------------------------------
    */
    public function sendOtp(Request $request)
    {
        //  VALIDASI EMAIL
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        $user = User::where('email', $request->email)->first();

        // RATE LIMIT (1 MENIT)
        $lastOtp = PasswordOtp::where('user_id', $user->id)
            ->latest()
            ->first();

        if ($lastOtp && $lastOtp->created_at->diffInSeconds(now()) < 60) {
            return back()->withErrors([
                'email' => 'Tunggu 1 menit sebelum meminta OTP baru.'
            ]);
        }

        // HAPUS OTP LAMA
        PasswordOtp::where('user_id', $user->id)->delete();

        // GENERATE OTP
        $otp = random_int(100000, 999999);
        $expireTime = now()->addMinutes(5);

        // SIMPAN OTP KE DATABASE
        PasswordOtp::create([
            'user_id' => $user->id,
            'otp_hash' => Hash::make($otp),
            'expires_at' => $expireTime,
            'attempts' => 0,
            'used' => false,
        ]);

        // SIMPAN SESSION UNTUK VERIFIKASI
        session([
            'otp_email' => $user->email,
            'otp_expires_at' => $expireTime->timestamp
        ]);

        // KIRIM EMAIL HTML
        Mail::to($user->email)->send(new OtpMail($otp));

        return redirect()->route('password.verify.form')
            ->with('success', 'OTP telah dikirim ke email.');
    }

    /*
    |--------------------------------------------------------------------------
    | VERIFIKASI OTP
    |--------------------------------------------------------------------------
    */
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'otp' => 'required|digits:6',
        ]);

        $email = session('otp_email');

        if (!$email) {
            return redirect()->route('password.request');
        }

        $user = User::where('email', $email)->firstOrFail();

        $otpRecord = PasswordOtp::where('user_id', $user->id)
            ->where('used', false)
            ->latest()
            ->first();

        if (!$otpRecord) {
            return back()->withErrors(['otp' => 'OTP tidak ditemukan.']);
        }

        if ($otpRecord->expires_at->isPast()) {
            return back()->withErrors(['otp' => 'OTP sudah kadaluarsa.']);
        }

        if ($otpRecord->attempts >= 5) {
            return back()->withErrors(['otp' => 'Terlalu banyak percobaan.']);
        }

        if (!Hash::check($request->otp, $otpRecord->otp_hash)) {
            $otpRecord->increment('attempts');
            return back()->withErrors(['otp' => 'OTP salah.']);
        }

        // Tandai OTP sudah dipakai
        $otpRecord->update(['used' => true]);

        session()->forget('otp_email');
        session(['password_reset_user_id' => $user->id]);

        return redirect()->route('password.reset.form');
    }

    /*
    |--------------------------------------------------------------------------
    | RESET PASSWORD
    |--------------------------------------------------------------------------
    */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'password' => 'required|min:6|confirmed',
        ]);

        $userId = session('password_reset_user_id');

        if (!$userId) {
            return redirect()->route('password.request');
        }

        $user = User::findOrFail($userId);

        $user->update([
            'password' => Hash::make($request->password)
        ]);

        // Hapus semua OTP milik user
        PasswordOtp::where('user_id', $user->id)->delete();

        session()->forget('password_reset_user_id');

        return redirect()->route('login')
            ->with('success', 'Password berhasil direset. Silakan login.');
    }
}