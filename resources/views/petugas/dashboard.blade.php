@extends('layouts.main')

@section('title', 'Antrian Petugas - DIPSpace')

@section('content')
    <h1 class="page-title">Antrian yang Menunggu Diproses</h1>

    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card">
        <p class="card-title">Reservasi</p>
        
        <div style="margin-bottom: 15px; display: flex; gap: 10px; flex-wrap: wrap;">
            <a href="?status=" class="btn {{ !request('status') ? 'btn-primary' : 'btn-outline-secondary' }}">Semua</a>
            <a href="?status=pending" class="btn {{ request('status') === 'pending' ? 'btn-primary' : 'btn-outline-secondary' }}">Pending</a>
            <a href="?status=approved" class="btn {{ request('status') === 'approved' ? 'btn-primary' : 'btn-outline-secondary' }}">Approved</a>
            <a href="?status=rejected" class="btn {{ request('status') === 'rejected' ? 'btn-primary' : 'btn-outline-secondary' }}">Rejected</a>
        </div>

        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Fasilitas</th>
                        <th>Lokasi</th>
                        <th>Peminjam</th>
                        <th>Mulai</th>
                        <th>Selesai</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($pendingReservations as $res)
                        <tr>
                            <td>{{ $res->facility->nama }}</td>
                            <td>{{ $res->facility->lokasi }}</td>
                            <td>{{ $res->user->email }}</td>
                            <td>{{ $res->start_time->format('Y-m-d H:i:s') }}</td>
                            <td>{{ $res->end_time->format('Y-m-d H:i:s') }}</td>
                            <td>
                                <span class="badge badge-{{ $res->status }}">
                                    {{ ucfirst($res->status) }}
                                </span>
                            </td>
                            <td>
                                <form method="post" action="{{ route('petugas.processReservation') }}" style="display:flex; gap:8px;">
                                    @csrf
                                    <input type="hidden" name="id" value="{{ $res->id }}">
                                    <button class="btn btn-sm btn-success" name="action" value="approve" type="submit" onclick="return confirm('Apakah Anda yakin ingin menyetujui?')">Ya</button>
                                    <button class="btn btn-sm btn-outline-danger" name="action" value="reject" type="submit" onclick="return confirm('Apakah Anda yakin ingin menolak?')">Tidak</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr class="empty-row"><td colspan="7">Tidak ada reservasi.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <p class="card-title">Laporan Kerusakan</p>
        
        <div style="margin-bottom: 15px; display: flex; gap: 10px; flex-wrap: wrap;">
            <a href="?status=" class="btn {{ !request('status') ? 'btn-primary' : 'btn-outline-secondary' }}">Semua</a>
            <a href="?status=pending" class="btn {{ request('status') === 'pending' ? 'btn-primary' : 'btn-outline-secondary' }}">Pending</a>
            <a href="?status=in_progress" class="btn {{ request('status') === 'in_progress' ? 'btn-primary' : 'btn-outline-secondary' }}">In Progress</a>
            <a href="?status=rejected" class="btn {{ request('status') === 'rejected' ? 'btn-primary' : 'btn-outline-secondary' }}">Rejected</a>
        </div>

        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Fasilitas</th>
                        <th>Lokasi</th>
                        <th>Pelapor</th>
                        <th>Kategori</th>
                        <th>Tanggal</th>
                        <th>Deskripsi</th>
                        <th>Foto</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($pendingReports as $rep)
                        <tr>
                            <td>{{ $rep->facility->nama }}</td>
                            <td>{{ $rep->facility->lokasi }}</td>
                            <td>{{ $rep->user->email }}</td>
                            <td>{{ ucfirst($rep->category) }}</td>
                            <td>{{ $rep->report_date->format('Y-m-d') }}</td>
                            <td>{{ $rep->description }}</td>
                            <td>
                                @if ($rep->photo_path)
                                    <a href="{{ asset($rep->photo_path) }}" target="_blank" rel="noopener" style="color:var(--navy); font-weight:600;">Lihat</a>
                                @else
                                    -
                                @endif
                            </td>
                            <td>
                                <span class="badge badge-{{ $rep->status }}">
                                    {{ ucfirst(str_replace('_', ' ', $rep->status)) }}
                                </span>
                            </td>
                            <td>
                                <form method="post" action="{{ route('petugas.processReport') }}" style="display:flex; gap:8px;">
                                    @csrf
                                    <input type="hidden" name="id" value="{{ $rep->id }}">
                                    <button class="btn btn-sm btn-success" name="action" value="start_report" type="submit" onclick="return confirm('Apakah Anda yakin ingin memproses laporan ini?')">Proses</button>
                                    <button class="btn btn-sm btn-outline-danger" name="action" value="reject_report" type="submit" onclick="return confirm('Apakah Anda yakin ingin menolak laporan ini?')">Tolak</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr class="empty-row"><td colspan="9">Tidak ada laporan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
