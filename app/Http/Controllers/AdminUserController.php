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
        $pendingUsers = User::where('is_verified', false)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('admin.users.pending', compact('pendingUsers'));
    }

    public function verifyUser($id)
    {
        $user = User::findOrFail($id);
        $user->update(['is_verified' => true]);

        return back()->with('success', "Akun {$user->email} berhasil diverifikasi.");
    }

    public function rejectUser(Request $request, $id)
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $user = User::findOrFail($id);
        $user->update([
            'is_verified' => false,
            'rejection_reason' => $request->reason,
        ]);

        return back()->with('success', "Akun {$user->email} telah ditolak.");
    }
}
