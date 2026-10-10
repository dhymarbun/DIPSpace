@extends('layouts.main')

@section('title', 'Atur Ulang Kata Sandi - DIPSpace')

@section('content')
    <div class="auth-wrap">
        <div class="auth-card">
            <h1>Atur Ulang Kata Sandi</h1>
            <p class="auth-desc">Masukkan kata sandi baru untuk akun Anda.</p>

            @if ($errors->any())
                <div class="alert alert-danger">
                    @foreach ($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('password.store') }}">
                @csrf
                <input type="hidden" name="token" value="{{ $request->route('token') }}">

                <div class="form-group">
                    <label class="form-label" for="email">Email</label>
                    <input type="email" class="form-control" id="email" name="email" value="{{ old('email', $request->email) }}" required autofocus autocomplete="username">
                </div>

                <div class="form-group">
                    <label class="form-label" for="password">Kata Sandi Baru</label>
                    <input type="password" class="form-control" id="password" name="password" required autocomplete="new-password" placeholder="Minimal 8 karakter">
                </div>

                <div class="form-group">
                    <label class="form-label" for="password_confirmation">Konfirmasi Kata Sandi Baru</label>
                    <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" required autocomplete="new-password">
                </div>

                <button type="submit" class="btn btn-primary btn-block">Simpan Kata Sandi</button>
            </form>

            <p class="auth-footer"><a href="{{ route('login') }}">&larr; Kembali ke halaman masuk</a></p>
        </div>
    </div>
@endsection
