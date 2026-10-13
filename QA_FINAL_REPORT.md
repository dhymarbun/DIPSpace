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
| Test | 72 | **224** | +152 test QA |
| Assertions | 231 | **770** | +539 assertion |
| Status | LULUS | **LULUS (224/224)** | Tidak ada regresi |

**Ringkasan temuan:**

- **8 defect terkonfirmasi diperbaiki** (BUG-01, 02, 03, 04, 08, 10, 11, 12) beserta regression test — semuanya diverifikasi ulang.
- **Tidak ada temuan severity Tinggi yang tersisa** (BUG-03, satu-satunya High, kini tertutup).
- **5 gap/observasi produk lain** didokumentasikan (memerlukan keputusan kebijakan/produk).
- **Tidak ada celah injeksi** yang ditemukan (SQLi aman, XSS keluar ter-escape, mass assignment diabaikan, CSRF aktif, unggahan PHP ditolak, sel CSV dinetralkan).
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

### 2.3 Cakupan test QA (152 test)
| Berkas | Fokus | Jumlah |
|---|---|---|
| `ReservationTimeValidationTest` | Aturan waktu & tanggal (US-03) | 33 |
| `ReportWorkflowTest` | Laporan kerusakan & alur perbaikan (US-06/07/11/12) | 22 |
| `ReservationOverlapTest` | Anti-bentrok & persetujuan (US-09) | 20 |
| `UserManagementTest` | Manajemen akun & verifikasi (US-13/14/15) | 14 |
| `FacilitySearchTest` | Pencarian & filter fasilitas (US-02) | 12 |
| `StaffDashboardSecurityTest` | Antrian petugas & pembatalan darurat (US-08/10) | 12 |
| `FacilityManagementTest` | Manajemen fasilitas admin (US-16) | 9 |
| `ReservationCancellationTest` | Pembatalan oleh pengguna (US-04) | 9 |
| `ReportingExportTest` | Rekap okupansi & ekspor (US-17) | 8 |
| `PublicAvailabilityTest` | Ketersediaan publik (US-01) | 7 |

---

## 3. User Story Traceability Matrix (US-01 .. US-17)

| US | Deskripsi Singkat | Verdict | Bukti Utama | Catatan |
|---|---|---|---|---|
| US-01 | Daftar fasilitas publik + status ketersediaan | **PASS** | `PublicAvailabilityTest` (7) | Halaman `/` menampilkan fasilitas + status; API jadwal publik tanpa data pemohon/tujuan. |
| US-02 | Pencarian/filter fasilitas (tipe, lokasi, kapasitas) | **PASS** | `FacilitySearchTest` (12) | Filter benar; input malformed (BUG-01) serta wildcard/`kapasitas` non-numerik (BUG-11) diperbaiki. |
| US-03 | Pengajuan reservasi pengguna | **PASS** | `ReservationSubmissionTest` (6), `ReservationTimeValidationTest` (33) | Terpersist benar; mass assignment `status`/`user_id` diabaikan; fasilitas rusak ditolak. |
| US-04 | Pembatalan reservasi oleh pengguna | **PASS** | `ReservationCancellationTest` (8) | Hanya reservasi sendiri, status pending/approved, dan `start_time > now()`; slot dilepas. |
| US-05 | Riwayat & detail reservasi milik pengguna | **PASS** (diperbaiki) | `ReservationSubmissionTest`, render live | **BUG-02**: tujuan & alasan pembatalan sebelumnya tidak tampil → kini ditampilkan. |
| US-06 | Pengajuan laporan kerusakan | **PASS** | `ReportWorkflowTest` (22) | Validasi kategori/tanggal/deskripsi; foto opsional; unggahan berbahaya ditolak. |
| US-07 | Pelacakan status laporan oleh pengguna | **PASS** | `ReportWorkflowTest` | Status & catatan resolusi tampil pada riwayat pengguna. |
| US-08 | Petugas memproses reservasi (setuju/tolak) | **PASS** | `ReservationOverlapTest`, `StaffDashboardSecurityTest` | Persetujuan kini dibungkus transaksi + row lock; **BUG-03** diperbaiki. |
| US-09 | Pencegahan bentrok jadwal (double booking) | **PASS** | `ReservationOverlapTest` (20) | Aturan half-open + serialisasi per fasilitas via row lock (efektif di MySQL/MariaDB). |
| US-10 | Pembatalan darurat oleh petugas + alasan | **PASS** | `StaffDashboardSecurityTest` (12) | Hanya approved & belum mulai; alasan wajib, tersimpan, dan tampil. |
| US-11 | Petugas memproses laporan (mulai/selesai/tolak) | **PARTIAL** | `ReportWorkflowTest` (22) | Tolak dari antrian kini wajib catatan (BUG-04 diperbaiki); transisi status belum dibatasi (BUG-05). |
| US-12 | Penandaan perbaikan & reaktivasi fasilitas | **PASS** | `ReportWorkflowTest` | Wajib ada laporan `in_progress`; fasilitas rusak tidak dapat direservasi; reaktivasi berfungsi. |
| US-13 | Admin membuat akun petugas | **PASS** | `UserManagementTest` (14) | Role dipatok server; petugas baru dapat login; hanya admin. |
| US-14 | Admin membuat akun pengguna | **PARTIAL** | `UserManagementTest` | `user_type` wajib diisi tetapi **tidak tersimpan** (BUG-06). |
| US-15 | Registrasi mandiri + verifikasi/penolakan admin | **PARTIAL** | `UserManagementTest` | Registrasi memaksa role pengguna & belum terverifikasi; **deviasi**: akun belum terverifikasi tetap bisa login (BUG-07). |
| US-16 | Admin mengelola fasilitas (tambah/ubah/nonaktif) | **PASS** | `FacilityManagementTest` (9) | Duplikat & input tidak valid ditolak; riwayat reservasi/laporan tetap utuh saat dinonaktifkan. |
| US-17 | Rekap okupansi & frekuensi kerusakan + ekspor | **PARTIAL** | `ReportingExportTest` (8) | Total benar; label ekspor kini jujur (BUG-08) dan formula injection dinetralkan (BUG-10); **tanpa filter** (BUG-09) & format belum XLSX/PDF asli. |

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
| BUG-04 | Rendah | Proses laporan (US-11) | **FIXED** | `processReport` aksi `reject_report` kini mewajibkan `resolution_notes` (`required_if`) dan menyimpannya, konsisten dengan `updateReportStatus`. | Selesai. |
| BUG-05 | Rendah | Transisi status laporan (US-11) | **OPEN** | Transisi tidak dibatasi; laporan `resolved` dapat dibuka kembali menjadi `pending`. Kebijakan alur belum didefinisikan. | Definisikan state machine transisi status yang sah. |
| BUG-06 | Rendah | Manajemen pengguna (US-14) | **OPEN** | `user_type` **wajib** pada validasi `storePengguna`, tetapi **silently discarded** (tidak ada kolom DB). | Tambah kolom/atau hapus field agar tidak menyesatkan. |
| BUG-07 | Rendah | Verifikasi akun (US-15) | **OPEN** (deviasi) | Dokumen menyiratkan akun belum terverifikasi **tidak boleh login**; aplikasi justru mengizinkan login dan mengunci fitur (reservasi/laporan) via middleware `account.verified`. | Konfirmasi kebijakan; selaraskan middleware login atau perbarui dokumen. |
| BUG-08 | Rendah | Ekspor laporan (US-17) | **FIXED** | Label tombol dibuat jujur: **"Export Excel (CSV)"** dan **"Cetak / Simpan PDF"** (format keluaran tidak berubah; tanpa dependensi baru). | Bila format asli diwajibkan, tambahkan dependensi XLSX/PDF (backlog). |
| BUG-09 | Rendah | Rekap laporan (US-17) | **OPEN** | Halaman rekap tidak memiliki filter (fasilitas/lokasi/rentang tanggal). | Tambahkan filter bila disyaratkan. |
| BUG-10 | Rendah | Keamanan ekspor (US-17) | **FIXED** | `exportCsv` menetralkan sel yang diawali `= + - @`/tab/CR dengan prefiks apostrof (`csvSafe()`). | Selesai. |
| BUG-11 | Rendah | Filter fasilitas (US-02) | **FIXED** | `lokasi` memakai `LIKE ... ESCAPE '!'` sehingga `%`/`_` diperlakukan literal; `kapasitas` non-numerik diabaikan (semua tampil), bukan hasil kosong. | Selesai. |
| BUG-12 | Rendah | Kode mati | **FIXED** | `resources/views/dashboard.blade.php` **dan** `app/Http/Controllers/DashboardController.php` (keduanya tak dirutekan) dihapus; route `/dashboard` tetap redirect sesuai peran (guard test ditambahkan). | Selesai. |
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
- Filter kapasitas: `abc` → **200** dengan filter diabaikan (semua tampil) setelah BUG-11; `[]=1` → **200** setelah BUG-01. ✅
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
- **Verifikasi:** seluruh suite (224 test) lulus; perilaku sekuensial & penolakan tetap sesuai.
- **Catatan:** efektivitas lock bergantung pada **MySQL/MariaDB**; pada SQLite (lingkungan test) lock no-op.

### FIX-04 — BUG-04: penolakan laporan dari antrian wajib catatan
- **Berkas:** `app/Http/Controllers/PetugasDashboardController.php`, `resources/views/petugas/dashboard.blade.php`
- **Perubahan:** `processReport` menambahkan `resolution_notes => required_if:action,reject_report` dan menyimpannya; UI menyediakan input alasan sebelum menolak.
- **Regression test:** `ReportWorkflowTest::test_reject_via_queue_requires_resolution_notes`, `ReportWorkflowTest::test_reject_via_queue_stores_resolution_notes`.

### FIX-05 — BUG-08: label ekspor jujur
- **Berkas:** `resources/views/admin/reports/index.blade.php`
- **Perubahan:** "Export Excel" → **"Export Excel (CSV)"**, "Export PDF" → **"Cetak / Simpan PDF"** (format keluaran tidak berubah; tanpa dependensi baru).
- **Regression test:** dicakup `ReportingExportTest` (content-type CSV/HTML).

### FIX-06 — BUG-10: netralkan CSV formula injection
- **Berkas:** `app/Http/Controllers/AdminReportController.php`
- **Perubahan:** helper `csvSafe()` memberi prefiks `'` pada sel yang diawali `= + - @`/tab/CR.
- **Regression test:** `ReportingExportTest::test_csv_neutralizes_formula_injection`.

### FIX-07 — BUG-11: escape wildcard LIKE & kapasitas non-numerik
- **Berkas:** `app/Http/Controllers/FacilityController.php`
- **Perubahan:** `lokasi` memakai `whereRaw("lokasi LIKE ? ESCAPE '!'")` dengan escape `%`/`_`; `kapasitas` hanya diterapkan bila `is_numeric`.
- **Regression test:** `FacilitySearchTest::test_percent_and_underscore_in_location_are_treated_as_literals`, `FacilitySearchTest::test_malformed_capacity_input_is_handled_without_server_error`.

### FIX-08 — BUG-12: hapus kode mati
- **Berkas:** `resources/views/dashboard.blade.php` dan `app/Http/Controllers/DashboardController.php` (dihapus)
- **Perubahan:** kedua berkas tidak dirutekan dihapus; route `/dashboard` (closure redirect) tidak terpengaruh.
- **Regression test:** `StaffDashboardSecurityTest::test_legacy_dashboard_route_redirects_each_role`.

### Dampak & Regresi
- Full suite: **224 lulus / 770 assertion**, tanpa regresi terhadap 72 test baseline.
- Tidak ada perubahan skema database. Satu perubahan kebijakan: penolakan laporan dari antrian kini **wajib catatan** (konsisten dengan alur `updateReportStatus` yang sudah ada).
- Perubahan di-commit pada branch `qa/final-audit` dan `qa/quick-wins` (belum di-merge ke `main`).

---

## 8. Product Requirement Gaps (tidak diperbaiki)

Gap berikut **tidak** diperbaiki karena memerlukan keputusan produk/kebijakan:

1. **`user_type` tidak tersimpan (BUG-06)** — perlu keputusan apakah menambah kolom atau menghapus field.
2. **Filter rekap kurang (BUG-09)** — perlu spesifikasi filter yang diminta.
3. **Transisi status laporan belum dibatasi (BUG-05)** — perlu definisi state machine resmi (mis. `resolved` tidak dapat dibuka kembali).
4. **Deviasi login akun belum terverifikasi (BUG-07)** — perlu klarifikasi niat spesifikasi.
5. **Rekonsiliasi reservasi saat fasilitas dinonaktifkan (BUG-13)** — perlu kebijakan bisnis.
6. **Batas waktu pembatalan** — aplikasi memilih `pending|approved` + waktu belum mulai; dicatat sebagai asumsi, bukan PASS palsu.
7. **Format ekspor asli (XLSX/PDF)** — label sudah jujur (BUG-08), namun format asli memerlukan dependensi baru.

---

## 9. Release Recommendation

**Rekomendasi: GO (backlog non-blocking).**

- **Dapat dirilis** untuk kebutuhan operasional saat ini: alur inti (publik → pengajuan → persetujuan → pembatalan → laporan → perbaikan → rekap) berfungsi benar, otorisasi berbasis peran ketat, CSRF aktif, serta tidak ditemukan celah injeksi.
- **BUG-03 (satu-satunya severity Tinggi) telah diperbaiki** dengan transaksi + row lock per fasilitas; pastikan target deploy adalah **MySQL/MariaDB** agar lock ditegakkan.
- **Sudah dirapikan (non-blocking):** label ekspor (BUG-08), konsistensi catatan penolakan (BUG-04), netralisasi formula CSV (BUG-10), escape filter & kapasitas (BUG-11), hapus kode mati (BUG-12).
- **Backlog:** BUG-05, 06, 07, 09, 13, plus format ekspor asli (XLSX/PDF).

### Keterbatasan audit
- Race condition konkuren **tidak direproduksi** secara deterministik (tidak ada mekanisme lock untuk diuji) → verdict berbasis bukti kode, bukan reproduced.
- Pengujian UI visual (overflow/layout) dan konsol peramban tidak dijalankan penuh (tool peramban tidak tersedia pada sesi ini); fokus audit pada perilaku server & kontrak data.

---

*Lampiran: seluruh test QA berada di `tests/Feature/Qa/`. Perintah reproduksi:*
- `php artisan test` — seluruh suite
- `php artisan test tests/Feature/Qa` — suite QA saja
