<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use App\Models\FacilityReport;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $pendingReservations = Reservation::with(['facility', 'user'])
            ->where('status', 'pending')
            ->orderBy('created_at', 'asc')
            ->get();

        $pendingReports = FacilityReport::with(['facility', 'user'])
            ->where('status', 'pending')
            ->orderBy('created_at', 'asc')
            ->get();

        return view('dashboard', compact('pendingReservations', 'pendingReports'));
    }

    public function processReservation(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:reservations,id',
            'action' => 'required|in:approve,reject',
        ]);

        $reservation = Reservation::where('id', $request->id)->where('status', 'pending')->first();

        if (!$reservation) {
            return back()->with('error', 'Reservasi tidak ditemukan atau sudah diproses.');
        }

        $reservation->update([
            'status' => $request->action === 'approve' ? 'approved' : 'rejected'
        ]);

        $message = $request->action === 'approve' ? 'Reservasi berhasil disetujui.' : 'Reservasi berhasil ditolak.';
        return back()->with('success', $message);
    }

    public function processReport(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:facility_reports,id',
            'action' => 'required|in:start_report,reject_report',
        ]);

        $report = FacilityReport::where('id', $request->id)->where('status', 'pending')->first();

        if (!$report) {
            return back()->with('error', 'Laporan tidak ditemukan atau sudah diproses.');
        }

        $report->update([
            'status' => $request->action === 'start_report' ? 'in_progress' : 'rejected'
        ]);

        $message = $request->action === 'start_report' ? 'Laporan ditandai sedang diproses.' : 'Laporan berhasil ditolak.';
        return back()->with('success', $message);
    }
}
