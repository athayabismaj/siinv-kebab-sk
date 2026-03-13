<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\ApiToken;
use App\Models\PasswordOtp;
use App\Models\User;
use App\Mail\OtpMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $validated = $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
            'device_name' => 'nullable|string|max:100',
        ]);

        $user = User::withTrashed()
            ->with('role')
            ->where('username', $validated['username'])
            ->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Username atau password salah.',
            ], 401);
        }

        if ($user->trashed()) {
            return response()->json([
                'success' => false,
                'message' => 'Akun Anda telah dinonaktifkan.',
            ], 403);
        }

        $plainToken = Str::random(64);
        $expiresAt = now()->addDays(7);

        ApiToken::create([
            'user_id' => $user->id,
            'name' => $validated['device_name'] ?? 'mobile-app',
            'token_hash' => hash('sha256', $plainToken),
            'expires_at' => $expiresAt,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Login berhasil.',
            'data' => [
                'token' => $plainToken,
                'token_type' => 'Bearer',
                'expires_at' => $expiresAt->toIso8601String(),
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'username' => $user->username,
                    'email' => $user->email,
                    'role' => $user->role?->name,
                ],
            ],
        ]);
    }

    public function me(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'role' => $user->role?->name,
            ],
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'username' => [
                'required',
                'string',
                'max:50',
                Rule::unique('users', 'username')->ignore($user->id),
            ],
            'email' => [
                'required',
                'email',
                'max:100',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
        ]);

        $user->update([
            'name' => $validated['name'],
            'username' => $validated['username'],
            'email' => $validated['email'],
        ]);

        $user->refresh();

        return response()->json([
            'success' => true,
            'message' => 'Profile berhasil diperbarui.',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'role' => $user->role?->name,
            ],
        ]);
    }

    public function changePassword(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        if (! Hash::check($validated['current_password'], $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Password lama tidak sesuai.',
            ], 422);
        }

        if (Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Password baru harus berbeda dari password lama.',
            ], 422);
        }

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Password berhasil diubah.',
        ]);
    }
    public function logout(Request $request)
    {
        $bearerToken = $request->bearerToken();
        if (! $bearerToken) {
            return response()->json([
                'success' => false,
                'message' => 'Token autentikasi tidak ditemukan.',
            ], 401);
        }

        ApiToken::query()
            ->where('token_hash', hash('sha256', $bearerToken))
            ->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logout berhasil.',
        ]);
    }

    public function forgotPassword(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        $user = User::query()
            ->where('email', $validated['email'])
            ->firstOrFail();

        $lastOtp = PasswordOtp::query()
            ->where('user_id', $user->id)
            ->latest()
            ->first();

        if ($lastOtp && $lastOtp->created_at->diffInSeconds(now()) < 60) {
            return response()->json([
                'success' => false,
                'message' => 'Tunggu 1 menit sebelum meminta OTP baru.',
            ], 429);
        }

        PasswordOtp::query()
            ->where('user_id', $user->id)
            ->delete();

        $otp = random_int(100000, 999999);
        $expireTime = now()->addMinutes(5);

        PasswordOtp::create([
            'user_id' => $user->id,
            'otp_hash' => Hash::make($otp),
            'expires_at' => $expireTime,
            'attempts' => 0,
            'used' => false,
        ]);

        Mail::to($user->email)->send(new OtpMail($otp));

        return response()->json([
            'success' => true,
            'message' => 'OTP telah dikirim ke email.',
            'data' => [
                'email' => $user->email,
                'expires_at' => $expireTime->toIso8601String(),
            ],
        ]);
    }

    public function verifyResetCode(Request $request)
    {
        $request->merge([
            'code' => $request->input('code')
                ?? $request->input('otp')
                ?? $request->input('reset_code'),
        ]);

        $validated = $request->validate([
            'email' => 'required|email|exists:users,email',
            'code' => 'required|digits:6',
        ]);

        $user = User::query()
            ->where('email', $validated['email'])
            ->firstOrFail();

        $otpRecord = PasswordOtp::query()
            ->where('user_id', $user->id)
            ->where('used', false)
            ->latest()
            ->first();

        if (! $otpRecord) {
            return response()->json([
                'success' => false,
                'message' => 'Kode OTP tidak ditemukan.',
            ], 422);
        }

        if ($otpRecord->expires_at->isPast()) {
            return response()->json([
                'success' => false,
                'message' => 'Kode OTP sudah kedaluwarsa.',
            ], 422);
        }

        if ($otpRecord->attempts >= 5) {
            return response()->json([
                'success' => false,
                'message' => 'Terlalu banyak percobaan OTP.',
            ], 429);
        }

        if (! Hash::check($validated['code'], $otpRecord->otp_hash)) {
            $otpRecord->increment('attempts');

            return response()->json([
                'success' => false,
                'message' => 'Kode OTP tidak valid.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Kode OTP valid.',
            'data' => [
                'email' => $user->email,
                'expires_at' => $otpRecord->expires_at->toIso8601String(),
            ],
        ]);
    }

    public function resetPassword(Request $request)
    {
        $request->merge([
            'code' => $request->input('code')
                ?? $request->input('otp')
                ?? $request->input('reset_code'),
            'password_confirmation' => $request->input('password_confirmation')
                ?? $request->input('confirm_password'),
        ]);

        $validated = $request->validate([
            'email' => 'required|email|exists:users,email',
            'code' => 'required|digits:6',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $user = User::query()
            ->where('email', $validated['email'])
            ->firstOrFail();

        $otpRecord = PasswordOtp::query()
            ->where('user_id', $user->id)
            ->where('used', false)
            ->latest()
            ->first();

        if (! $otpRecord) {
            return response()->json([
                'success' => false,
                'message' => 'Kode OTP tidak ditemukan.',
            ], 422);
        }

        if ($otpRecord->expires_at->isPast()) {
            return response()->json([
                'success' => false,
                'message' => 'Kode OTP sudah kedaluwarsa.',
            ], 422);
        }

        if (! Hash::check($validated['code'], $otpRecord->otp_hash)) {
            $otpRecord->increment('attempts');

            return response()->json([
                'success' => false,
                'message' => 'Kode OTP tidak valid.',
            ], 422);
        }

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        $otpRecord->update(['used' => true]);
        PasswordOtp::query()
            ->where('user_id', $user->id)
            ->where('id', '!=', $otpRecord->id)
            ->delete();

        ApiToken::query()->where('user_id', $user->id)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Password berhasil direset. Silakan login ulang.',
        ]);
    }
}


