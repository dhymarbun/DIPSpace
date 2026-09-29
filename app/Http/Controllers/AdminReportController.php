<?php

namespace App\Http\Controllers;

use App\Models\Facility;
use App\Models\Reservation;
use App\Models\FacilityReport;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminReportController extends Controller
{
    // ========== FR-15: Export Reports ==========

    public function index()
    {
        $facilities = Facility::orderBy('nama', 'asc')->get();

        // Calculate occupancy and damage data per facility
        $facilityStats = $facilities->map(function ($facility) {
            $totalHours = Reservation::where('facility_id', $facility->id)
                ->where('status', 'approved')
                ->get()
                ->sum(function ($res) {
                    return $res->start_time->diffInHours($res->end_time);
                });

            $reservationCount = Reservation::where('facility_id', $facility->id)
                ->where('status', 'approved')
                ->count();

            $damageCount = FacilityReport::where('facility_id', $facility->id)->count();

            return [
                'facility' => $facility,
                'total_hours' => $totalHours,
                'reservation_count' => $reservationCount,
                'damage_count' => $damageCount,
            ];
        });

        return view('admin.reports.index', compact('facilityStats'));
    }

    public function export(Request $request)
    {
        $request->validate([
            'format' => 'required|in:csv,excel,pdf',
        ]);

        $format = $request->format;
        $facilities = Facility::orderBy('nama', 'asc')->get();

        $data = $facilities->map(function ($facility) {
            $totalHours = Reservation::where('facility_id', $facility->id)
                ->where('status', 'approved')
                ->get()
                ->sum(function ($res) {
                    return $res->start_time->diffInHours($res->end_time);
                });

            $reservationCount = Reservation::where('facility_id', $facility->id)
                ->where('status', 'approved')
                ->count();

            $damageCount = FacilityReport::where('facility_id', $facility->id)->count();

            return [
                'nama' => $facility->nama,
                'tipe' => $facility->tipe,
                'lokasi' => $facility->lokasi,
                'total_jam' => $totalHours,
                'jumlah_reservasi' => $reservationCount,
                'jumlah_kerusakan' => $damageCount,
            ];
        });

        if ($format === 'csv') {
            return $this->exportCsv($data);
        }

        if ($format === 'excel') {
            return $this->exportCsv($data, 'xls');
        }

        // PDF export - return a view that can be printed as PDF
        return view('admin.reports.pdf', compact('data'));
    }

    private function exportCsv($data, $filename = 'rekap-fasilitas')
    {
        $filename = $filename . '_' . date('Y-m-d_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () use ($data) {
            $file = fopen('php://output', 'w');

            // Header row
            fputcsv($file, ['Nama Fasilitas', 'Tipe', 'Lokasi', 'Total Jam Okupansi', 'Jumlah Reservasi', 'Jumlah Kerusakan']);

            // Data rows
            foreach ($data as $row) {
                fputcsv($file, [
                    $row['nama'],
                    $row['tipe'],
                    $row['lokasi'],
                    $row['total_jam'],
                    $row['jumlah_reservasi'],
                    $row['jumlah_kerusakan'],
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
