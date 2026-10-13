<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Facility extends Model
{
    use HasFactory;

    public const STATUS_AKTIF = 'aktif';
    public const STATUS_DALAM_PERBAIKAN = 'dalam_perbaikan';

    /**
     * Nilai yang diizinkan kolom `status` (harus sinkron dengan enum di migrasi).
     */
    public const STATUSES = [
        self::STATUS_AKTIF,
        self::STATUS_DALAM_PERBAIKAN,
    ];

    /**
     * Ikon yang boleh dipakai item sarana pendukung (kunci => label Bahasa Indonesia).
     * Harus sinkron dengan `resources/views/partials/icon.blade.php`.
     */
    public const SARANA_ICONS = [
        'projector' => 'Proyektor',
        'audio' => 'Audio / pengeras suara',
        'ac' => 'Pendingin ruangan',
        'seat' => 'Kursi / meja',
        'computer' => 'Komputer',
        'network' => 'Jaringan / internet',
        'board' => 'Papan tulis',
        'light' => 'Penerangan',
        'users' => 'Area umum / ruang ganti',
        'box' => 'Perlengkapan',
        'location' => 'Lokasi',
        'tools' => 'Peralatan',
    ];

    /**
     * Batas jumlah item sarana pendukung per fasilitas.
     */
    public const SARANA_MAX = 12;

    protected $fillable = [
        'nama',
        'tipe',
        'lokasi',
        'kapasitas',
        'status',
        'foto',
        'deskripsi',
        'sarana_pendukung',
    ];

    protected function casts(): array
    {
        return [
            'sarana_pendukung' => 'array',
        ];
    }

    /**
     * URL publik foto fasilitas (null bila belum ada foto).
     */
    public function getFotoUrlAttribute(): ?string
    {
        return $this->foto ? Storage::disk('public')->url($this->foto) : null;
    }

    // Relasi: 1 fasilitas bisa punya banyak reservasi
    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }

    // Relasi: 1 fasilitas bisa punya banyak laporan
    public function facilityReports()
    {
        return $this->hasMany(FacilityReport::class);
    }

    /**
     * Profil konten bawaan (deskripsi + sarana pendukung) berdasarkan tipe fasilitas.
     * Dipakai sebagai fallback bila admin belum mengisi deskripsi/sarana sendiri.
     *
     * @return array{deskripsi: string, sarana: array<int, array{icon: string, nama: string, keterangan: string}>}
     */
    public static function profileForTipe(string $tipe): array
    {
        $profiles = [
            'Aula' => [
                'deskripsi' => 'Aula serbaguna untuk seminar, kuliah umum, dan kegiatan kampus. Ruang yang luas mendukung acara dalam jumlah besar dengan penataan yang dapat disesuaikan kebutuhan kegiatan.',
                'sarana' => [
                    ['icon' => 'projector', 'nama' => 'Proyektor & layar', 'keterangan' => 'Untuk presentasi kegiatan'],
                    ['icon' => 'audio', 'nama' => 'Sistem audio', 'keterangan' => 'Pengeras suara dan mikrofon'],
                    ['icon' => 'ac', 'nama' => 'Pendingin ruangan', 'keterangan' => 'Ruang ber-AC'],
                    ['icon' => 'seat', 'nama' => 'Kursi & panggung', 'keterangan' => 'Penataan sesuai kebutuhan'],
                ],
            ],
            'Laboratorium' => [
                'deskripsi' => 'Laboratorium untuk praktikum, pelatihan, dan kegiatan belajar berbasis perangkat. Dilengkapi komputer dan jaringan internet untuk mendukung praktik mahasiswa.',
                'sarana' => [
                    ['icon' => 'computer', 'nama' => 'Komputer praktikum', 'keterangan' => 'Perangkat siap pakai'],
                    ['icon' => 'network', 'nama' => 'Jaringan internet', 'keterangan' => 'Koneksi kabel & Wi-Fi'],
                    ['icon' => 'projector', 'nama' => 'Proyektor & layar', 'keterangan' => 'Untuk presentasi materi'],
                    ['icon' => 'ac', 'nama' => 'Pendingin ruangan', 'keterangan' => 'Ruang ber-AC'],
                ],
            ],
            'Ruang Kelas' => [
                'deskripsi' => 'Ruang kelas untuk perkuliahan, diskusi kelompok, dan kegiatan akademik harian. Tata letak yang fleksibel mendukung pembelajaran interaktif.',
                'sarana' => [
                    ['icon' => 'projector', 'nama' => 'Proyektor & layar', 'keterangan' => 'Untuk presentasi materi'],
                    ['icon' => 'board', 'nama' => 'Papan tulis', 'keterangan' => 'Whiteboard dan spidol'],
                    ['icon' => 'ac', 'nama' => 'Pendingin ruangan', 'keterangan' => 'Ruang ber-AC'],
                    ['icon' => 'seat', 'nama' => 'Kursi & meja', 'keterangan' => 'Penataan kelas'],
                ],
            ],
            'Ruang Seminar' => [
                'deskripsi' => 'Ruang seminar untuk presentasi, sidang, dan kegiatan ilmiah skala menengah. Mendukung penyampaian materi dengan perangkat audio visual yang memadai.',
                'sarana' => [
                    ['icon' => 'projector', 'nama' => 'Proyektor & layar', 'keterangan' => 'Untuk presentasi materi'],
                    ['icon' => 'audio', 'nama' => 'Sistem audio', 'keterangan' => 'Pengeras suara dan mikrofon'],
                    ['icon' => 'ac', 'nama' => 'Pendingin ruangan', 'keterangan' => 'Ruang ber-AC'],
                    ['icon' => 'seat', 'nama' => 'Kursi & meja', 'keterangan' => 'Penataan seminar'],
                ],
            ],
            'Lapangan Olahraga' => [
                'deskripsi' => 'Lapangan olahraga terbuka untuk kegiatan kebugaran, latihan, dan pertandingan antarunit. Dapat digunakan pada pagi hingga sore hari.',
                'sarana' => [
                    ['icon' => 'light', 'nama' => 'Penerangan lapangan', 'keterangan' => 'Untuk kegiatan sore'],
                    ['icon' => 'seat', 'nama' => 'Area tribun', 'keterangan' => 'Tempat penonton'],
                    ['icon' => 'users', 'nama' => 'Ruang ganti', 'keterangan' => 'Fasilitas pendukung'],
                    ['icon' => 'box', 'nama' => 'Perlengkapan lapangan', 'keterangan' => 'Jaring & tiang'],
                ],
            ],
        ];

        return $profiles[$tipe] ?? self::defaultProfile();
    }

    /**
     * Daftar sarana pendukung bawaan untuk sebuah tipe fasilitas.
     *
     * @return array<int, array{icon: string, nama: string, keterangan: string}>
     */
    public static function saranaForTipe(string $tipe): array
    {
        return self::profileForTipe($tipe)['sarana'];
    }

    /**
     * Profil bawaan untuk tipe yang tidak dikenali.
     *
     * @return array{deskripsi: string, sarana: array<int, array{icon: string, nama: string, keterangan: string}>}
     */
    private static function defaultProfile(): array
    {
        return [
            'deskripsi' => 'Fasilitas kampus yang dapat digunakan untuk kegiatan akademik maupun non-akademik. Pastikan kesesuaian waktu dan kapasitas sebelum mengajukan reservasi.',
            'sarana' => [
                ['icon' => 'box', 'nama' => 'Peralatan standar', 'keterangan' => 'Sesuai kondisi ruangan'],
                ['icon' => 'ac', 'nama' => 'Pendingin ruangan', 'keterangan' => 'Bila tersedia'],
                ['icon' => 'seat', 'nama' => 'Kursi & meja', 'keterangan' => 'Menyesuaikan kapasitas'],
                ['icon' => 'light', 'nama' => 'Penerangan', 'keterangan' => 'Untuk kegiatan dalam ruang'],
            ],
        ];
    }
}
