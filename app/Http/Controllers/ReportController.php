<?php

namespace App\Http\Controllers;

use App\Models\Facility;
use App\Models\FacilityReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReportController extends Controller
{
    public function index()
    {
        $facilities = Facility::where('status', '!=', 'dalam_perbaikan')
            ->orderBy('nama', 'asc')
            ->get();

        return view('reports.index', compact('facilities'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'facility_id' => 'required|exists:facilities,id',
            'category' => 'required|in:kecil,sedang,parah',
            'report_date' => 'required|date',
            'description' => 'required|string|max:2000',
            'photo' => 'nullable|image|mimes:jpeg,jpg,png,webp|max:5120',
        ]);

        $facility = Facility::where('id', $request->facility_id)
            ->where('status', '!=', 'dalam_perbaikan')
            ->first();

        if (!$facility) {
            return back()->withInput()->with('error', 'Fasilitas tidak tersedia atau sedang dalam perbaikan.');
        }

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $file = $request->file('photo');
            $uploadDir = public_path('uploads/reports');
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $filename = bin2hex(random_bytes(16)) . '.' . $file->getClientOriginalExtension();
            $file->move($uploadDir, $filename);
            $photoPath = 'uploads/reports/' . $filename;
        }

        FacilityReport::create([
            'user_id' => Auth::id(),
            'facility_id' => $request->facility_id,
            'category' => $request->category,
            'report_date' => $request->report_date,
            'description' => $request->description,
            'photo_path' => $photoPath,
            'status' => 'pending',
        ]);

        return redirect()->route('reports.index')->with('success', 'Laporan kerusakan berhasil dikirim.');
    }
}
