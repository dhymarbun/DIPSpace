<?php

namespace App\Http\Controllers;

use App\Models\Facility;
use Illuminate\Http\Request;

class FacilityController extends Controller
{
    public function index(Request $request)
    {
        $query = Facility::query();

        if ($request->filled('tipe')) {
            $query->where('tipe', $request->tipe);
        }

        if ($request->filled('lokasi')) {
            $query->where('lokasi', 'like', '%' . $request->lokasi . '%');
        }

        if ($request->filled('kapasitas')) {
            $query->where('kapasitas', '>=', $request->kapasitas);
        }

        $facilities = $query->orderBy('nama')->get();
        $allTipe = Facility::distinct()->pluck('tipe');
        $allLokasi = Facility::distinct()->pluck('lokasi');

        return view('welcome', compact('facilities', 'allTipe', 'allLokasi'));
    }

    /**
     * Halaman detail fasilitas (publik). Deskripsi & sarana pendukung diambil
     * dari kolom yang diisi admin bila tersedia, dan jika tidak diisi maka
     * diturunkan dari `tipe` fasilitas sebagai fallback.
     */
    public function show(Facility $facility)
    {
        $profile = $this->profileFor($facility->tipe);

        if (filled($facility->deskripsi)) {
            $profile['deskripsi'] = $facility->deskripsi;
        }

        return view('facilities.show', [
            'facility' => $facility,
            'profile' => $profile,
        ]);
    }

    /**
     * Profil konten (deskripsi + sarana pendukung) berdasarkan tipe fasilitas.
     *
     * @return array{deskripsi: string, sarana: array<int, array{icon: string, nama: string, keterangan: string}>}
     */
    private function profileFor(string $tipe): array
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

        if (isset($profiles[$tipe])) {
            return $profiles[$tipe];
        }

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
