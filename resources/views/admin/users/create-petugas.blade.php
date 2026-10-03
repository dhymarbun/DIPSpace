@extends('layouts.main')

@section('title', 'Daftar Petugas - DIPSpace')

@section('content')
    <h1 class="page-title">Daftar Akun Petugas</h1>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card">
        <p class="card-title">Form Pendaftaran Petugas</p>

        <form method="post" action="{{ route('admin.users.storePetugas') }}">
            @csrf

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="name">Nama Lengkap</label>
                    <input type="text" id="name" name="name" class="form-control {{ $errors->has('name') ? 'is-invalid' : '' }}" value="{{ old('name') }}" required>
                    @error('name') <span class="text-danger">{{ $message }}</span> @enderror
                </div>

                <div class="form-group">
                    <label class="form-label" for="email">Email</label>
                    <input type="email" id="email" name="email" class="form-control {{ $errors->has('email') ? 'is-invalid' : '' }}" value="{{ old('email') }}" required>
                    @error('email') <span class="text-danger">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="password">Password</label>
                    <input type="password" id="password" name="password" class="form-control {{ $errors->has('password') ? 'is-invalid' : '' }}" required>
                    <span class="form-hint">Minimal 8 karakter.</span>
                    @error('password') <span class="text-danger">{{ $message }}</span> @enderror
                </div>

                <div class="form-group">
                    <label class="form-label" for="password_confirmation">Konfirmasi Password</label>
                    <input type="password" id="password_confirmation" name="password_confirmation" class="form-control" required>
                    <span class="form-hint">Ulangi password yang sama.</span>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Daftarkan Petugas</button>
                <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-secondary">Batal</a>
            </div>
        </form>
    </div>
@endsection