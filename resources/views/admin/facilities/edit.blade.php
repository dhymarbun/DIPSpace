@extends('layouts.main')

@section('title', 'Edit Fasilitas - DIPSpace')

@section('content')
    <h1 class="page-title">Edit Fasilitas</h1>

    <div class="card">
        <p class="card-title">Form Edit Fasilitas</p>

        <form method="post" action="{{ route('admin.facilities.update', $facility->id) }}">
            @csrf
            @method('PUT')

            <div class="form-group">
                <label for="nama">Nama Fasilitas</label>
                <input type="text" id="nama" name="nama" value="{{ old('nama', $facility->nama) }}" required>
                @error('nama') <span class="text-danger">{{ $message }}</span> @enderror
            </div>

            <div class="form-group">
                <label for="tipe">Tipe Fasilitas</label>
                <input type="text" id="tipe" name="tipe" value="{{ old('tipe', $facility->tipe) }}" required>
                @error('tipe') <span class="text-danger">{{ $message }}</span> @enderror
            </div>

            <div class="form-group">
                <label for="lokasi">Lokasi</label>
                <input type="text" id="lokasi" name="lokasi" value="{{ old('lokasi', $facility->lokasi) }}" required>
                @error('lokasi') <span class="text-danger">{{ $message }}</span> @enderror
            </div>

            <div class="form-group">
                <label for="kapasitas">Kapasitas (orang)</label>
                <input type="number" id="kapasitas" name="kapasitas" value="{{ old('kapasitas', $facility->kapasitas) }}" required min="1">
                @error('kapasitas') <span class="text-danger">{{ $message }}</span> @enderror
            </div>

            <div style="display:flex; gap:10px;">
                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                <a href="{{ route('admin.facilities.index') }}" class="btn btn-outline-secondary">Batal</a>
            </div>
        </form>
    </div>
