@extends('layouts.main')

@section('title', 'Reservasi - DIPSpace')

@section('content')
    <h1 class="page-title">Ajukan Reservasi Fasilitas</h1>

    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    @if (session('warning'))
        <div class="alert alert-danger">{{ session('warning') }}</div>
    @endif
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">
            @foreach ($errors->all() as $err)
                <div>{{ $err }}</div>
            @endforeach
        </div>
    @endif

    <div class="card">
        <form method="post" action="{{ route('reservations.store') }}">
            @csrf
            <div class="form-group">
                <label class="form-label" for="facility_id">Fasilitas</label>
                <select class="form-control" id="facility_id" name="facility_id" required>
                    <option value="">Pilih fasilitas</option>
                    @foreach ($facilities as $facility)
                        <option value="{{ $facility->id }}" {{ old('facility_id') == $facility->id ? 'selected' : '' }}>
                            {{ $facility->nama }} — {{ $facility->lokasi }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="start_time">Waktu Mulai</label>
                    <input type="datetime-local" class="form-control" id="start_time" name="start_time" value="{{ old('start_time') }}" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="end_time">Waktu Selesai</label>
                    <input type="datetime-local" class="form-control" id="end_time" name="end_time" value="{{ old('end_time') }}" required>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label" for="tujuan">Tujuan Penggunaan</label>
                <input type="text" class="form-control" id="tujuan" name="tujuan" maxlength="255" placeholder="Contoh: Rapat koordinasi organisasi mahasiswa" value="{{ old('tujuan') }}" required>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Ajukan Reservasi</button>
            </div>
        </form>
    </div>

    <div class="card">
        <p class="card-title">Riwayat Reservasi Saya</p>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Fasilitas</th>
                        <th>Waktu Mulai</th>
                        <th>Waktu Selesai</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($reservations as $res)
                        @php
                            $canCancel = in_array($res->status, ['pending', 'approved'], true) && $res->start_time > now();
                        @endphp
                        <tr>
                            <td>{{ $res->facility->nama }}</td>
                            <td>{{ $res->start_time->format('Y-m-d H:i:s') }}</td>
                            <td>{{ $res->end_time->format('Y-m-d H:i:s') }}</td>
                            <td>
                                <span class="badge badge-{{ $res->status }}">
                                    {{ ucfirst($res->status) }}
                                </span>
                            </td>
                            <td>
                                @if ($canCancel)
                                    <form method="post" action="{{ route('reservations.cancel') }}" onsubmit="return confirm('Apakah Anda yakin ingin membatalkan reservasi ini?');">
                                        @csrf
                                        <input type="hidden" name="id" value="{{ $res->id }}">
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Batalkan</button>
                                    </form>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr class="empty-row">
                            <td colspan="5">Belum ada reservasi.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div style="text-align:center; margin-top:24px;">
        <a href="{{ route('reports.index') }}" class="btn btn-outline-danger">Ada fasilitas yang rusak? Laporkan sekarang!</a>
    </div>
@endsection
