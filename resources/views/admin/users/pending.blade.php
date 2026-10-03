@extends('layouts.main')

@section('title', 'Verifikasi Akun - DIPSpace')

@section('content')
    <h1 class="page-title">Akun Menunggu Verifikasi</h1>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card">
        <p class="card-title">Daftar Akun Pending</p>

        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Tanggal Daftar</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($pendingUsers as $user)
                        <tr>
                            <td>{{ $user->name }}</td>
                            <td>{{ $user->email }}</td>
                            <td>{{ ucfirst($user->role) }}</td>
                            <td>{{ $user->created_at->format('Y-m-d H:i') }}</td>
                            <td>
                                <div style="display:flex; gap:8px; flex-wrap:wrap;">
                                    <form method="post" action="{{ route('admin.users.verify', $user->id) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('Verifikasi akun ini?')">Verifikasi</button>
                                    </form>
                                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="document.getElementById('reject-{{ $user->id }}').style.display='block'">Tolak</button>
                                </div>
                                <div id="reject-{{ $user->id }}" style="display:none; margin-top:8px;">
                                    <form method="post" action="{{ route('admin.users.reject', $user->id) }}">
                                        @csrf
                                        <input type="text" name="reason" class="form-control" placeholder="Alasan penolakan..." required style="margin-bottom:8px;">
                                        <button type="submit" class="btn btn-sm btn-danger">Konfirmasi Tolak</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr class="empty-row"><td colspan="5">Tidak ada akun yang menunggu verifikasi.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
