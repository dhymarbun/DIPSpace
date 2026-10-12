# DIPSpace

Sebuah aplikasi web untuk mengelola penggunaan fasilitas kampus (ruang kelas, aula, laboratorium, alat, dan lapangan). Pengguna dapat mengecek ketersediaan dan mengajukan reservasi, serta melaporkan kerusakan atau masalah pada fasilitas yang sama. Petugas dan admin memproses kedua alur (reservasi dan laporan) secara terpusat dalam satu sistem.

## User Story

| ID | Aktor | User Story |
|---|---|---|
| US-01 | Pengunjung/Pengguna | Melihat daftar fasilitas beserta status ketersediaannya per slot waktu tanpa melihat detail pemohon atau tujuan penggunaan. |
| US-02 | Pengunjung/Pengguna | Mencari fasilitas berdasarkan tipe, lokasi, dan kapasitas. |
| US-03 | Pengguna | Mengajukan reservasi pada rentang waktu tertentu dengan menyebutkan tujuan penggunaan. |
| US-04 | Pengguna | Membatalkan reservasi miliknya sendiri sebelum batas waktu tertentu. |
| US-05 | Pengguna | Melihat riwayat dan status reservasi, termasuk detail lengkap reservasi miliknya. |
| US-06 | Pengguna | Melaporkan kerusakan atau masalah pada fasilitas dengan kategori, deskripsi, dan foto. |
| US-07 | Pengguna | Melihat status laporan kerusakan yang telah diajukan. |
| US-08 | Petugas | Melihat dashboard atau antrean reservasi dan laporan yang masih menunggu diproses. |
| US-09 | Petugas | Menyetujui atau menolak reservasi secara manual, dengan sistem mencegah persetujuan reservasi yang berbenturan jadwal pada fasilitas yang sama. |
| US-10 | Petugas | Membatalkan reservasi yang telah disetujui dalam kondisi mendesak dengan mencantumkan alasan pembatalan. |
| US-11 | Petugas | Mengubah status laporan menjadi baru, diproses, selesai, atau ditolak, beserta catatan resolusi saat laporan ditutup. |
| US-12 | Petugas | Menandai fasilitas sebagai dalam perbaikan berdasarkan laporan kerusakan yang ditangani dan mengaktifkannya kembali setelah perbaikan selesai. |
| US-13 | Admin | Mendaftarkan akun petugas secara langsung tanpa registrasi mandiri oleh petugas. |
| US-14 | Admin | Mendaftarkan akun pengguna (mahasiswa, dosen, atau staf) secara langsung. |
| US-15 | Admin | Memverifikasi atau menolak akun pengguna hasil registrasi mandiri sebelum akun dapat digunakan untuk login, jika fitur tersebut diterapkan. |
| US-16 | Admin | Mengelola data fasilitas melalui penambahan, pengubahan, dan penonaktifan. |
| US-17 | Admin | Melihat dan mengekspor rekap okupansi fasilitas serta frekuensi kerusakan berdasarkan fasilitas atau lokasi dalam format CSV, Excel, atau PDF. |

User Story digunakan sebagai acuan kebutuhan fitur dan pembagian tugas pengembangan. Implementasi setiap fitur tetap mengacu pada kebutuhan sistem dan validasi yang relevan.

## Requirement

- PHP 8.3+ (dibatasi `^8.3` di `composer.json`)
- Composer
- Node.js & npm (opsional — aplikasi tidak bergantung pada aset Vite)
- MySQL / MariaDB — atau SQLite (default: `DB_CONNECTION=sqlite` di `.env.example`)

## Setup Lokal (Untuk Anggota Tim)

1. **Clone repo dan masuk ke branch projek**
```bash
   git clone https://github.com/dhymarbun/DIPSpace
   cd DIPSpace

   # Gunakan branch yang sedang dikerjakan (contoh saat ini: fix/manual-test-issues)
   # Format: git checkout <nama-branch> && git pull origin <nama-branch>
   git checkout fix/manual-test-issues
   git pull origin fix/manual-test-issues
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
   - Jika memakai SQLite bawaan: pastikan file `database/database.sqlite` ada (buat jika belum ada, mis. `type nul > database\database.sqlite` di Windows / `touch database/database.sqlite` di Linux/Mac).
   - Jika memakai MySQL/MariaDB: isi `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` di `.env` dan buat databasenya terlebih dahulu.
   - Jalankan migrasi, seeder, dan link storage:
```bash
   php artisan migrate --seed
   php artisan storage:link
```
   > Seeder (`database/seeders/DatabaseSeeder.php`) otomatis membuat **3 akun demo** dan **5 fasilitas awal**.

5. **Jalankan Server (opsional: build aset frontend)**
   Aplikasi memakai `public/css/style.css` dan **tidak lagi bergantung pada aset Vite**. Jika ingin membangun aset Breeze (tidak wajib untuk aplikasi berjalan):
   - `npm run build` — build sekali
   - `npm run dev` — mode pengembangan (biarkan berjalan di terminal terpisah)

6. **Jalankan Server**
```bash
   php artisan serve
```
   Akses website di: http://127.0.0.1:8000

## Akun Demo

Semua akun demo dibuat oleh seeder (`php artisan migrate --seed`), berstatus **sudah diverifikasi admin** (`is_verified = true`), dan memakai password yang sama:

| Role | Email | Password |
|------|-------|----------|
| Pengguna | budi@dipspace.test | password123 |
| Petugas | petugas@dipspace.test | password123 |
| Admin | admin@dipspace.test | password123 |

Akun yang didaftarkan lewat halaman `/register` **belum otomatis diverifikasi** — admin harus menyetujuinya di menu *Kelola Pengguna → Pengajuan Akun* sebelum akun tersebut bisa melakukan reservasi/laporan.

## Role & Alur Singkat

| Role | Yang bisa dilakukan |
|------|---------------------|
| (Publik) | Lihat daftar fasilitas + status, kalender jadwal, login, daftar akun baru |
| Pengguna (terverifikasi) | Ajukan & batalkan reservasi, lapor kerusakan, lihat riwayat, kelola profil |
| Petugas | Proses antrian reservasi (setujui/tolak/batalkan), proses laporan kerusakan, tandai fasilitas dalam perbaikan |
| Admin | Kelola fasilitas & pengguna, verifikasi akun baru, rekap & ekspor laporan |


## Menjalankan Tes

```bash
   php artisan test
```

> Seluruh tes passing. Catatan: ekspektasi beberapa tes Breeze default telah disesuaikan dengan perilaku aplikasi (mis. redirect setelah login/registrasi mengarah ke beranda untuk pengguna biasa, dan `ExampleTest` memakai `RefreshDatabase`).