@extends('layouts.main')

@section('title', 'Antrian Petugas - DIPSpace')

@section('content')
    @if(Auth::user()->role === 'petugas')
        <h1 class="page-title">Dashboard Petugas</h1>
        <div class="card">
            <p>Selamat datang, Petugas. Halaman ini sedang dalam pengembangan.</p>
        </div>
    @else
        <h1 class="page-title">Antrian yang Menunggu Diproses</h1>

        @if (session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif
        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div class="card">
            <p class="card-title">Reservasi Menunggu Persetujuan</p>
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Fasilitas</th>
                            <th>Lokasi</th>
                            <th>Peminjam</th>
                            <th>Mulai</th>
                            <th>Selesai</th>
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
                                    <form method="post" action="{{ route('dashboard.processReservation') }}" style="display:flex; gap:8px;">
                                        @csrf
                                        <input type="hidden" name="id" value="{{ $res->id }}">
                                        <button class="btn btn-sm btn-success" name="action" value="approve" type="submit">Ya</button>
                                        <button class="btn btn-sm btn-outline-danger" name="action" value="reject" type="submit">Tidak</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr class="empty-row"><td colspan="6">Tidak ada reservasi yang menunggu.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <p class="card-title">Laporan Kerusakan Menunggu Diproses</p>
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
                                    <form method="post" action="{{ route('dashboard.processReport') }}" style="display:flex; gap:8px;">
                                        @csrf
                                        <input type="hidden" name="id" value="{{ $rep->id }}">
                                        <button class="btn btn-sm btn-success" name="action" value="start_report" type="submit">Proses</button>
                                        <button class="btn btn-sm btn-outline-danger" name="action" value="reject_report" type="submit">Tolak</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr class="empty-row"><td colspan="8">Tidak ada laporan yang menunggu.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif
@endsection
