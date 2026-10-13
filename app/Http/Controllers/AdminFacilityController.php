<?php

namespace App\Http\Controllers;

use App\Models\Facility;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AdminFacilityController extends Controller
{
    // ========== FR-14: Manage Facilities ==========

    public function index()
    {
        $facilities = Facility::orderBy('nama', 'asc')->get();
        return view('admin.facilities.index', compact('facilities'));
    }

    public function create()
    {
        $existingTipes = Facility::distinct()->pluck('tipe')->toArray();
        $existingLokasis = Facility::distinct()->pluck('lokasi')->toArray();

        return view('admin.facilities.create', compact('existingTipes', 'existingLokasis'));
    }

    public function store(Request $request)
    {
        $sarana = $this->prepareSarana($request->input('sarana', []));
        $request->merge(['sarana' => $sarana]);

        $request->validate([
            'nama' => 'required|string|max:100',
            'tipe' => 'required|string|max:50',
            'lokasi' => 'required|string|max:100',
            'kapasitas' => 'required|integer|min:1',
            'deskripsi' => 'nullable|string|max:2000',
            'foto' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'sarana' => 'nullable|array|max:' . Facility::SARANA_MAX,
            'sarana.*.nama' => 'required|string|max:60',
            'sarana.*.keterangan' => 'nullable|string|max:100',
            'sarana.*.icon' => 'required|string|in:' . implode(',', array_keys(Facility::SARANA_ICONS)),
        ]);

        $duplicate = Facility::whereRaw('LOWER(nama) = ?', [strtolower($request->nama)])
            ->whereRaw('LOWER(tipe) = ?', [strtolower($request->tipe)])
            ->whereRaw('LOWER(lokasi) = ?', [strtolower($request->lokasi)])
            ->exists();

        if ($duplicate) {
            return back()->withInput()->with('error', 'Fasilitas dengan nama, tipe, dan lokasi yang sama sudah ada.');
        }

        $data = [
            'nama' => $request->nama,
            'tipe' => $request->tipe,
            'lokasi' => $request->lokasi,
            'kapasitas' => $request->kapasitas,
            'status' => Facility::STATUS_AKTIF,
            'deskripsi' => $request->deskripsi,
            'sarana_pendukung' => $sarana !== [] ? $sarana : null,
        ];

        if ($request->hasFile('foto')) {
            $data['foto'] = $request->file('foto')->store('facilities', 'public');
        }

        Facility::create($data);

        return redirect()->route('admin.facilities.index')->with('success', 'Fasilitas berhasil ditambahkan.');
    }

    public function edit($id)
    {
        $facility = Facility::findOrFail($id);

        // Prefill dari data tersimpan, atau dari bawaan tipe bila belum diisi admin.
        $sarana = old('sarana', $facility->sarana_pendukung ?: Facility::saranaForTipe($facility->tipe));

        return view('admin.facilities.edit', compact('facility', 'sarana'));
    }

    public function update(Request $request, $id)
    {
        $sarana = $this->prepareSarana($request->input('sarana', []));
        $request->merge(['sarana' => $sarana]);

        $request->validate([
            'nama' => 'required|string|max:100',
            'tipe' => 'required|string|max:50',
            'lokasi' => 'required|string|max:100',
            'kapasitas' => 'required|integer|min:1',
            'status' => 'required|in:' . implode(',', Facility::STATUSES),
            'deskripsi' => 'nullable|string|max:2000',
            'foto' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'hapus_foto' => 'nullable|boolean',
            'sarana' => 'nullable|array|max:' . Facility::SARANA_MAX,
            'sarana.*.nama' => 'required|string|max:60',
            'sarana.*.keterangan' => 'nullable|string|max:100',
            'sarana.*.icon' => 'required|string|in:' . implode(',', array_keys(Facility::SARANA_ICONS)),
        ]);

        $facility = Facility::findOrFail($id);

        $duplicate = Facility::whereRaw('LOWER(nama) = ?', [strtolower($request->nama)])
            ->whereRaw('LOWER(tipe) = ?', [strtolower($request->tipe)])
            ->whereRaw('LOWER(lokasi) = ?', [strtolower($request->lokasi)])
            ->where('id', '!=', $id)
            ->exists();

        if ($duplicate) {
            return back()->withInput()->with('error', 'Fasilitas dengan nama, tipe, dan lokasi yang sama sudah ada.');
        }

        $data = [
            'nama' => $request->nama,
            'tipe' => $request->tipe,
            'lokasi' => $request->lokasi,
            'kapasitas' => $request->kapasitas,
            'status' => $request->status,
            'deskripsi' => $request->deskripsi,
            'sarana_pendukung' => $sarana !== [] ? $sarana : null,
        ];

        if ($request->hasFile('foto')) {
            if ($facility->foto) {
                Storage::disk('public')->delete($facility->foto);
            }
            $data['foto'] = $request->file('foto')->store('facilities', 'public');
        } elseif ($request->boolean('hapus_foto') && $facility->foto) {
            Storage::disk('public')->delete($facility->foto);
            $data['foto'] = null;
        }

        $facility->update($data);

        return redirect()->route('admin.facilities.index')->with('success', 'Fasilitas berhasil diperbarui.');
    }

    /**
     * Bersihkan input sarana pendukung: buang baris kosong, rapikan spasi,
     * dan pastikan ikon selalu salah satu nilai yang dikenal.
     *
     * @param  mixed  $rows
     * @return array<int, array{icon: string, nama: string, keterangan: string}>
     */
    private function prepareSarana($rows): array
    {
        if (! is_array($rows)) {
            return [];
        }

        $allowed = array_keys(Facility::SARANA_ICONS);
        $clean = [];

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $nama = trim((string) ($row['nama'] ?? ''));
            $keterangan = trim((string) ($row['keterangan'] ?? ''));

            // Abaikan baris yang benar-benar kosong.
            if ($nama === '' && $keterangan === '') {
                continue;
            }

            $icon = (string) ($row['icon'] ?? '');
            if (! in_array($icon, $allowed, true)) {
                $icon = 'box';
            }

            $clean[] = [
                'icon' => $icon,
                'nama' => $nama,
                'keterangan' => $keterangan,
            ];
        }

        return $clean;
    }

    public function deactivate($id)
    {
        $facility = Facility::findOrFail($id);

        if ($facility->status === Facility::STATUS_DALAM_PERBAIKAN) {
            return back()->with('error', "Fasilitas {$facility->nama} sudah dalam perbaikan.");
        }

        $facility->update(['status' => Facility::STATUS_DALAM_PERBAIKAN]);

        return back()->with('success', "Fasilitas {$facility->nama} ditandai dalam perbaikan.");
    }

    public function activate($id)
    {
        $facility = Facility::findOrFail($id);

        if ($facility->status === Facility::STATUS_AKTIF) {
            return back()->with('error', "Fasilitas {$facility->nama} sudah aktif.");
        }

        $facility->update(['status' => Facility::STATUS_AKTIF]);

        return back()->with('success', "Fasilitas {$facility->nama} berhasil diaktifkan.");
    }
}
