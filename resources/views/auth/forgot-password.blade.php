@extends('layouts.main')

@section('title', 'Lupa Kata Sandi - DIPSpace')

@section('content')
    <div class="auth-wrap">
        <div class="auth-card">
            <h1>Lupa Kata Sandi</h1>
            <p class="auth-desc">Masukkan email akun Anda. Kami akan mengirimkan tautan untuk mengatur ulang kata sandi.</p>

            @if (session('status'))
                <div class="alert alert-success">{{ session('status') }}</div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger">
                    @foreach ($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('password.email') }}">
                @csrf

                <div class="form-group">
                    <label class="form-label" for="email">Email</label>
                    <input type="email" class="form-control" id="email" name="email" value="{{ old('email') }}" required autofocus placeholder="nama@email.com">
                </div>

                <button type="submit" class="btn btn-primary btn-block">Kirim Tautan Reset</button>
            </form>

            <p class="auth-footer"><a href="{{ route('login') }}">&larr; Kembali ke halaman masuk</a></p>
        </div>
    </div>
@endsection
