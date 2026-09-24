# DIPSpace
Sebuah aplikasi web untuk mengelola penggunaan fasilitas kampus (ruang kelas, aula, laboratorium, alat, dan lapangan). Pengguna dapat mengecek ketersediaan dan mengajukan reservasi, serta melaporkan kerusakan atau masalah pada fasilitas yang sama. Petugas dan admin memproses kedua alur (reservasi dan laporan) secara terpusat dalam satu sistem.

## Requirement
- PHP 8.2+ 
- Composer
- Node.js & npm
- MySQL / MariaDB (Atau SQLite bawaan Laravel 11)

## Setup Lokal (Untuk Anggota Tim)

1. **Clone repo dan masuk ke branch projek**
```bash
   git clone https://github.com/dhymarbun/DIPSpace
   cd DIPSpace
   
   # Pindah ke branch laravel (sesuaikan dengan branch yang sedang dikerjakan)
   git checkout laravel
   git pull origin laravel
```

2. **Install Dependencies**
```bash
   composer install
   npm install
```

3. **Setup Environment**
   Copy file `.env.example` menjadi `.env`:
   - Windows (PowerShell/CMD): `copy .env.example .env`
   - Linux/Mac/Git Bash: `cp .env.example .env`

   Lalu generate application key:
```bash
   php artisan key:generate
```

4. **Setup Database & Storage**
   Sesuaikan konfigurasi database di file `.env` (misal ubah `DB_CONNECTION`, `DB_DATABASE`, dll jika pakai MySQL. Jika pakai default Laravel 11 SQLite, biarkan saja).
   Jalankan migrasi, seeder, dan link storage:
```bash
   php artisan migrate --seed
   php artisan storage:link
```

5. **Jalankan Server**
   Buka 2 terminal terpisah dan jalankan:
   - Terminal 1: `php artisan serve`
   - Terminal 2: `npm run dev`

6. **Buka di Browser**
   Akses website di: http://127.0.0.1:8000


## Akun Demo

| Role     | Email                  | Password |
|----------|-------------------------|----------|
| Pengguna | budi@dipspace.test      | *(isi sesuai password yang di-set)* |
| Petugas  | petugas@dipspace.test   | *(isi sesuai password yang di-set)* |
| Admin    | admin@dipspace.test     | *(isi sesuai password yang di-set)* |

## Fitur yang Sudah Berjalan
- Lihat daftar fasilitas beserta status (tanpa login)
- Login / logout
- Ajukan reservasi fasilitas (dengan validasi jam operasional & slot 30 menit)
- Lihat riwayat reservasi milik sendiri
- Batalkan reservasi milik sendiri (status pending/approved, sebelum waktu mulai)
- Laporkan kerusakan fasilitas dengan kategori, tanggal, deskripsi, dan foto
- Dashboard petugas/admin untuk memproses antrian reservasi dan laporan kerusakan