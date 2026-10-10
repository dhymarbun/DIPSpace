@extends('layouts.main')

@section('title', 'Laporkan Kerusakan - DIPSpace')

@section('content')
    <h1 class="page-title">Laporkan Kerusakan Fasilitas</h1>

    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
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
        <form method="post" action="{{ route('reports.store') }}" enctype="multipart/form-data">
            @csrf
            <div class="form-row">
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
                <div class="form-group">
                    <label class="form-label" for="category">Kategori Kerusakan</label>
                    <select class="form-control" id="category" name="category" required>
                        <option value="">Kecil / Sedang / Parah</option>
                        <option value="kecil" {{ old('category') == 'kecil' ? 'selected' : '' }}>Kecil</option>
                        <option value="sedang" {{ old('category') == 'sedang' ? 'selected' : '' }}>Sedang</option>
                        <option value="parah" {{ old('category') == 'parah' ? 'selected' : '' }}>Parah</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label" for="report_date">Tanggal</label>
                <input type="date" class="form-control" id="report_date" name="report_date" value="{{ old('report_date', date('Y-m-d')) }}" required>
            </div>
            <div class="form-group">
                <label class="form-label" for="description">Deskripsi Masalah</label>
                <textarea class="form-control" id="description" name="description" rows="5" maxlength="2000" placeholder="Jelaskan kondisi kerusakan, lokasi spesifik, dan dampaknya terhadap penggunaan fasilitas." required>{{ old('description') }}</textarea>
            </div>
            <div class="form-group">
                <label class="form-label" for="photo">Foto</label>
                <input type="file" class="form-control" id="photo" name="photo" accept=".jpg,.jpeg,.png,.webp">
                <p class="form-hint">Opsional · JPG/PNG/WEBP, maks. 5MB</p>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Kirim Laporan</button>
            </div>
        </form>
    </div>

    <div class="card">
        <p class="card-title">Riwayat Laporan Kerusakan Saya</p>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Fasilitas</th>
                        <th>Kategori</th>
                        <th>Deskripsi</th>
                        <th>Foto</th>
                        <th>Status</th>
                        <th>Catatan Resolusi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($myReports as $report)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($report->report_date)->format('d/m/Y') }}</td>
                            <td>{{ $report->facility->nama }}</td>
                            <td><span class="badge badge-{{ $report->category }}">{{ ucfirst($report->category) }}</span></td>
                            <td style="max-width: 300px; white-space: normal;">{{ $report->description }}</td>
                            <td>
                                @if($report->photo_path)
                                    <a href="{{ asset($report->photo_path) }}" target="_blank" class="btn btn-sm btn-outline-primary">Lihat Foto</a>
                                @else
                                    <span style="color: #9ca3af;">Tidak ada</span>
                                @endif
                            </td>
                            <td>
                                @if($report->status === 'pending')
                                    <span class="badge badge-warning">Menunggu</span>
                                @elseif($report->status === 'in_progress')
                                    <span class="badge badge-info">Diproses</span>
                                @elseif($report->status === 'resolved')
                                    <span class="badge badge-success">Selesai</span>
                                @elseif($report->status === 'rejected')
                                    <span class="badge badge-danger">Ditolak</span>
                                @endif
                            </td>
                            <td style="max-width: 250px; white-space: normal;">
                                {{ $report->resolution_notes ?? '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr class="empty-row">
                            <td colspan="7">Belum ada laporan kerusakan yang Anda kirimkan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
