<?php

namespace App\Http\Controllers;

use App\Models\Facility;
use Illuminate\Http\Request;

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
        $request->validate([
            'nama' => 'required|string|max:100',
            'tipe' => 'required|string|max:50',
            'lokasi' => 'required|string|max:100',
            'kapasitas' => 'required|integer|min:1',
        ]);

        $duplicate = Facility::whereRaw('LOWER(nama) = ?', [strtolower($request->nama)])
            ->whereRaw('LOWER(tipe) = ?', [strtolower($request->tipe)])
            ->whereRaw('LOWER(lokasi) = ?', [strtolower($request->lokasi)])
            ->exists();

        if ($duplicate) {
            return back()->withInput()->with('error', 'Fasilitas dengan nama, tipe, dan lokasi yang sama sudah ada.');
        }

        Facility::create([
            'nama' => $request->nama,
            'tipe' => $request->tipe,
            'lokasi' => $request->lokasi,
            'kapasitas' => $request->kapasitas,
            'status' => Facility::STATUS_AKTIF,
        ]);

        return redirect()->route('admin.facilities.index')->with('success', 'Fasilitas berhasil ditambahkan.');
    }

    public function edit($id)
    {
        $facility = Facility::findOrFail($id);
        return view('admin.facilities.edit', compact('facility'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'nama' => 'required|string|max:100',
            'tipe' => 'required|string|max:50',
            'lokasi' => 'required|string|max:100',
            'kapasitas' => 'required|integer|min:1',
            'status' => 'required|in:' . implode(',', Facility::STATUSES),
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

        $facility->update([
            'nama' => $request->nama,
            'tipe' => $request->tipe,
            'lokasi' => $request->lokasi,
            'kapasitas' => $request->kapasitas,
            'status' => $request->status,
        ]);

        return redirect()->route('admin.facilities.index')->with('success', 'Fasilitas berhasil diperbarui.');
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
