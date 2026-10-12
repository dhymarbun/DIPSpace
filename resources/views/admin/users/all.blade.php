@extends('layouts.main')

@section('title', 'Semua Akun - DIPSpace')

@section('content')
    <h1 class="page-title">Semua Akun</h1>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card">
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                        <tr>
                            <td>{{ $user->name }}</td>
                            <td>{{ $user->email }}</td>
                            <td>{{ ucfirst($user->role) }}</td>
                            <td>
                                @if ($user->is_verified)
                                    <span class="badge badge-success">Terverifikasi</span>
                                @else
                                    <span class="badge badge-warning">Belum Verifikasi</span>
                                @endif
                            </td>
                            <td>
                                <div style="display:flex; gap:8px; flex-wrap:wrap;">
                                    <form method="post" action="{{ route('admin.users.resetPassword', $user->id) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-secondary" onclick="return confirm('Reset password akun ini ke password123?')">Reset Password</button>
                                    </form>
                                    <form method="post" action="{{ route('admin.users.delete', $user->id) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus akun ini? Tindakan ini tidak dapat dibatalkan.')">Hapus</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr class="empty-row"><td colspan="5">Tidak ada akun.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div style="margin-top: 20px;">
        <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-secondary">Kembali ke Dashboard</a>
    </div>
@endsection

