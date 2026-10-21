<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/** Mengeluarkan paksa pengguna yang akunnya dinonaktifkan saat sesi masih berjalan. */
class EnsureActive
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if ($user && !$user->is_active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors(['login' => 'Akun Anda dinonaktifkan. Hubungi administrator.']);
        }

        return $next($request);
    }
}
