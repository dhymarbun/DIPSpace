# DIPSpace — Software Requirements Specification (SRS)

**Versi:** 1.0  
**Tanggal:** 29 September 2026  
**Proyek:** Pengembangan Platform Khusus 2026 Sebelum UTS  
**Sistem:** DIPSpace — Aplikasi Manajemen Fasilitas Kampus

---

## 1. Pendahuluan

### 1.1 Tujuan Dokumen
Dokumen ini mendefinisikan kebutuhan fungsional dan non-fungsional untuk pengembangan platform DIPSpace, sebuah aplikasi web untuk mengelola penggunaan fasilitas kampus (ruang kelas, aula, laboratorium, alat, dan lapangan).

### 1.2 Ruang Lingkup
Aplikasi memungkinkan pengguna untuk:
- Mengecek ketersediaan fasilitas
- Mengajukan reservasi fasilitas
- Melaporkan kerusakan fasilitas
- Memproses reservasi dan laporan secara terpusat oleh petugas/admin

### 1.3 Definisi dan Istilah

| Istilah | Definisi |
|---------|----------|
| **Pengguna** | Mahasiswa, dosen, atau staf yang menggunakan fasilitas kampus |
| **Petugas** | Staf yang memproses reservasi dan laporan kerusakan |
| **Admin** | Pengelola sistem dengan akses penuh |
| **Fasilitas** | Ruang kelas, aula, laboratorium, alat, atau lapangan kampus |
| **Reservasi** | Peminjaman fasilitas untuk periode waktu tertentu |
| **Laporan Kerusakan** | Pengaduan kerusakan/masalah pada fasilitas |

### 1.4 Peran Pengguna (User Roles)

| Role | Deskripsi |
|------|-----------|
| **Pengguna** | Mengajukan reservasi, melihat riwayat, melaporkan kerusakan |
| **Petugas** | Memproses antrian reservasi dan laporan kerusakan |
| **Admin** | Akses penuh termasuk manajemen akun dan fasilitas |

---

## 2. Kebutuhan Fungsional

### 2.1 Fitur yang Sudah Berjalan (Existing)

| ID | Kebutuhan |
|----|-----------|
| FR-01 | Lihat daftar fasilitas beserta status (tanpa login) |
| FR-02 | Login / logout |
| FR-03 | Ajukan reservasi fasilitas (dengan validasi jam operasional & slot 30 menit) |
| FR-04 | Lihat riwayat reservasi milik sendiri |
| FR-05 | Batalkan reservasi milik sendiri (status pending/approved, sebelum waktu mulai) |
| FR-06 | Laporkan kerusakan fasilitas dengan kategori, tanggal, deskripsi, dan foto |
| FR-07 | Dashboard petugas/admin untuk memproses antrian reservasi dan laporan kerusakan |

### 2.2 Kebutuhan Fungsional Baru (Pengembangan 2026 Sebelum UTS)

#### FR-08: Pembatalan Reservasi oleh Petugas (Kondisi Mendesak)

| Atribut | Detail |
|---------|--------|
| **ID** | FR-08 |
| **Prioritas** | Tinggi |
| **Aktor** | Petugas |
| **Precondition** | Petugas telah login; terdapat reservasi dengan status **approved** |
| **Postcondition** | Status reservasi berubah menjadi **cancelled** dengan alasan pembatalan tercatat |

**User Story 11:**
> *Sebagai petugas, saya bisa membatalkan reservasi yang sudah disetujui dalam kondisi mendesak (mis. fasilitas mendadak tidak bisa dipakai), dengan mencantumkan alasan pembatalan.*

**Alur Utama:**
1. Petugas membuka dashboard dan melihat daftar reservasi approved
2. Petugas memilih reservasi yang akan dibatalkan
3. Petugas mengklik tombol "Batalkan"
4. Sistem menampilkan form pembatalan dengan field **alasan pembatalan** (wajib diisi)
5. Petugas mengisi alasan dan mengonfirmasi
6. Sistem mengubah status reservasi menjadi `cancelled`
7. Sistem mencatat timestamp dan petugas yang melakukan pembatalan
8. Sistem menampilkan notifikasi keberhasilan

**Validasi:**
- Alasan pembatalan tidak boleh kosong
- Hanya reservasi berstatus `approved` yang dapat dibatalkan oleh petugas
- Jika waktu mulai reservasi sudah lewat, pembatalan tidak dapat dilakukan

---

#### FR-09: Pengubahan Status Laporan oleh Petugas

| Atribut | Detail |
|---------|--------|
| **ID** | FR-09 |
| **Prioritas** | Tinggi |
| **Aktor** | Petugas |
| **Precondition** | Petugas telah login; terdapat laporan kerusakan |
| **Postcondition** | Status laporan diperbarui; catatan resolusi tersimpan saat laporan ditutup |

**User Story 12:**
> *Sebagai petugas, saya bisa mengubah status laporan (baru/diproses/selesai/ditolak) beserta catatan resolusi saat laporan ditutup.*

**Alur Utama:**
1. Petugas membuka daftar laporan kerusakan
2. Petugas memilih laporan yang akan diproses
3. Sistem menampilkan detail laporan
4. Petugas mengubah status laporan:
   - `baru` (new) — laporan baru masuk
   - `diproses` (in progress) — laporan sedang ditangani
   - `selesai` (resolved) — laporan telah diselesaikan
   - `ditolak` (rejected) — laporan ditolak
5. Jika status diubah menjadi `selesai` atau `ditolak`, **catatan resolusi** wajib diisi
6. Sistem menyimpan perubahan status beserta catatan resolusi
7. Sistem mencatat timestamp dan petugas yang melakukan perubahan

**Validasi:**
- Catatan resolusi wajib diisi jika status = `selesai` atau `ditolak`
- Catatan resolusi opsional untuk status `diproses`
- Riwayat perubahan status dapat dilacak

---

#### FR-10: Penandaan Fasilitas "Dalam Perbaikan"

| Atribut | Detail |
|---------|--------|
| **ID** | FR-10 |
| **Prioritas** | Tinggi |
| **Aktor** | Petugas |
| **Precondition** | Petugas telah login; terdapat laporan kerusakan dengan status `diproses` |
| **Postcondition** | Status fasilitas berubah menjadi `dalam_perbaikan`; setelah selesai diperbaiki, status kembali `aktif` |

**User Story 13:**
> *Sebagai petugas, saya bisa menandai fasilitas berstatus 'dalam perbaikan' terkait laporan kerusakan yang sedang ditangani, dan mengembalikannya ke status aktif setelah selesai diperbaiki.*

**Alur Utama:**
1. Petugas membuka detail laporan kerusakan dengan status `diproses`
2. Petugas mengklik tombol "Tandai Dalam Perbaikan"
3. Sistem mengubah status fasilitas terkait menjadi `dalam_perbaikan`
4. Sistem menampilkan indikator visual pada daftar fasilitas
5. Setelah perbaikan selesai, petugas membuka detail laporan yang sudah berstatus `selesai`
6. Petugas mengklik tombol "Aktifkan Fasilitas"
7. Sistem mengubah status fasilitas kembali menjadi `aktif`
8. Sistem mencatat riwayat perbaikan

**Validasi:**
- Hanya fasilitas yang terkait dengan laporan `diproses` yang dapat ditandai
- Fasilitas dengan status `dalam_perbaikan` tidak dapat direservasi
- Sistem mencegah reservasi baru pada fasilitas yang sedang diperbaikan

---

#### FR-11: Pendaftaran Akun Petugas oleh Admin

| Atribut | Detail |
|---------|--------|
| **ID** | FR-11 |
| **Prioritas** | Tinggi |
| **Aktor** | Admin |
| **Precondition** | Admin telah login |
| **Postcondition** | Akun petugas baru terdaftar dan dapat login |

**User Story 13 (Admin):**
> *Sebagai admin, saya bisa mendaftarkan akun petugas secara langsung (petugas tidak melakukan registrasi mandiri dalam kondisi apa pun).*

**Alur Utama:**
1. Admin membuka menu Manajemen Akun
2. Admin memilih "Tambah Petugas"
3. Sistem menampilkan form pendaftaran petugas:
   - Nama lengkap
   - Email
   - Password
   - Konfirmasi password
4. Admin mengisi data dan menyimpan
5. Sistem membuat akun dengan role `petugas`
6. Sistem menampilkan notifikasi keberhasilan

**Validasi:**
- Email harus unik
- Password minimal 8 karakter
- Petugas **tidak dapat** mendaftar sendiri melalui form registrasi publik
- Form registrasi publik (jika ada) hanya untuk role `pengguna`

---

#### FR-12: Pendaftaran Akun Pengguna oleh Admin

| Atribut | Detail |
|---------|--------|
| **ID** | FR-12 |
| **Prioritas** | Tinggi |
| **Aktor** | Admin |
| **Precondition** | Admin telah login |
| **Postcondition** | Akun pengguna baru terdaftar dan dapat login |

**User Story 14:**
> *Sebagai admin, saya bisa mendaftarkan akun pengguna (mahasiswa/dosen/staf) secara langsung tanpa melalui form registrasi mandiri.*

**Alur Utama:**
1. Admin membuka menu Manajemen Akun
2. Admin memilih "Tambah Pengguna"
3. Sistem menampilkan form pendaftaran pengguna:
   - Nama lengkap
   - Email
   - NIM/NIP (opsional, sesuai tipe)
   - Tipe pengguna (mahasiswa/dosen/staf)
   - Password
   - Konfirmasi password
4. Admin mengisi data dan menyimpan
5. Sistem membuat akun dengan role `pengguna`
6. Sistem menampilkan notifikasi keberhasilan

**Validasi:**
- Email harus unik
- Password minimal 8 karakter
- Sistem mendukung input massal (upload CSV) untuk efisiensi

---

#### FR-13: Verifikasi Akun Pengguna (Registrasi Mandiri)

| Atribut | Detail |
|---------|--------|
| **ID** | FR-13 |
| **Prioritas** | Sedang |
| **Aktor** | Admin |
| **Precondition** | Terdapat akun pengguna dengan status `pending` (jika fitur registrasi mandiri diimplementasikan) |
| **Postcondition** | Akun pengguna berstatus `verified` atau `rejected` |

**User Story 15:**
> *Sebagai admin, saya bisa memverifikasi atau menolak akun pengguna hasil registrasi mandiri (jika diimplementasikan) sebelum akun tersebut dapat digunakan untuk login.*

**Alur Utama:**
1. Admin membuka menu Verifikasi Akun
2. Sistem menampilkan daftar akun dengan status `pending`
3. Admin memilih akun yang akan diverifikasi
4. Admin memilih tindakan:
   - **Verifikasi** — akun aktif dan dapat login
   - **Tolak** — akun ditolak dengan alasan
5. Sistem memperbarui status akun
6. Sistem mengirim notifikasi ke email pengguna

**Validasi:**
- Akun dengan status `pending` tidak dapat login
- Alasan penolakan wajib diisi jika akun ditolak
- Riwayat verifikasi tersimpan untuk audit

---

#### FR-14: Manajemen Data Fasilitas oleh Admin

| Atribut | Detail |
|---------|--------|
| **ID** | FR-14 |
| **Prioritas** | Tinggi |
| **Aktor** | Admin |
| **Precondition** | Admin telah login |
| **Postcondition** | Data fasilitas ditambah/diperbarui/dinonaktifkan |

**User Story 16:**
> *Sebagai admin, saya bisa mengelola data fasilitas (tambah/edit/nonaktifkan).*

**Alur Utama:**
1. Admin membuka menu Manajemen Fasilitas
2. Sistem menampilkan daftar fasilitas
3. Admin dapat melakukan:
   - **Tambah Fasilitas:** Mengisi form (nama, tipe, lokasi, kapasitas, foto)
   - **Edit Fasilitas:** Mengubah data fasilitas yang sudah ada
   - **Nonaktifkan Fasilitas:** Mengubah status menjadi `nonaktif` (tidak dapat direservasi)
4. Sistem menyimpan perubahan
5. Sistem menampilkan notifikasi keberhasilan

**Validasi:**
- Nama fasilitas harus unik per lokasi
- Kapasitas harus lebih dari 0
- Fasilitas yang dinonaktifkan tidak muncul di daftar reservasi
- Fasilitas yang dinonaktifkan tidak menghapus data historis

---

#### FR-15: Ekspor Rekap Okupansi dan Frekuensi Kerusakan

| Atribut | Detail |
|---------|--------|
| **ID** | FR-15 |
| **Prioritas** | Sedang |
| **Aktor** | Admin |
| **Precondition** | Admin telah login; terdapat data reservasi dan laporan |
| **Postcondition** | File rekap diekspor dalam format CSV/Excel/PDF |

**User Story 17:**
> *Sebagai admin, saya bisa melihat dan mengekspor (CSV/Excel/PDF) rekap okupansi fasilitas dan frekuensi kerusakan per fasilitas/lokasi.*

**Alur Utama:**
1. Admin membuka menu Laporan & Rekap
2. Sistem menampilkan dashboard rekap:
   - Okupansi fasilitas (total jam terpakai per fasilitas)
   - Frekuensi kerusakan per fasilitas
   - Frekuensi kerusakan per lokasi
3. Admin memilih periode waktu (harian/mingguan/bulanan)
4. Admin memilih format ekspor: **CSV**, **Excel**, atau **PDF**
5. Sistem menampilkan preview sebelum ekspor
6. Admin mengklik "Ekspor"
7. Sistem menghasilkan file dan mengunduhnya

**Data yang Diekspor:**
- Nama fasilitas
- Tipe fasilitas
- Lokasi
- Total jam okupansi
- Jumlah reservasi
- Jumlah laporan kerusakan
- Rata-rata waktu penyelesaian perbaikan

---

## 3. Kebutuhan Non-Fungsional

### 3.1 Performa
- Halaman utama harus dimuat dalam < 2 detik
- Query database untuk dashboard petugas harus < 500ms
- Ekspor laporan harus selesai dalam < 10 detik untuk data < 10.000 baris

### 3.2 Keamanan
- Password di-hash menggunakan bcrypt
- Proteksi CSRF pada semua form
- Validasi role-based access control (RBAC) di setiap route
- Upload foto laporan harus validasi tipe file (JPG/PNG) dan ukuran maksimal (2MB)

### 3.3 Kompatibilitas
- Browser: Chrome, Firefox, Safari, Edge (versi terbaru)
- Responsive design untuk mobile dan desktop

### 3.4 Ketersediaan
- Sistem harus tersedia 24/7 selama periode akademik
- Backup database harian

---

## 4. Batasan dan Asumsi

### 4.1 Batasan
- Aplikasi dibangun menggunakan Laravel 11 dengan SQLite/MySQL
- Frontend menggunakan Blade templates + Tailwind CSS + Alpine.js
- Tidak ada integrasi payment gateway
- Notifikasi hanya via email (tidak ada SMS/push notification)

### 4.2 Asumsi
- Jam operasional fasilitas: 07.00–21.00 WIB
- Slot reservasi: 30 menit
- Maksimal reservasi per pengguna: 3 aktif bersamaan
- Kategori kerusakan: kecil, sedang, parah

---

## 5. Diagram Use Case

```
┌─────────────────────────────────────────────────────────────┐
│                        DIPSpace                              │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────┐  │
│  │ Pengguna │  │ Petugas  │  │  Admin   │  │  Sistem  │  │
│  └────┬─────┘  └────┬─────┘  └────┬─────┘  └────┬─────┘  │
│       │              │              │              │        │
│       ├─ Lihat Fasilitas            │              │        │
│       ├─ Login/Logout               │              │        │
│       ├─ Ajukan Reservasi           │              │        │
│       ├─ Lihat Riwayat              │              │        │
│       ├─ Batalkan Reservasi         │              │        │
│       ├─ Laporkan Kerusakan         │              │        │
│       │              ├─ Proses Reservasi           │        │
│       │              ├─ Batalkan Reservasi (FR-08) │        │
│       │              ├─ Ubah Status Laporan (FR-09)│        │
│       │              ├─ Tandai Perbaikan (FR-10)  │        │
│       │              │              ├─ Daftar Petugas (FR-11)│
│       │              │              ├─ Daftar Pengguna(FR-12)│
│       │              │              ├─ Verifikasi Akun(FR-13)│
│       │              │              ├─ Kelola Fasilitas(FR-14)│
│       │              │              ├─ Ekspor Rekap (FR-15)│
│       │              │              │              │        │
└─────────────────────────────────────────────────────────────┘
```

---

## 6. Status Pengembangan

| Modul | Status | Keterangan |
|-------|--------|------------|
| Autentikasi (Login/Logout) | ✅ Selesai | Laravel Breeze |
| Manajemen Fasilitas (Admin) | 🔧 Sebagian | Perlu penambahan fitur nonaktifkan |
| Reservasi (Pengguna) | ✅ Selesai | Dengan validasi jam & slot |
| Laporan Kerusakan (Pengguna) | ✅ Selesai | Dengan upload foto |
| Dashboard Petugas | ✅ Selesai | Proses reservasi & laporan |
| Dashboard Admin | 🔧 Sebagian | Perlu pengembangan lebih lanjut |
| FR-08: Pembatalan oleh Petugas | ❌ Belum | - |
| FR-09: Ubah Status Laporan | ❌ Belum | - |
| FR-10: Tandai Perbaikan | ❌ Belum | - |
| FR-11: Daftar Petugas (Admin) | ❌ Belum | - |
| FR-12: Daftar Pengguna (Admin) | ❌ Belum | - |
| FR-13: Verifikasi Akun | ❌ Belum | - |
| FR-14: Kelola Fasilitas | ❌ Belum | - |
| FR-15: Ekspor Rekap | ❌ Belum | - |

---

## 7. Referensi

- README.md — Dokumentasi setup dan fitur dasar
- Database Schema — Tabel: users, facilities, reservations, facility_reports
- Laravel 11 Documentation — https://laravel.com/docs/11.x

---

*Dokumen ini akan diperbarui seiring perkembangan proyek. Setiap perubahan requirement harus dicatat di bagian changelog.*

---

## Changelog

| Versi | Tanggal | Perubahan | Oleh |
|-------|---------|-----------|------|
| 1.0 | 29 Sep 2026 | Dokumen awal SRS dengan FR-01 s.d. FR-15 | Tim Pengembang |
