<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\PasswordOtp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class ForgotPasswordController extends Controller {
    
    /*** KIRIM OTP ***/ 
    public function sendOtp(Request $request) {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        $user = User::where('email', $request->email)->first();

        // Hapus OTP lama
        PasswordOtp::where('user_id', $user->id)->delete();

        $otp = random_int(100000, 999999);

        PasswordOtp::create([
            'user_id' => $user->id,
            'otp_hash' => Hash::make($otp),
            'expires_at' => Carbon::now()->addMinutes(5),
        ]);

        // Simpan email di session
        session(['otp_email' => $user->email]);

        // Untuk testing tanpa email:
        // return back()->with('success', "OTP: $otp");

        // Jika sudah siap kirim email:
        Mail::raw("Kode OTP Anda: $otp (berlaku 5 menit)", function ($message) use ($user) {
            $message->to($user->email)
                ->subject('Reset Password OTP');
        });

        return redirect()->route('password.verify.form')
            ->with('success', 'OTP telah dikirim ke email.');
    }

    /*** VERIFIKASI OTP ***/ 

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

        if ($otpRecord->expires_at < now()) {
            return back()->withErrors(['otp' => 'OTP sudah kadaluarsa.']);
        }

        if ($otpRecord->attempts >= 5) {
            return back()->withErrors(['otp' => 'Terlalu banyak percobaan.']);
        }

        if (!Hash::check($request->otp, $otpRecord->otp_hash)) {
            $otpRecord->increment('attempts');
            return back()->withErrors(['otp' => 'OTP salah.']);
        }

        $otpRecord->update(['used' => true]);

        session()->forget('otp_email');
        session(['password_reset_user_id' => $user->id]);

        return redirect()->route('password.reset.form');
    }

 
    /*** RESET PASSWORD ***/  
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

        PasswordOtp::where('user_id', $user->id)->delete();

        session()->forget('password_reset_user_id');

        return redirect()->route('login')
            ->with('success', 'Password berhasil direset. Silakan login.');
    }
}