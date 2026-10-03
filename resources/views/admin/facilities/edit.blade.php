@extends('layouts.main')

@section('title', 'Edit Fasilitas - DIPSpace')

@section('content')
    <h1 class="page-title">Edit Fasilitas</h1>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card">
        <p class="card-title">Form Edit Fasilitas</p>

        <form method="post" action="{{ route('admin.facilities.update', $facility->id) }}">
            @csrf
            @method('PUT')

            <div class="form-group">
                <label class="form-label" for="nama">Nama Fasilitas</label>
                <input type="text" id="nama" name="nama" class="form-control {{ $errors->has('nama') ? 'is-invalid' : '' }}" value="{{ old('nama', $facility->nama) }}" required>
                @error('nama') <span class="text-danger">{{ $message }}</span> @enderror
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="tipe">Tipe Fasilitas</label>
                    <input type="text" id="tipe" name="tipe" class="form-control {{ $errors->has('tipe') ? 'is-invalid' : '' }}" value="{{ old('tipe', $facility->tipe) }}" required>
                    @error('tipe') <span class="text-danger">{{ $message }}</span> @enderror
                </div>

                <div class="form-group">
                    <label class="form-label" for="lokasi">Lokasi</label>
                    <input type="text" id="lokasi" name="lokasi" class="form-control {{ $errors->has('lokasi') ? 'is-invalid' : '' }}" value="{{ old('lokasi', $facility->lokasi) }}" required>
                    @error('lokasi') <span class="text-danger">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="kapasitas">Kapasitas (orang)</label>
                    <input type="number" id="kapasitas" name="kapasitas" class="form-control {{ $errors->has('kapasitas') ? 'is-invalid' : '' }}" value="{{ old('kapasitas', $facility->kapasitas) }}" required min="1">
                    @error('kapasitas') <span class="text-danger">{{ $message }}</span> @enderror
                </div>

                <div class="form-group">
                    <label class="form-label" for="status">Status</label>
                    <select id="status" name="status" class="form-control {{ $errors->has('status') ? 'is-invalid' : '' }}" required>
                        @foreach (\App\Models\Facility::STATUSES as $status)
                            <option value="{{ $status }}" {{ old('status', $facility->status) === $status ? 'selected' : '' }}>
                                {{ ucfirst(str_replace('_', ' ', $status)) }}
                            </option>
                        @endforeach
                    </select>
                    <span class="form-hint">Pilih "Dalam perbaikan" bila fasilitas sedang rusak atau tidak dapat dipakai.</span>
                    @error('status') <span class="text-danger">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                <a href="{{ route('admin.facilities.index') }}" class="btn btn-outline-secondary">Batal</a>
            </div>
        </form>
    </div>
@endsection