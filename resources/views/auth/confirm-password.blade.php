@extends('layouts.main')

@section('title', 'Konfirmasi Kata Sandi - DIPSpace')

@section('content')
    <div class="auth-wrap">
        <div class="auth-card">
            <h1>Konfirmasi Kata Sandi</h1>
            <p class="auth-desc">Ini adalah area aman. Masukkan kata sandi Anda untuk melanjutkan.</p>

            @if ($errors->any())
                <div class="alert alert-danger">
                    @foreach ($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('password.confirm') }}">
                @csrf

                <div class="form-group">
                    <label class="form-label" for="password">Kata Sandi</label>
                    <input type="password" class="form-control" id="password" name="password" required autofocus autocomplete="current-password">
                </div>

                <button type="submit" class="btn btn-primary btn-block">Konfirmasi</button>
            </form>

            <p class="auth-footer"><a href="{{ route('home') }}">&larr; Kembali ke beranda</a></p>
        </div>
    </div>
@endsection
