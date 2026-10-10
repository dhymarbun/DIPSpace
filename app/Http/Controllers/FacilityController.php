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
}
