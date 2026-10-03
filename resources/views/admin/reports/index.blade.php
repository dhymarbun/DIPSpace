@extends('layouts.main')

@section('title', 'Laporan & Rekap - DIPSpace')

@section('content')
    <h1 class="page-title">Laporan & Rekap Fasilitas</h1>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px; flex-wrap:wrap; gap:10px;">
            <p class="card-title">Rekap Okupansi & Frekuensi Kerusakan</p>
            <div style="display:flex; gap:8px;">
                <form method="get" action="{{ route('admin.reports.export') }}" style="display:inline;">
                    <input type="hidden" name="format" value="csv">
                    <button type="submit" class="btn btn-sm btn-outline-secondary">Export CSV</button>
                </form>
                <form method="get" action="{{ route('admin.reports.export') }}" style="display:inline;">
                    <input type="hidden" name="format" value="excel">
                    <button type="submit" class="btn btn-sm btn-outline-secondary">Export Excel</button>
                </form>
                <form method="get" action="{{ route('admin.reports.export') }}" style="display:inline;">
                    <input type="hidden" name="format" value="pdf">
                    <button type="submit" class="btn btn-sm btn-outline-secondary">Export PDF</button>
                </form>
            </div>
        </div>

        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Fasilitas</th>
                        <th>Tipe</th>
                        <th>Lokasi</th>
                        <th>Total Jam Okupansi</th>
                        <th>Jumlah Reservasi</th>
                        <th>Jumlah Kerusakan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($facilityStats as $stat)
                        <tr>
                            <td>{{ $stat['facility']->nama }}</td>
                            <td>{{ $stat['facility']->tipe }}</td>
                            <td>{{ $stat['facility']->lokasi }}</td>
                            <td>{{ $stat['total_hours'] }} jam</td>
                            <td>{{ $stat['reservation_count'] }}</td>
                            <td>{{ $stat['damage_count'] }}</td>
                        </tr>
                    @empty
                        <tr class="empty-row"><td colspan="6">Tidak ada data.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
