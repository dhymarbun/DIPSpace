@extends('layouts.main')

@section('title', 'Kelola Fasilitas - DIPSpace')

@section('content')
    <h1 class="page-title">Kelola Fasilitas</h1>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
            <p class="card-title">Daftar Fasilitas</p>
            <a href="{{ route('admin.facilities.create') }}" class="btn btn-primary">+ Tambah Fasilitas</a>
        </div>

        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Tipe</th>
                        <th>Lokasi</th>
                        <th>Kapasitas</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($facilities as $facility)
                        <tr>
                            <td>{{ $facility->nama }}</td>
                            <td>{{ $facility->tipe }}</td>
                            <td>{{ $facility->lokasi }}</td>
                            <td>{{ $facility->kapasitas }}</td>
                            <td>
                                <span class="badge badge-{{ $facility->status == 'aktif' ? 'approved' : 'pending' }}">
                                    {{ ucfirst(str_replace('_', ' ', $facility->status)) }}
                                </span>
                            </td>
                            <td>
                                <div style="display:flex; gap:8px; flex-wrap:wrap;">
                                    <a href="{{ route('admin.facilities.edit', $facility->id) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                                    @if ($facility->status == 'aktif')
                                        <form method="post" action="{{ route('admin.facilities.deactivate', $facility->id) }}">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Nonaktifkan fasilitas ini?')">Nonaktifkan</button>
                                        </form>
                                    @else
                                        <form method="post" action="{{ route('admin.facilities.activate', $facility->id) }}">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-success">Aktifkan</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr class="empty-row"><td colspan="6">Tidak ada fasilitas.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
