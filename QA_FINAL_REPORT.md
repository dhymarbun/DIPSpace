# QA_FINAL_REPORT.md — DIPSpace

**Judul:** Laporan Final QA, Audit Kepatuhan User Story & Perburuan Bug — DIPSpace (Sistem Reservasi Fasilitas)
**Tanggal audit:** 13 Oktober 2026
**Jenjang:** Final Product QA (Phase 1–6)
**Status dokumen:** Final

> Dokumen ini disusun berdasarkan pengujian **perilaku nyata** (bukan sekadar keberadaan route/controller/view),
> eksekusi test otomatis, probing server live, dan pembacaan kode. Setiap klaim dibedakan menjadi:
> **terkonfirmasi (reproduced)**, **berbasis bukti kode (code-evidence)**, **deviasi spesifikasi**, atau
> **belum terverifikasi**.

---

## 1. Executive Summary

Audit dilakukan menyeluruh terhadap 17 user story (US-01..US-17) dengan tiga metode:

1. **Pengujian otomatis terisolasi** — suite QA baru di `tests/Feature/Qa/` (database sqlite `:memory:` + `RefreshDatabase`), plus regression test untuk setiap perbaikan.
2. **Probing server live** — `php artisan serve` di `127.0.0.1:8123` (CSRF, autentikasi, validasi, XSS refleksi).
3. **Pembacaan kode** — untuk hal yang tidak dapat direproduksi deterministik (mis. race condition).

**Hasil eksekusi test akhir:**

| Metrik | Baseline | Setelah QA | Keterangan |
|---|---|---|---|
| Test | 72 | **222** | +150 test QA |
| Assertions | 231 | **752** | +521 assertion |
| Status | LULUS | **LULUS (222/222)** | Tidak ada regresi |

**Ringkasan temuan:**

- **3 defect terkonfirmasi diperbaiki** (BUG-01, BUG-02, BUG-03) beserta regression test — semuanya berhasil diverifikasi ulang.
- **Tidak ada temuan severity Tinggi yang tersisa** (BUG-03, satu-satunya High, kini tertutup).
- **9 gap/observasi produk lain** didokumentasikan (tidak diperbaiki karena memerlukan keputusan kebijakan/produk, bukan bug teknis murni).
- **Tidak ada celah injeksi** yang ditemukan (SQLi aman, XSS keluar ter-escape, mass assignment diabaikan, CSRF aktif, unggahan PHP ditolak).
- **Rekomendasi rilis: GO** — dengan backlog non-blocking (lihat bagian 8).

---

## 2. Environment & Metodologi

### 2.1 Lingkungan
| Item | Nilai |
|---|---|
| Platform | Windows (win32) |
| PHP | 8.5.10 |
| Framework | Laravel 13.33.0 |
| Database (aplikasi) | SQLite |
| Database (test) | SQLite `:memory:` |
| Test runner | PHPUnit 12 (`^12.5.12`) |
| Locale aplikasi | `id` (Indonesia) |
| Jumlah route | 55 |
| Server live | `php artisan serve` @ `127.0.0.1:8123` |

### 2.2 Metode & Prinsip
- Pengujian menggunakan database **in-memory terisolasi**; berkas unggahan uji selalu dibersihkan (`deletePublicFile`) agar repositori tetap bersih.
- Batasan HTML/JS (mis. atribut `required`, `accept`) **tidak dianggap sebagai bukti**; seluruh validasi kewenangan dan aturan bisnis diuji di sisi server.
- Zona waktu aplikasi = UTC; tes waktu menggunakan waktu tetap (`Carbon::setTestNow`) agar deterministik.
- Verdict yang digunakan: **PASS / FAIL / PARTIAL / BLOCKED / NOT IMPLEMENTED / NOT VERIFIED**.

### 2.3 Cakupan test QA (150 test)
| Berkas | Fokus | Jumlah |
|---|---|---|
| `ReservationTimeValidationTest` | Aturan waktu & tanggal (US-03) | 33 |
| `ReportWorkflowTest` | Laporan kerusakan & alur perbaikan (US-06/07/11/12) | 21 |
| `ReservationOverlapTest` | Anti-bentrok & persetujuan (US-09) | 20 |
| `UserManagementTest` | Manajemen akun & verifikasi (US-13/14/15) | 14 |
| `FacilitySearchTest` | Pencarian & filter fasilitas (US-02) | 12 |
| `StaffDashboardSecurityTest` | Antrian petugas & pembatalan darurat (US-08/10) | 11 |
| `FacilityManagementTest` | Manajemen fasilitas admin (US-16) | 9 |
| `ReservationCancellationTest` | Pembatalan oleh pengguna (US-04) | 9 |
| `ReportingExportTest` | Rekap okupansi & ekspor (US-17) | 8 |
| `PublicAvailabilityTest` | Ketersediaan publik (US-01) | 7 |

---

## 3. User Story Traceability Matrix (US-01 .. US-17)

| US | Deskripsi Singkat | Verdict | Bukti Utama | Catatan |
|---|---|---|---|---|
| US-01 | Daftar fasilitas publik + status ketersediaan | **PASS** | `PublicAvailabilityTest` (7) | Halaman `/` menampilkan fasilitas + status; API jadwal publik tanpa data pemohon/tujuan. |
| US-02 | Pencarian/filter fasilitas (tipe, lokasi, kapasitas) | **PASS** | `FacilitySearchTest` (11) | Filter tipe/lokasi/kapasitas benar; input malformed diperbaiki (BUG-01). |
| US-03 | Pengajuan reservasi pengguna | **PASS** | `ReservationSubmissionTest` (6), `ReservationTimeValidationTest` (33) | Terpersist benar; mass assignment `status`/`user_id` diabaikan; fasilitas rusak ditolak. |
| US-04 | Pembatalan reservasi oleh pengguna | **PASS** | `ReservationCancellationTest` (8) | Hanya reservasi sendiri, status pending/approved, dan `start_time > now()`; slot dilepas. |
| US-05 | Riwayat & detail reservasi milik pengguna | **PASS** (diperbaiki) | `ReservationSubmissionTest`, render live | **BUG-02**: tujuan & alasan pembatalan sebelumnya tidak tampil → kini ditampilkan. |
| US-06 | Pengajuan laporan kerusakan | **PASS** | `ReportWorkflowTest` (21) | Validasi kategori/tanggal/deskripsi; foto opsional; unggahan berbahaya ditolak. |
| US-07 | Pelacakan status laporan oleh pengguna | **PASS** | `ReportWorkflowTest` | Status & catatan resolusi tampil pada riwayat pengguna. |
| US-08 | Petugas memproses reservasi (setuju/tolak) | **PASS** | `ReservationOverlapTest`, `StaffDashboardSecurityTest` | Persetujuan kini dibungkus transaksi + row lock; **BUG-03** diperbaiki. |
| US-09 | Pencegahan bentrok jadwal (double booking) | **PASS** | `ReservationOverlapTest` (20) | Aturan half-open + serialisasi per fasilitas via row lock (efektif di MySQL/MariaDB). |
| US-10 | Pembatalan darurat oleh petugas + alasan | **PASS** | `StaffDashboardSecurityTest` (10) | Hanya approved & belum mulai; alasan wajib, tersimpan, dan tampil. |
| US-11 | Petugas memproses laporan (mulai/selesai/tolak) | **PARTIAL** | `ReportWorkflowTest` | Tolak dari antrian melewati catatan resolusi (BUG-04); transisi status tak dibatasi (BUG-05). |
| US-12 | Penandaan perbaikan & reaktivasi fasilitas | **PASS** | `ReportWorkflowTest` | Wajib ada laporan `in_progress`; fasilitas rusak tidak dapat direservasi; reaktivasi berfungsi. |
| US-13 | Admin membuat akun petugas | **PASS** | `UserManagementTest` (14) | Role dipatok server; petugas baru dapat login; hanya admin. |
| US-14 | Admin membuat akun pengguna | **PARTIAL** | `UserManagementTest` | `user_type` wajib diisi tetapi **tidak tersimpan** (BUG-06). |
| US-15 | Registrasi mandiri + verifikasi/penolakan admin | **PARTIAL** | `UserManagementTest` | Registrasi memaksa role pengguna & belum terverifikasi; **deviasi**: akun belum terverifikasi tetap bisa login (BUG-07). |
| US-16 | Admin mengelola fasilitas (tambah/ubah/nonaktif) | **PASS** | `FacilityManagementTest` (9) | Duplikat & input tidak valid ditolak; riwayat reservasi/laporan tetap utuh saat dinonaktifkan. |
| US-17 | Rekap okupansi & frekuensi kerusakan + ekspor | **PARTIAL** | `ReportingExportTest` (9) | Total benar; ekspor **"Excel" = CSV** dan **"PDF" = HTML** (BUG-08); tanpa filter (BUG-09); formula injection (BUG-10). |

Kesimpulan matriks: **12 PASS, 5 PARTIAL, 0 FAIL, 0 NOT IMPLEMENTED, 0 NOT VERIFIED** pada cakupan yang diuji.

---

## 4. Reservation Integrity (Integritas Reservasi)

### 4.1 Aturan waktu (sisi server)
| Skenario | Input | Ekspektasi | Hasil |
|---|---|---|---|
| Batas bawah valid | 07:00–07:30 | Diterima | ✅ PASS |
| Batas atas valid | 19:30–20:00 | Diterima | ✅ PASS |
| Mulai < 07:00 | 06:30 | Ditolak | ✅ PASS |
| Selesai > 20:00 | 20:30 | Ditolak | ✅ PASS |
| Menit bukan 00/30 | 09:15 | Ditolak | ✅ PASS |
| Beberapa slot (span) | 09:00–11:30 | Diterima | ✅ PASS |
| Mulai di masa lampau | kemarin/hari ini lewat | Ditolak | ✅ PASS |
| Tanggal selesai ≠ mulai | beda hari | Ditolak | ✅ PASS |

### 4.2 Anti-bentrok (overlap)
| Skenario | Hasil |
|---|---|
| Ajukan di slot `approved` | Ditolak ✅ |
| Ajukan bentrok pengajuan sendiri (`pending`) | Ditolak ✅ |
| Ajukan tepat berdempet (09:00–10:00 vs 10:00–11:00) | Diterima ✅ |
| Setujui bentrok penuh/sebagian/mengandung | Ditolak ✅ |
| Setujui berdempet / fasilitas / tanggal berbeda | Diterima ✅ |
| Setujui terhadap reservasi `cancelled`/`rejected` | Diterima ✅ |
| Setujui fasilitas sedang perbaikan | Ditolak ✅ |
| Setujui id tak dikenal / persetujuan berulang | Ditolak ✅ |
| Reservasi yang dibatalkan dilepas dari jadwal | Diterima lagi ✅ |

Model insiden tumpang tindih bersifat **half-open** `[start, end)` — tidak ada off-by-one pada batas berdempet.

### 4.3 Integritas konkuren
- **BUG-03 (Tinggi) — DIPERBAIKI.** Persetujuan kini dijalankan dalam `DB::transaction` dengan `lockForUpdate` pada baris fasilitas (lalu reservasi), sehingga persetujuan untuk fasilitas yang sama berjalan bergantian dan pengecekan bentrok selalu memakai kondisi terbaru. Efektif pada **MySQL/MariaDB** (target deploy); pada SQLite lock bersifat no-op.
  - Catatan verifikasi: race konkuren **tidak dapat direproduksi deterministik** di lingkungan test (SQLite in-memory). Regression test memverifikasi invarian perilaku (status dibaca ulang setelah lock, penolakan atomik, bentrok tetap ditolak).
- **Kebijakan batas waktu pembatalan (US-04)** tidak dispesifikasikan eksplisit; aplikasi memilih kebijakan: `status ∈ {pending, approved}` **dan** `start_time > now()`. Kebijakan ini diuji dan konsisten, namun dicatat sebagai **pilihan kebijakan aplikasi**, bukan aturan yang diverifikasi dari dokumen.

---

## 5. Bug Register

| ID | Severity | Area | Status | Deskripsi & Bukti | Rekomendasi |
|---|---|---|---|---|---|
| BUG-01 | Sedang | Pencarian fasilitas (US-02) | **FIXED** | `?kapasitas[]=1` (parameter array) menyebabkan **HTTP 500** pada halaman publik — Blade memanggil `request('kapasitas')` dan `htmlspecialchars` menerima array. | Sanitasi parameter non-skalar di controller; view memakai variabel tersanitasi. |
| BUG-02 | Sedang | Riwayat reservasi (US-05) | **FIXED** | Riwayat pengguna tidak menampilkan kolom **Tujuan** maupun **alasan pembatalan**; pengguna tidak dapat melihat detail lengkap/resolusi. | Tambah kolom Tujuan + tampilkan alasan pembatalan. |
| BUG-03 | **Tinggi** | Persetujuan reservasi (US-08/09) | **FIXED** | Tidak ada transaksi/locking pada `processReservation` → risiko **double-booking konkuren** (TOCTOU). Kini dibungkus `DB::transaction` + row lock per fasilitas, status dibaca ulang setelah lock, dan penolakan memakai update bersyarat atomik. | Selesai. Pertimbangkan constraint unik parsial bila kelak pindah ke PostgreSQL. |
| BUG-04 | Rendah | Proses laporan (US-11) | **OPEN** | `processReport` aksi `reject_report` menyimpan status `rejected` **tanpa** `resolution_notes`, tidak konsisten dengan `updateReportStatus` yang mewajibkan catatan. | Samakan aturan: wajibkan catatan atau dokumentasikan secara eksplisit. |
| BUG-05 | Rendah | Transisi status laporan (US-11) | **OPEN** | Transisi tidak dibatasi; laporan `resolved` dapat dibuka kembali menjadi `pending`. Kebijakan alur belum didefinisikan. | Definisikan state machine transisi status yang sah. |
| BUG-06 | Rendah | Manajemen pengguna (US-14) | **OPEN** | `user_type` **wajib** pada validasi `storePengguna`, tetapi **silently discarded** (tidak ada kolom DB). | Tambah kolom/atau hapus field agar tidak menyesatkan. |
| BUG-07 | Rendah | Verifikasi akun (US-15) | **OPEN** (deviasi) | Dokumen menyiratkan akun belum terverifikasi **tidak boleh login**; aplikasi justru mengizinkan login dan mengunci fitur (reservasi/laporan) via middleware `account.verified`. | Konfirmasi kebijakan; selaraskan middleware login atau perbarui dokumen. |
| BUG-08 | Rendah | Ekspor laporan (US-17) | **OPEN** | Tombol **"Export Excel"** mengunduh **CSV**; **"Export PDF"** mengembalikan **HTML** (print view). Label menyesatkan. | Naikkan ke format asli (XLSX/PDF) atau ganti label. |
| BUG-09 | Rendah | Rekap laporan (US-17) | **OPEN** | Halaman rekap tidak memiliki filter (fasilitas/lokasi/rentang tanggal). | Tambahkan filter bila disyaratkan. |
| BUG-10 | Rendah | Keamanan ekspor (US-17) | **OPEN** | CSV menulis nilai apa adanya; nama fasilitas `=1+1` dapat menjadi **formula injection** di spreadsheet. | Prefix `'`/escape sel yang diawali `= + - @`. |
| BUG-11 | Rendah | Filter fasilitas (US-02) | **OPEN** | `lokasi` memakai `LIKE` tanpa escape `%`/`_` sehingga `%` mencocokkan semua; `kapasitas` non-numerik mengembalikan hasil kosong **tanpa pesan**. | Escape wildcard & tangani input non-numerik secara eksplisit. |
| BUG-12 | Rendah | Kode mati | **OPEN** | `resources/views/dashboard.blade.php` tidak direferensikan oleh route manapun (route `/dashboard` berupa closure redirect). | Hapus atau hubungkan view. |
| BUG-13 | Rendah | Manajemen fasilitas (US-16) | **OPEN** | Menonaktifkan fasilitas **tidak** merekonsiliasi reservasi `approved` yang masih akan datang (tidak dibatalkan/diberi notifikasi). Kebijakan bisnis belum ditetapkan. | Tentukan kebijakan penanganan reservasi terdampak. |

---

## 6. Input Validation & Security

### 6.1 Otentikasi & otorisasi
| Pemeriksaan | Ekspektasi | Hasil |
|---|---|---|
| Tamu → `/reservations`, `/reports` | Redirect `/login` | ✅ PASS |
| Tamu → `/admin/*` | Redirect `/login` | ✅ PASS |
| Pengguna/petugas → `/admin/*` | 403 | ✅ PASS |
| Admin/pengguna → `/petugas/*` | 403 | ✅ PASS |
| Pengguna → endpoint status laporan petugas | 403 | ✅ PASS |
| Pengguna membatalkan reservasi orang lain (id tampering) | Ditolak (peringatan) | ✅ PASS |
| Akun belum terverifikasi → fitur reservasi/laporan | Redirect `verification.notice` | ✅ PASS |
| Akun ditolak dengan sesi aktif | Dipaksa logout | ✅ PASS |

### 6.2 Cross-Site Request Forgery (CSRF)
Diuji pada server **live** (di dalam PHPUnit CSRF dilewati):
- `POST /login` tanpa token → **419**
- `POST /register` tanpa token → **419**

✅ Efektif.

### 6.3 Injeksi & escaping
- **SQL Injection:** pola `' OR 1=1--` pada `tipe`/`lokasi` tidak mengubah data (query builder ber-parameter). ✅
- **XSS reflektif:** probe `?tipe=<script>alert(1)</script>` → **0 kecocokan** (output ter-escape Blade). ✅
- **Mass assignment:** `status`, `user_id`, `role`, `is_verified` pada pengajuan reservasi/registrasi **diabaikan**. ✅
- **IDOR:** reservasi & laporan selalu difilter berdasarkan pemilik. ✅

### 6.4 Unggahan berkas (US-06)
| Kasus | Hasil |
|---|---|
| Gambar valid (PNG) | Diterima, tersimpan, dapat diakses |
| Tanpa foto | Diterima (opsional) |
| `evil.php`, `evil.PHP`, `evil.pHp`, `evil.phtml` (konten PNG valid) | **Ditolak** (validasi `photo`) ✅ |
| Payload PHP (`shell.php`) | **Ditolak** ✅ |

Catatan: ketahanan ini bersandar pada validasi `image|mimes` Laravel; disarankan tetap menurunkan ekstensi dari MIME tervalidasi sebagai pertahanan berlapis (bukan defect yang terbukti).

### 6.5 Validasi kueri & input umum
- API jadwal: `month=13` → **422**, `year=abc` → **422** (validasi server aktif). ✅
- Filter kapasitas: `abc` → tidak 500 (hasil kosong, dicatat di BUG-11); `[]=1` → **200** setelah perbaikan (BUG-01). ✅
- Form laporan (server-side): kategori wajib & terbatas, deskripsi wajib/trim/≤2000, tanggal wajib & tidak boleh masa depan, fasilitas harus ada. ✅

---

## 7. Fixes & Regression

### FIX-01 — BUG-01: HTTP 500 pada parameter filter array
- **Berkas:** `app/Http/Controllers/FacilityController.php`, `resources/views/welcome.blade.php`
- **Perubahan:** controller menormalkan `tipe`/`lokasi`/`kapasitas` non-skalar menjadi `null` dan meneruskannya ke view; view berhenti memanggil `request(...)` langsung untuk field yang dapat berupa array.
- **Regression test:** `FacilitySearchTest::test_malformed_capacity_input_is_handled_without_server_error`
- **Verifikasi:** test PASS; probing live `GET /?kapasitas[]=1` → **200** (sebelumnya 500).

### FIX-02 — BUG-02: detail riwayat reservasi pengguna tidak lengkap
- **Berkas:** `resources/views/reservations/index.blade.php`
- **Perubahan:** menambah kolom **Tujuan** dan menampilkan **alasan pembatalan** pada status `cancelled`.
- **Regression test:** `ReservationSubmissionTest::test_reservation_survives_page_reload_and_is_visible_to_owner` (kini mengassert tampilnya tujuan).
- **Verifikasi:** test PASS; render live terautentikasi menampilkan header "Tujuan".

### FIX-03 — BUG-03: serialisasi persetujuan reservasi (anti double-booking)
- **Berkas:** `app/Http/Controllers/PetugasDashboardController.php`
- **Perubahan:** persetujuan (`approve`) dijalankan dalam `DB::transaction`; baris **fasilitas** dikunci dengan `lockForUpdate` (urutan lock fasilitas → reservasi untuk menghindari deadlock), status reservasi dibaca ulang setelah lock, lalu cek bentrok + update. Penolakan (`reject`) diubah menjadi update bersyarat atomik (`WHERE status = 'pending'`).
- **Regression test:** `ReservationOverlapTest::test_reject_is_an_atomic_conditional_update`, `ReservationOverlapTest::test_approval_rechecks_state_after_locking`.
- **Verifikasi:** seluruh suite (222 test) lulus; perilaku sekuensial & penolakan tetap sesuai.
- **Catatan:** efektivitas lock bergantung pada **MySQL/MariaDB**; pada SQLite (lingkungan test) lock no-op.

### Dampak & Regresi
- Full suite: **222 lulus / 752 assertion**, tanpa regresi terhadap 72 test baseline.
- Tidak ada perubahan pada aturan bisnis, skema database, atau kebijakan yang belum ditetapkan.
- Tidak ada commit/push yang dilakukan (menunggu otorisasi).

---

## 8. Product Requirement Gaps (tidak diperbaiki)

Gap berikut **tidak** diperbaiki karena memerlukan keputusan produk/kebijakan dan/atau di luar perbaikan minimal yang aman:

1. **`user_type` tidak tersimpan (BUG-06)** — perlu keputusan apakah menambah kolom atau menghapus field.
2. **Label ekspor menyesatkan (BUG-08)** — perlu keputusan menambah dependensi XLSX/PDF atau mengubah label.
3. **Filter rekap kurang (BUG-09)** — perlu spesifikasi filter yang diminta.
4. **Transisi status laporan & tolak tanpa catatan (BUG-04/05)** — perlu definisi state machine resmi.
5. **Deviasi login akun belum terverifikasi (BUG-07)** — perlu klarifikasi niat spesifikasi.
6. **Rekonsiliasi reservasi saat fasilitas dinonaktifkan (BUG-13)** — perlu kebijakan bisnis.
7. **Batas waktu pembatalan** — aplikasi memilih `pending|approved` + waktu belum mulai; dicatat sebagai asumsi, bukan PASS palsu.

---

## 9. Release Recommendation

**Rekomendasi: GO (backlog non-blocking).**

- **Dapat dirilis** untuk kebutuhan operasional saat ini: alur inti (publik → pengajuan → persetujuan → pembatalan → laporan → perbaikan → rekap) berfungsi benar, otorisasi berbasis peran ketat, CSRF aktif, serta tidak ditemukan celah injeksi.
- **BUG-03 (satu-satunya severity Tinggi) telah diperbaiki** dengan transaksi + row lock per fasilitas; pastikan target deploy adalah **MySQL/MariaDB** agar lock ditegakkan.
- **Disarankan (non-blocking):** rapikan **BUG-08** (label ekspor) dan **BUG-04** (konsistensi catatan penolakan) agar tidak menyesatkan pengguna/petugas.
- **Backlog:** BUG-05, 06, 07, 09, 10, 11, 12, 13.

### Keterbatasan audit
- Race condition konkuren **tidak direproduksi** secara deterministik (tidak ada mekanisme lock untuk diuji) → verdict berbasis bukti kode, bukan reproduced.
- Pengujian UI visual (overflow/layout) dan konsol peramban tidak dijalankan penuh (tool peramban tidak tersedia pada sesi ini); fokus audit pada perilaku server & kontrak data.

---

*Lampiran: seluruh test QA berada di `tests/Feature/Qa/`. Perintah reproduksi:*
- `php artisan test` — seluruh suite
- `php artisan test tests/Feature/Qa` — suite QA saja
