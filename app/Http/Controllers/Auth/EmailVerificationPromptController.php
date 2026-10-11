<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmailVerificationPromptController extends Controller
{
    /**
     * Tampilkan status verifikasi akun oleh admin.
     *
     * Aplikasi ini memakai verifikasi manual oleh admin (kolom is_verified),
     * bukan link verifikasi email, jadi halaman ini hanya menampilkan status
     * akun dan tombol kembali ke beranda.
     */
    public function __invoke(Request $request): View
    {
        return view('auth.verify-email');
    }
}
