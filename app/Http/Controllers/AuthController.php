<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ], [], ['login' => 'username atau email']);

        $user = User::where('username', $data['login'])->orWhere('email', $data['login'])->first();

        if (!$user || !Hash::check($data['password'], $user->password)) {
            return back()->withInput($request->only('login'))
                ->withErrors(['login' => 'Username/email atau password salah.']);
        }

        if (!$user->is_active) {
            return back()->withInput($request->only('login'))
                ->withErrors(['login' => 'Akun Anda dinonaktifkan. Hubungi administrator.']);
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        $user->forceFill(['last_login_at' => now()])->save();
        ActivityLog::record('Autentikasi', 'Masuk ke sistem');

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request)
    {
        ActivityLog::record('Autentikasi', 'Keluar dari sistem');

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
