<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\QueryException;
use Throwable;

class LoginController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        // ================= VALIDASI INPUT =================
        $request->validate([
            'username' => 'required',
            'password' => 'required',
        ]);

        try {
            // ================= CARI USER TERMASUK SOFT DELETE =================
            $user = User::withTrashed()
                ->where('username', $request->username)
                ->first();
        } catch (QueryException|Throwable $e) {
            report($e);

            return back()->withErrors([
                'username' => 'Layanan database sedang bermasalah. Coba lagi beberapa saat.',
            ])->withInput();
        }

        // ================= USER TIDAK DITEMUKAN =================
        if (!$user) {
            return back()->withErrors([
                'username' => 'Username atau password salah.'
            ])->withInput();
        }

        // ================= USER NONAKTIF =================
        if ($user->trashed()) {
            return back()->withErrors([
                'username' => 'Akun Anda telah dinonaktifkan.'
            ]);
        }

        // ================= PASSWORD SALAH =================
        if (!Hash::check($request->password, $user->password)) {
            return back()->withErrors([
                'username' => 'Username atau password salah.'
            ])->withInput();
        }

        // ================= LOGIN MANUAL =================
        Auth::login($user);
        $request->session()->regenerate();

        // ================= REDIRECT BERDASARKAN ROLE =================
        switch ($user->role->name) {

            case 'owner':
                return redirect()->route('owner.panel');

            case 'admin':
                return redirect()->route('admin.panel');

            default:
                Auth::logout();
                return back()->withErrors([
                    'username' => 'Role tidak diizinkan mengakses web.'
                ]);
        }
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
