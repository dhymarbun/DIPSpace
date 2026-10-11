<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Hanya pengguna yang sudah diverifikasi admin yang boleh mengakses fitur
 * reservasi & laporan. Pengguna yang belum diverifikasi diarahkan ke halaman
 * status akun (dengan tombol kembali ke beranda).
 */
class EnsureAccountVerified
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if ($user && $user->role === 'pengguna' && ! $user->is_verified) {
            return redirect()->route('verification.notice');
        }

        return $next($request);
    }
}

