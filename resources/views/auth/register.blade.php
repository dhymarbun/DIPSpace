@extends('layouts.main')

@section('title', 'Daftar Akun - DIPSpace')

@section('content')
    <div class="auth-wrap">
        <div class="auth-card">
            <h1>Daftar Akun</h1>
            <p class="auth-desc">Buat akun untuk mengajukan reservasi fasilitas.</p>

            <div class="alert alert-info">
                Akun baru harus <strong>diverifikasi oleh admin</strong> terlebih dahulu sebelum dapat melakukan reservasi.
            </div>

            @if ($errors->any())
                <div class="alert alert-danger">
                    @foreach ($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('register') }}">
                @csrf

                <div class="form-group">
                    <label class="form-label" for="name">Nama</label>
                    <input type="text" class="form-control" id="name" name="name" value="{{ old('name') }}" required autofocus autocomplete="name" placeholder="Nama lengkap">
                </div>

                <div class="form-group">
                    <label class="form-label" for="email">Email</label>
                    <input type="email" class="form-control" id="email" name="email" value="{{ old('email') }}" required autocomplete="username" placeholder="nama@email.com">
                </div>

                <div class="form-group">
                    <label class="form-label" for="password">Kata Sandi</label>
                    <input type="password" class="form-control" id="password" name="password" required autocomplete="new-password" placeholder="Minimal 8 karakter">
                </div>

                <div class="form-group">
                    <label class="form-label" for="password_confirmation">Konfirmasi Kata Sandi</label>
                    <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" required autocomplete="new-password" placeholder="Ulangi kata sandi">
                </div>

                <button type="submit" class="btn btn-primary btn-block">Daftar</button>
            </form>

            <p class="auth-footer">Sudah punya akun? <a href="{{ route('login') }}">Masuk</a></p>
        </div>
    </div>
@endsection
