@extends('layouts.main')

@section('title', 'DIPSpace - Daftar Fasilitas')

@section('content')
    <h1 class="page-title">Cek Ketersediaan Ruangan</h1>

    <div class="card" style="padding:0; overflow:hidden;">
        <div class="table-wrap" style="border:none; border-radius:0;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Tipe</th>
                        <th>Lokasi</th>
                        <th>Kapasitas</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($facilities as $facility)
                        <tr>
                            <td>{{ $facility->nama }}</td>
                            <td>{{ $facility->tipe }}</td>
                            <td>{{ $facility->lokasi }}</td>
                            <td>{{ $facility->kapasitas }}</td>
                            <td>
                                <span class="badge badge-{{ $facility->status }}">
                                    {{ ucwords(str_replace('_', ' ', $facility->status)) }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr class="empty-row">
                            <td colspan="5">Belum ada fasilitas.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
