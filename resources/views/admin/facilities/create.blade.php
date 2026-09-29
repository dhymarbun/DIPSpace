@extends('layouts.main')

@section('title', 'Tambah Fasilitas - DIPSpace')

@section('content')
    <h1 class="page-title">Tambah Fasilitas Baru</h1>

    <div class="card">
        <p class="card-title">Form Tambah Fasilitas</p>

        <form method="post" action="{{ route('admin.facilities.store') }}">
            @csrf

            <div class="form-group">
                <label for="nama">Nama Fasilitas</label>
                <input type="text" id="nama" name="nama" value="{{ old('nama') }}" required>
                @error('nama') <span class="text-danger">{{ $message }}</span> @enderror
            </div>

            <div class="form-group">
                <label for="tipe">Tipe Fasilitas</label>
                <input type="text" id="tipe" name="tipe" value="{{ old('tipe') }}" required placeholder="Ruang Kelas, Aula, Laboratorium, dll.">
                @error('tipe') <span class="text-danger">{{ $message }}</span> @enderror
            </div>

            <div class="form-group">
                <label for="lokasi">Lokasi</label>
                <input type="text" id="lokasi" name="lokasi" value="{{ old('lokasi') }}" required>
                @error('lokasi') <span class="text-danger">{{ $message }}</span> @enderror
            </div>

            <div class="form-group">
                <label for="kapasitas">Kapasitas (orang)</label>
                <input type="number" id="kapasitas" name="kapasitas" value="{{ old('kapasitas') }}" required min="1">
                @error('kapasitas') <span class="text-danger">{{ $message }}</span> @enderror
            </div>

            <div style="display:flex; gap:10px;">
                <button type="submit" class="btn btn-primary">Simpan</button>
                <a href="{{ route('admin.facilities.index') }}" class="btn btn-outline-secondary">Batal</a>
            </div>
        </form>
    </div>
