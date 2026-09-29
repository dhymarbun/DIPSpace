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
        return view('admin.facilities.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama' => 'required|string|max:100',
            'tipe' => 'required|string|max:50',
            'lokasi' => 'required|string|max:100',
            'kapasitas' => 'required|integer|min:1',
        ]);

        Facility::create([
            'nama' => $request->nama,
            'tipe' => $request->tipe,
            'lokasi' => $request->lokasi,
            'kapasitas' => $request->kapasitas,
            'status' => 'aktif',
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
        ]);

        $facility = Facility::findOrFail($id);
        $facility->update([
            'nama' => $request->nama,
            'tipe' => $request->tipe,
            'lokasi' => $request->lokasi,
            'kapasitas' => $request->kapasitas,
        ]);

        return redirect()->route('admin.facilities.index')->with('success', 'Fasilitas berhasil diperbarui.');
    }

    public function deactivate($id)
    {
        $facility = Facility::findOrFail($id);
        $facility->update(['status' => 'nonaktif']);

        return back()->with('success', "Fasilitas {$facility->nama} berhasil dinonaktifkan.");
    }

    public function activate($id)
    {
        $facility = Facility::findOrFail($id);
        $facility->update(['status' => 'aktif']);

        return back()->with('success', "Fasilitas {$facility->nama} berhasil diaktifkan.");
    }
}
