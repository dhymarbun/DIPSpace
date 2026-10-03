@extends('layouts.main')

@section('title', 'Tambah Fasilitas - DIPSpace')

@section('content')
    <h1 class="page-title">Tambah Fasilitas Baru</h1>

    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card">
        <p class="card-title">Form Tambah Fasilitas</p>

        <form method="post" action="{{ route('admin.facilities.store') }}">
            @csrf

            <div class="form-group">
                <label class="form-label" for="nama">Nama Fasilitas</label>
                <input type="text" id="nama" name="nama" class="form-control {{ $errors->has('nama') ? 'is-invalid' : '' }}" value="{{ old('nama') }}" required>
                @error('nama') <span class="text-danger">{{ $message }}</span> @enderror
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="tipe">Tipe Fasilitas</label>
                    <input type="text" id="tipe" name="tipe" class="form-control {{ $errors->has('tipe') ? 'is-invalid' : '' }}" value="{{ old('tipe') }}" required placeholder="Ruang Kelas, Aula, Laboratorium, dll.">
                    @error('tipe') <span class="text-danger">{{ $message }}</span> @enderror
                </div>

                <div class="form-group">
                    <label class="form-label" for="lokasi">Lokasi</label>
                    <input type="text" id="lokasi" name="lokasi" class="form-control {{ $errors->has('lokasi') ? 'is-invalid' : '' }}" value="{{ old('lokasi') }}" required>
                    @error('lokasi') <span class="text-danger">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="kapasitas">Kapasitas (orang)</label>
                    <input type="number" id="kapasitas" name="kapasitas" class="form-control {{ $errors->has('kapasitas') ? 'is-invalid' : '' }}" value="{{ old('kapasitas') }}" required min="1">
                    @error('kapasitas') <span class="text-danger">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Simpan</button>
                <a href="{{ route('admin.facilities.index') }}" class="btn btn-outline-secondary">Batal</a>
            </div>
        </form>
    </div>
@endsection