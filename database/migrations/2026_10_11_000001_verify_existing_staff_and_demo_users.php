<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Kolom is_verified ditambahkan dengan default false, sehingga akun lama
 * (termasuk akun demo) ikut menjadi "belum diverifikasi". Admin/petugas tidak
 * butuh verifikasi, dan akun demo budi@dipspace.test diasumsikan sudah terverifikasi.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')
            ->whereIn('role', ['admin', 'petugas'])
            ->update(['is_verified' => true]);

        DB::table('users')
            ->where('email', 'budi@dipspace.test')
            ->whereNull('rejection_reason')
            ->update(['is_verified' => true]);
    }

    public function down(): void
    {
        // Data migration; tidak perlu di-rollback.
    }
};

