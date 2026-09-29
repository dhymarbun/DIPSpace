@extends('layouts.main')

@section('title', 'Daftar Pengguna - DIPSpace')

@section('content')
    <h1 class="page-title">Daftar Akun Pengguna</h1>

    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card">
        <p class="card-title">Form Pendaftaran Pengguna</p>

        <form method="post" action="{{ route('admin.users.storePengguna') }}">
            @csrf

            <div class="form-group">
                <label for="name">Nama Lengkap</label>
                <input type="text" id="name" name="name" value="{{ old('name') }}" required>
                @error('name') <span class="text-danger">{{ $message }}</span> @enderror
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" required>
                @error('email') <span class="text-danger">{{ $message }}</span> @enderror
            </div>

            <div class="form-group">
                <label for="user_type">Tipe Pengguna</label>
                <select id="user_type" name="user_type" required>
                    <option value="">-- Pilih Tipe --</option>
                    <option value="mahasiswa" {{ old('user_type') == 'mahasiswa' ? 'selected' : '' }}>Mahasiswa</option>
                    <option value="dosen" {{ old('user_type') == 'dosen' ? 'selected' : '' }}>Dosen</option>
                    <option value="staf" {{ old('user_type') == 'staf' ? 'selected' : '' }}>Staf</option>
                </select>
                @error('user_type') <span class="text-danger">{{ $message }}</span> @enderror
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
                @error('password') <span class="text-danger">{{ $message }}</span> @enderror
            </div>

            <div class="form-group">
                <label for="password_confirmation">Konfirmasi Password</label>
                <input type="password" id="password_confirmation" name="password_confirmation" required>
            </div>

            <button type="submit" class="btn btn-primary">Daftarkan Pengguna</button>
        </form>
    </div>
