<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use App\Models\FacilityReport;
use App\Models\Facility;
use Illuminate\Http\Request;

class PetugasDashboardController extends Controller
{
    public function index()
    {
        $status = request('status');
        
        $reservationQuery = Reservation::with(['facility', 'user']);
        $reportQuery = FacilityReport::with(['facility', 'user']);

        if ($status) {
            $reservationQuery->where('status', $status);
            $reportQuery->where('status', $status);
        }

        $pendingReservations = $reservationQuery->orderBy('created_at', 'desc')->get();
        $pendingReports = $reportQuery->orderBy('created_at', 'desc')->get();

        return view('petugas.dashboard', compact('pendingReservations', 'pendingReports', 'status'));
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

    // ========== FR-08: Cancel Approved Reservation ==========

    public function cancelReservation(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:reservations,id',
            'cancellation_reason' => 'required|string|max:500',
        ]);

        $reservation = Reservation::where('id', $request->id)
            ->where('status', 'approved')
            ->first();

        if (!$reservation) {
            return back()->with('error', 'Reservasi tidak ditemukan atau tidak berstatus approved.');
        }

        if ($reservation->start_time <= now()) {
            return back()->with('error', 'Reservasi sudah berlangsung atau selesai, tidak dapat dibatalkan.');
        }

        $reservation->update([
            'status' => 'cancelled',
            'cancellation_reason' => $request->cancellation_reason,
        ]);

        return back()->with('success', 'Reservasi berhasil dibatalkan.');
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

    // ========== FR-09: Update Report Status with Resolution Notes ==========

    public function updateReportStatus(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:facility_reports,id',
            'status' => 'required|in:pending,in_progress,resolved,rejected',
            'resolution_notes' => 'nullable|string|max:1000',
        ]);

        $report = FacilityReport::findOrFail($request->id);

        // If status is resolved or rejected, resolution_notes is required
        if (in_array($request->status, ['resolved', 'rejected']) && empty($request->resolution_notes)) {
            return back()->with('error', 'Catatan resolusi wajib diisi ketika laporan ditutup (selesai/ditolak).');
        }

        $report->update([
            'status' => $request->status,
            'resolution_notes' => $request->resolution_notes,
        ]);

        return back()->with('success', 'Status laporan berhasil diperbarui.');
    }

    // ========== FR-10: Mark Facility as Under Repair / Reactivate ==========

    public function markFacilityRepair($id)
    {
        $facility = Facility::findOrFail($id);

        // Check if there's an in_progress report for this facility
        $hasActiveReport = FacilityReport::where('facility_id', $id)
            ->where('status', 'in_progress')
            ->exists();

        if (!$hasActiveReport) {
            return back()->with('error', 'Tidak ada laporan kerusakan yang sedang diproses untuk fasilitas ini.');
        }

        $facility->update(['status' => 'dalam_perbaikan']);

        return back()->with('success', "Fasilitas {$facility->nama} ditandai sedang dalam perbaikan.");
    }

    public function reactivateFacility($id)
    {
        $facility = Facility::findOrFail($id);

        if ($facility->status !== 'dalam_perbaikan') {
            return back()->with('error', 'Fasilitas tidak sedang dalam perbaikan.');
        }

        $facility->update(['status' => 'aktif']);

        return back()->with('success', "Fasilitas {$facility->nama} berhasil diaktifkan kembali.");
    }
}
