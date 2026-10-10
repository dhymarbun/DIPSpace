<?php

namespace App\Http\Controllers;

use App\Models\Facility;
use App\Models\FacilityReport;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Http\Request;

class AdminDashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'users_total' => User::count(),
            'users_pengguna' => User::where('role', 'pengguna')->count(),
            'users_petugas' => User::where('role', 'petugas')->count(),
            'users_pending' => User::where('role', 'pengguna')->where('is_verified', false)->whereNull('rejection_reason')->count(),
            'facilities_total' => Facility::count(),
            'facilities_repair' => Facility::where('status', Facility::STATUS_DALAM_PERBAIKAN)->count(),
            'reservations_total' => Reservation::count(),
            'reservations_pending' => Reservation::where('status', 'pending')->count(),
            'reports_open' => FacilityReport::whereIn('status', ['pending', 'in_progress'])->count(),
        ];

        return view('admin.dashboard', compact('stats'));
    }
}
