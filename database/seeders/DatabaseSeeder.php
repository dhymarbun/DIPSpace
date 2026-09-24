<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Facility;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Akun Demo
        User::create([
            'name' => 'Budi Santoso',
            'email' => 'budi@dipspace.test',
            'password' => Hash::make('password123'),
            'role' => 'pengguna',
        ]);

        User::create([
            'name' => 'Petugas DIPSpace',
            'email' => 'petugas@dipspace.test',
            'password' => Hash::make('password123'),
            'role' => 'petugas',
        ]);

        User::create([
            'name' => 'Admin DIPSpace',
            'email' => 'admin@dipspace.test',
            'password' => Hash::make('password123'),
            'role' => 'admin',
        ]);

        // 2. Data Fasilitas Awal
        DB::table('facilities')->insert([
            ['nama' => 'Laboratorium Komputer A', 'tipe' => 'Laboratorium', 'lokasi' => 'Gedung Teknik Lantai 2', 'kapasitas' => 36, 'status' => 'aktif', 'created_at' => now(), 'updated_at' => now()],
            ['nama' => 'Aula Serbaguna Cendekia', 'tipe' => 'Aula', 'lokasi' => 'Gedung Rektorat Lantai 1', 'kapasitas' => 300, 'status' => 'aktif', 'created_at' => now(), 'updated_at' => now()],
            ['nama' => 'Ruang Kelas B-203', 'tipe' => 'Ruang Kelas', 'lokasi' => 'Gedung B Lantai 2', 'kapasitas' => 42, 'status' => 'aktif', 'created_at' => now(), 'updated_at' => now()],
            ['nama' => 'Ruang Seminar Pascasarjana', 'tipe' => 'Ruang Seminar', 'lokasi' => 'Gedung Pascasarjana Lantai 3', 'kapasitas' => 80, 'status' => 'aktif', 'created_at' => now(), 'updated_at' => now()],
            ['nama' => 'Lapangan Basket Kampus', 'tipe' => 'Lapangan Olahraga', 'lokasi' => 'Area Olahraga Barat', 'kapasitas' => 150, 'status' => 'dalam_perbaikan', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}