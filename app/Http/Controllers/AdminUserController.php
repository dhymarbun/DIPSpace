<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminUserController extends Controller
{
    // ========== FR-11: Register Petugas ==========

    public function createPetugas()
    {
        return view('admin.users.create-petugas');
    }

    public function storePetugas(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'petugas',
            'is_verified' => true,
        ]);

        return redirect()->route('admin.dashboard')->with('success', 'Akun petugas berhasil didaftarkan.');
    }

    // ========== FR-12: Register Pengguna ==========

    public function createPengguna()
    {
        return view('admin.users.create-pengguna');
    }

    public function storePengguna(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'user_type' => 'required|in:mahasiswa,dosen,staf',
        ]);

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'pengguna',
            'is_verified' => true,
        ]);

        return redirect()->route('admin.dashboard')->with('success', 'Akun pengguna berhasil didaftarkan.');
    }

    // ========== FR-13: Verify/Reject Users ==========

    public function pendingUsers()
    {
        // Menunggu verifikasi = belum diverifikasi & belum pernah ditolak.
        $pendingUsers = User::where('role', 'pengguna')
            ->where('is_verified', false)
            ->whereNull('rejection_reason')
            ->orderBy('created_at', 'desc')
            ->get();

        $rejectedUsers = User::where('role', 'pengguna')
            ->where('is_verified', false)
            ->whereNotNull('rejection_reason')
            ->orderBy('updated_at', 'desc')
            ->get();

        return view('admin.users.pending', compact('pendingUsers', 'rejectedUsers'));
    }

    public function verifyUser($id)
    {
        $user = User::where('role', 'pengguna')->findOrFail($id);
        $user->update([
            'is_verified' => true,
            'rejection_reason' => null,
        ]);

        return back()->with('success', "Akun {$user->email} berhasil diverifikasi.");
    }

    public function rejectUser(Request $request, $id)
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ], [
            'reason.required' => 'Alasan penolakan wajib diisi.',
        ]);

        $user = User::where('role', 'pengguna')->findOrFail($id);
        $user->update([
            'is_verified' => false,
            'rejection_reason' => $request->reason,
        ]);

        return back()->with('success', "Akun {$user->email} telah ditolak dan tidak dapat lagi mengakses sistem.");
    }
}
