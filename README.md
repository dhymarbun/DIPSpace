# DIPSpace
Sebuah aplikasi web untuk mengelola penggunaan fasilitas kampus (ruang kelas, aula, laboratorium, alat, dan lapangan). Pengguna dapat mengecek ketersediaan dan mengajukan reservasi, serta melaporkan kerusakan atau masalah pada fasilitas yang sama. Petugas dan admin memproses kedua alur (reservasi dan laporan) secara terpusat dalam satu sistem.

## Requirement
- PHP 8.x dengan extension `mysqli` aktif
- MySQL / MariaDB

## Setup Lokal

1. **Clone repo**
```bash
   git clone https://github.com/dhymarbun/DIPSpace
   cd DIPSpace
```

2. **Cek extension mysqli aktif**
```bash
   php -m | findstr mysqli
```
   Kalau belum muncul, buka `php.ini` (cek lokasinya lewat `php --ini`), hapus tanda `;` di depan baris `extension=mysqli`, lalu restart server.

3. **Import database**
```bash
   mysql -u root -p < database.sql
```
   (kosongkan password kalau MySQL local tidak pakai password, tinggal Enter)

   Atau lewat phpMyAdmin: Import → pilih `database.sql` → Go.

4. **Sesuaikan koneksi database (kalau perlu)**
   Buka `config/db.php`, sesuaikan `$dbHost`, `$dbUser`, `$dbPassword` dengan setup MySQL masing-masing (default: `localhost` / `root` / password kosong).
   Jika database sudah pernah di-import sebelumnya, jalankan `migration_facility_reports.sql` untuk menambahkan tabel laporan tanpa menghapus data.

5. **Jalankan server**
```bash
   php -S localhost:8000
```

6. **Buka di browser**

http://localhost:8000


## Akun Demo

| Role     | Email                  | Password |
|----------|-------------------------|----------|
| Pengguna | budi@dipspace.test      | *(isi sesuai password yang di-set)* |
| Admin    | admin@dipspace.test     | *(isi sesuai password yang di-set)* |

## Fitur yang Sudah Berjalan
- Lihat daftar fasilitas beserta status (tanpa login)
- Login / logout
- Ajukan reservasi fasilitas (dengan validasi jam operasional & slot 30 menit)
- Lihat riwayat reservasi milik sendiri
- Batalkan reservasi milik sendiri (status pending/approved, sebelum waktu mulai)
- Laporkan kerusakan fasilitas dengan kategori, tanggal, deskripsi, dan foto