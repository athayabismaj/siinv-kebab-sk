<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\PasswordOtp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class ForgotPasswordController extends Controller
{
    // Kirim OTP
    public function sendOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        $user = User::where('email', $request->email)->first();

        // Generate OTP 6 digit
        $otp = random_int(100000, 999999);

        PasswordOtp::create([
            'user_id' => $user->id,
            'otp_hash' => Hash::make($otp),
            'expires_at' => Carbon::now()->addMinutes(5),
        ]);

        // Kirim email
        Mail::raw("Kode OTP Anda: $otp (berlaku 5 menit)", function ($message) use ($user) {
            $message->to($user->email)
                ->subject('Reset Password OTP');
        });

        return back()->with('success', 'OTP telah dikirim ke email.');
    }

    // Verifikasi OTP
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp' => 'required|digits:6',
        ]);

        $user = User::where('email', $request->email)->firstOrFail();

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

        if (!Hash::check($request->otp, $otpRecord->otp_hash)) {
            $otpRecord->increment('attempts');
            return back()->withErrors(['otp' => 'OTP salah.']);
        }

        $otpRecord->update(['used' => true]);

        return redirect()->route('password.reset.form', $user->id);
    }
}