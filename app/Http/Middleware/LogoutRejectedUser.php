<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Pengguna yang akunnya ditolak admin langsung dikeluarkan pada request
 * berikutnya, meskipun sesinya masih aktif.
 */
class LogoutRejectedUser
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if ($user && $user->isRejected()) {
            $reason = $user->rejection_reason;

            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->withErrors(['email' => "Akun Anda ditolak oleh admin. Alasan: {$reason}"]);
        }

        return $next($request);
    }
}

