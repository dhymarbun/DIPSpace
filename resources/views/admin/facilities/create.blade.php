@extends('layouts.main')

@section('title', 'Tambah Fasilitas - DIPSpace')

@section('content')
    <h1 class="page-title">Tambah Fasilitas Baru</h1>

    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card">
        <p class="card-title">Form Tambah Fasilitas</p>

        <form method="post" action="{{ route('admin.facilities.store') }}" enctype="multipart/form-data">
            @csrf

            <div class="form-group">
                <label class="form-label" for="nama">Nama Fasilitas</label>
                <input type="text" id="nama" name="nama" class="form-control {{ $errors->has('nama') ? 'is-invalid' : '' }}" value="{{ old('nama') }}" required>
                @error('nama') <span class="text-danger">{{ $message }}</span> @enderror
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="tipe">Tipe Fasilitas</label>
                    <input type="text" id="tipe" name="tipe" list="tipe-list" class="form-control {{ $errors->has('tipe') ? 'is-invalid' : '' }}" value="{{ old('tipe') }}" required placeholder="Ruang Kelas, Aula, Laboratorium, dll.">
                    <datalist id="tipe-list">
                        @foreach($existingTipes as $tipe)
                            <option value="{{ $tipe }}"></option>
                        @endforeach
                    </datalist>
                    @error('tipe') <span class="text-danger">{{ $message }}</span> @enderror
                </div>

                <div class="form-group">
                    <label class="form-label" for="lokasi">Lokasi</label>
                    <input type="text" id="lokasi" name="lokasi" list="lokasi-list" class="form-control {{ $errors->has('lokasi') ? 'is-invalid' : '' }}" value="{{ old('lokasi') }}" required>
                    <datalist id="lokasi-list">
                        @foreach($existingLokasis as $lokasi)
                            <option value="{{ $lokasi }}"></option>
                        @endforeach
                    </datalist>
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

            <div class="form-group">
                <label class="form-label" for="deskripsi">Deskripsi (Tentang Fasilitas)</label>
                <textarea id="deskripsi" name="deskripsi" rows="4" class="form-control {{ $errors->has('deskripsi') ? 'is-invalid' : '' }}" placeholder="Jelaskan fasilitas ini untuk ditampilkan pada halaman detail.">{{ old('deskripsi') }}</textarea>
                <span class="form-hint">Tampil pada bagian &ldquo;Tentang fasilitas&rdquo; di halaman detail. Kosongkan untuk memakai deskripsi otomatis sesuai tipe fasilitas.</span>
                @error('deskripsi') <span class="text-danger">{{ $message }}</span> @enderror
            </div>

            <div class="form-group">
                <label class="form-label" for="foto">Foto Fasilitas</label>
                <input type="file" id="foto" name="foto" class="form-control {{ $errors->has('foto') ? 'is-invalid' : '' }}" accept="image/png,image/jpeg,image/webp">
                <span class="form-hint">Format JPG, PNG, atau WEBP. Maksimal 2 MB.</span>
                @error('foto') <span class="text-danger">{{ $message }}</span> @enderror
                <img id="foto-preview" class="form-photo-preview" alt="Pratinjau foto" style="display:none;">
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Simpan</button>
                <a href="{{ route('admin.facilities.index') }}" class="btn btn-outline-secondary">Batal</a>
            </div>
        </form>
    </div>
@endsection

@section('scripts')
    @include('admin.facilities.partials.photo-preview-script')
@endsection