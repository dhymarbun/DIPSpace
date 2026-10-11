@extends('layouts.main')

@section('title', 'Status Akun - DIPSpace')

@section('content')
    @php $user = Auth::user(); @endphp
    <div class="auth-wrap">
        <div class="auth-card" style="text-align:center;">
            <h1>Status Akun</h1>

            @if ($user->role !== 'pengguna' || $user->is_verified)
                <div class="alert alert-success">
                    Akun Anda sudah terverifikasi. Anda dapat menggunakan semua fitur DIPSpace.
                </div>
            @else
                <div class="alert alert-warning">
                    <strong>Akun Anda belum diverifikasi oleh admin.</strong><br>
                    Anda belum dapat mengajukan reservasi atau laporan kerusakan. Silakan tunggu hingga admin memverifikasi akun Anda.
                </div>
            @endif

            <a href="{{ route('home') }}" class="btn btn-primary btn-block">Kembali ke Beranda</a>
        </div>
    </div>
@endsection
