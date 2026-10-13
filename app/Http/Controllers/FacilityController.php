<?php

namespace App\Http\Controllers;

use App\Models\Facility;
use Illuminate\Http\Request;

class FacilityController extends Controller
{
    public function index(Request $request)
    {
        // Filter juga dapat dikirim langsung lewat query string. Nilai non-skalar
        // (mis. `?kapasitas[]=1`) diabaikan agar tidak menimbulkan error server.
        $tipe = $request->input('tipe');
        $lokasi = $request->input('lokasi');
        $kapasitas = $request->input('kapasitas');

        $tipe = is_array($tipe) ? null : $tipe;
        $lokasi = is_array($lokasi) ? null : $lokasi;
        $kapasitas = is_array($kapasitas) ? null : $kapasitas;

        $query = Facility::query();

        if (filled($tipe)) {
            $query->where('tipe', $tipe);
        }

        if (filled($lokasi)) {
            // Escape karakter wildcard LIKE agar '%' dan '_' diperlakukan sebagai
            // karakter literal, bukan pola pencarian. ESCAPE '!' portabel untuk
            // MySQL/MariaDB maupun SQLite.
            $escapedLokasi = str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $lokasi);
            $query->whereRaw("lokasi LIKE ? ESCAPE '!'", ['%' . $escapedLokasi . '%']);
        }

        if (filled($kapasitas) && is_numeric($kapasitas)) {
            // Kapasitas non-numerik diabaikan (filter tidak diterapkan) agar
            // tidak menghasilkan kueri yang salah.
            $query->where('kapasitas', '>=', $kapasitas);
        }

        $facilities = $query->orderBy('nama')->get();
        $allTipe = Facility::distinct()->pluck('tipe');
        $allLokasi = Facility::distinct()->pluck('lokasi');

        return view('welcome', compact('facilities', 'allTipe', 'allLokasi', 'tipe', 'lokasi', 'kapasitas'));
    }

    /**
     * Halaman detail fasilitas (publik). Deskripsi & sarana pendukung diambil
     * dari kolom yang diisi admin bila tersedia, dan jika tidak diisi maka
     * diturunkan dari `tipe` fasilitas sebagai fallback.
     */
    public function show(Facility $facility)
    {
        $profile = Facility::profileForTipe($facility->tipe);

        if (filled($facility->deskripsi)) {
            $profile['deskripsi'] = $facility->deskripsi;
        }

        if (! empty($facility->sarana_pendukung)) {
            $profile['sarana'] = $facility->sarana_pendukung;
        }

        return view('facilities.show', [
            'facility' => $facility,
            'profile' => $profile,
        ]);
    }
}
