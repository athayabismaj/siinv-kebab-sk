<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'username' => 'required',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials)) {

            $request->session()->regenerate();
            $user = Auth::user();

            if ($user->role->name === 'owner') {
                return redirect()->route('owner.panel');
            }

            if ($user->role->name === 'admin') {
                return redirect()->route('admin.panel');
            }

            // Kasir tidak boleh akses web
            Auth::logout();
            return back()->withErrors([
                'username' => 'Role tidak diizinkan mengakses web.'
            ]);
        }

        return back()->withErrors([
            'username' => 'Username atau password salah.'
        ]);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}