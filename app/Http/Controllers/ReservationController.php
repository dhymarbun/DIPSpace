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
            'start_date' => 'required|date|date_format:Y-m-d',
            'start_time' => 'required|date_format:H:i',
            'end_date' => 'required|date|date_format:Y-m-d',
            'end_time' => 'required|date_format:H:i',
            'tujuan' => 'required|string|max:255',
        ]);

        $startTime = Carbon::createFromFormat('Y-m-d H:i', $request->start_date . ' ' . $request->start_time);
        $endTime = Carbon::createFromFormat('Y-m-d H:i', $request->end_date . ' ' . $request->end_time);

        if ($startTime->lt(now())) {
            return back()->withInput()->with('error', 'Waktu mulai tidak boleh di masa lampau.');
        }

        if ($endTime->lte($startTime)) {
            return back()->withInput()->with('error', 'Waktu selesai harus lebih besar dari waktu mulai.');
        }

        if ($startTime->hour < 7 || $startTime->hour >= 20 || !in_array($startTime->minute, [0, 30])) {
            return back()->withInput()->with('error', 'Waktu mulai harus antara 07:00 hingga sebelum 20:00 dengan slot 30 menit (00 atau 30).');
        }

        if ($endTime->hour < 7 || $endTime->hour > 20 || ($endTime->hour == 20 && $endTime->minute > 0) || !in_array($endTime->minute, [0, 30])) {
            return back()->withInput()->with('error', 'Waktu selesai harus antara 07:00 hingga 20:00 dengan slot 30 menit (00 atau 30).');
        }



        // Cek bentrok dengan reservasi yang approved atau pending
        $hasOverlap = Reservation::where('facility_id', $request->facility_id)
            ->whereIn('status', ['approved', 'pending'])
            ->where(function ($query) use ($startTime, $endTime) {
                $query->whereBetween('start_time', [$startTime, $endTime])
                    ->orWhereBetween('end_time', [$startTime, $endTime])
                    ->orWhere(function ($q) use ($startTime, $endTime) {
                        $q->where('start_time', '<=', $startTime)
                          ->where('end_time', '>=', $endTime);
                    });
            })
            ->exists();

        if ($hasOverlap) {
            return back()->withInput()->with('error', 'Jadwal tersebut sudah terpakai. Silakan pilih waktu lain atau cek jadwal reservasi di bawah.');
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

    public function getSchedule(Request $request)
    {
        $request->validate([
            'facility_id' => 'required|exists:facilities,id',
            'month' => 'nullable|integer|min:1|max:12',
            'year' => 'nullable|integer|min:2026',
        ]);

        $month = $request->month ?? now()->month;
        $year = $request->year ?? now()->year;

        $startDate = Carbon::createFromDate($year, $month, 1)->startOfDay();
        $endDate = $startDate->clone()->endOfMonth()->endOfDay();

        $reservations = Reservation::where('facility_id', $request->facility_id)
            ->whereIn('status', ['pending', 'approved'])
            ->whereBetween('start_time', [$startDate, $endDate])
            ->get(['id', 'start_time', 'end_time', 'status']);

        $schedule = $reservations->map(function ($res) {
            return [
                'date' => $res->start_time->format('Y-m-d'),
                'start_time' => $res->start_time->format('H:i'),
                'end_time' => $res->end_time->format('H:i'),
                'status' => $res->status,
            ];
        });

        return response()->json(['reservations' => $schedule]);
    }
}
