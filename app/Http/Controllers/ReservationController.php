<?php

namespace App\Http\Controllers;

use App\Models\Facility;
use App\Models\Reservation;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReservationController extends Controller
{
    public function index()
    {
        $facilities = Facility::orderBy('nama', 'asc')->get();
        $reservations = Reservation::with('facility')
            ->where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->get();

        return view('reservations.index', compact('facilities', 'reservations'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'facility_id' => 'required|exists:facilities,id',
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
            'tujuan' => 'required|string|max:255',
        ]);

        $startTime = Carbon::parse($request->start_time);
        $endTime = Carbon::parse($request->end_time);

        // Validasi slot 30 menit & jam operasional (07:00 - 20:00)
        $isValidStart = $startTime->hour >= 7 && $startTime->hour <= 20 && in_array($startTime->minute, [0, 30]);
        $isValidEnd = $endTime->hour >= 7 && $endTime->hour <= 20 && in_array($endTime->minute, [0, 30]);

        if (!$isValidStart || !$isValidEnd) {
            return back()->withInput()->with('error', 'Waktu reservasi harus berada antara pukul 07:00 hingga 20:00 dan menggunakan slot 30 menit.');
        }

        // Cek bentrok dengan reservasi yang approved
        $hasOverlap = Reservation::where('facility_id', $request->facility_id)
            ->where('status', 'approved')
            ->where('start_time', '<', $endTime)
            ->where('end_time', '>', $startTime)
            ->exists();

        if ($hasOverlap) {
            return back()->withInput()->with('error', 'Jadwal tersebut bertabrakan dengan reservasi yang sudah disetujui.');
        }

        Reservation::create([
            'user_id' => Auth::id(),
            'facility_id' => $request->facility_id,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'tujuan' => $request->tujuan,
            'status' => 'pending',
        ]);

        return redirect()->route('reservations.index')->with('success', 'Reservasi berhasil dikirim dan menunggu persetujuan.');
    }

    public function cancel(Request $request)
    {
        $reservation = Reservation::where('id', $request->id)
            ->where('user_id', Auth::id())
            ->first();

        if (!$reservation) {
            return back()->with('warning', 'Reservasi tidak ditemukan atau bukan milik Anda.');
        }

        if (!in_array($reservation->status, ['pending', 'approved']) || $reservation->start_time <= now()) {
            return back()->with('warning', 'Reservasi sudah tidak dapat dibatalkan.');
        }

        $reservation->update(['status' => 'cancelled']);

        return redirect()->route('reservations.index')->with('success', 'Reservasi berhasil dibatalkan.');
    }
}
