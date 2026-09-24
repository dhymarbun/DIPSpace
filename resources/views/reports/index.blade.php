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
@endsection
