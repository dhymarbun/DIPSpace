<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\FacilityController;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\PetugasDashboardController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\AdminFacilityController;
use App\Http\Controllers\AdminReportController;

Route::get('/', [FacilityController::class, 'index'])->name('home');

Route::middleware(['auth', 'verified'])->get('/dashboard', function () {
    $role = auth()->user()->role;
    if ($role === 'admin') {
        return redirect()->route('admin.dashboard');
    } elseif ($role === 'petugas') {
        return redirect()->route('petugas.dashboard');
    }
    return redirect()->route('home');
})->name('dashboard');

// ========== ADMIN ROUTES ==========
Route::middleware(['auth', 'verified', 'role:admin'])->group(function () {
    Route::get('/admin/dashboard', [AdminDashboardController::class, 'index'])->name('admin.dashboard');

    // FR-11 & FR-12: User Management
    Route::get('/admin/users/create-petugas', [AdminUserController::class, 'createPetugas'])->name('admin.users.createPetugas');
    Route::post('/admin/users/create-petugas', [AdminUserController::class, 'storePetugas'])->name('admin.users.storePetugas');
    Route::get('/admin/users/create-pengguna', [AdminUserController::class, 'createPengguna'])->name('admin.users.createPengguna');
    Route::post('/admin/users/create-pengguna', [AdminUserController::class, 'storePengguna'])->name('admin.users.storePengguna');

    // FR-13: Verify/Reject Users
    Route::get('/admin/users/pending', [AdminUserController::class, 'pendingUsers'])->name('admin.users.pending');
    Route::post('/admin/users/{id}/verify', [AdminUserController::class, 'verifyUser'])->name('admin.users.verify');
    Route::post('/admin/users/{id}/reject', [AdminUserController::class, 'rejectUser'])->name('admin.users.reject');

    // FR-14: Facility Management
    Route::get('/admin/facilities', [AdminFacilityController::class, 'index'])->name('admin.facilities.index');
    Route::get('/admin/facilities/create', [AdminFacilityController::class, 'create'])->name('admin.facilities.create');
    Route::post('/admin/facilities', [AdminFacilityController::class, 'store'])->name('admin.facilities.store');
    Route::get('/admin/facilities/{id}/edit', [AdminFacilityController::class, 'edit'])->name('admin.facilities.edit');
    Route::put('/admin/facilities/{id}', [AdminFacilityController::class, 'update'])->name('admin.facilities.update');
    Route::post('/admin/facilities/{id}/deactivate', [AdminFacilityController::class, 'deactivate'])->name('admin.facilities.deactivate');
    Route::post('/admin/facilities/{id}/activate', [AdminFacilityController::class, 'activate'])->name('admin.facilities.activate');

    // FR-15: Reports Export
    Route::get('/admin/reports', [AdminReportController::class, 'index'])->name('admin.reports.index');
    Route::get('/admin/reports/export', [AdminReportController::class, 'export'])->name('admin.reports.export');
});

// ========== PETUGAS ROUTES ==========
Route::middleware(['auth', 'verified', 'role:petugas'])->group(function () {
    Route::get('/petugas/dashboard', [PetugasDashboardController::class, 'index'])->name('petugas.dashboard');
    Route::post('/petugas/reservation', [PetugasDashboardController::class, 'processReservation'])->name('petugas.processReservation');
    Route::post('/petugas/report', [PetugasDashboardController::class, 'processReport'])->name('petugas.processReport');

    // FR-08: Cancel Approved Reservation
    Route::post('/petugas/reservation/cancel', [PetugasDashboardController::class, 'cancelReservation'])->name('petugas.cancelReservation');

    // FR-09: Update Report Status
    Route::post('/petugas/report/status', [PetugasDashboardController::class, 'updateReportStatus'])->name('petugas.updateReportStatus');

    // FR-10: Mark Facility Repair / Reactivate
    Route::post('/petugas/facility/{id}/mark-repair', [PetugasDashboardController::class, 'markFacilityRepair'])->name('petugas.markFacilityRepair');
    Route::post('/petugas/facility/{id}/reactivate', [PetugasDashboardController::class, 'reactivateFacility'])->name('petugas.reactivateFacility');
});

// ========== USER ROUTES ==========
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/reservations', [ReservationController::class, 'index'])->name('reservations.index');
    Route::post('/reservations', [ReservationController::class, 'store'])->name('reservations.store');
    Route::post('/reservations/cancel', [ReservationController::class, 'cancel'])->name('reservations.cancel');
    Route::get('/api/reservations/schedule', [ReservationController::class, 'getSchedule'])->name('reservations.schedule');

    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::post('/reports', [ReportController::class, 'store'])->name('reports.store');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
