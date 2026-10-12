@extends('layouts.main')

@section('title', 'Admin Dashboard - DIPSpace')

@section('content')
    <h1 class="page-title">Admin Dashboard</h1>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="stat-grid">
        <div class="stat-card">
            <span class="stat-label">Total Pengguna</span>
            <span class="stat-value">{{ $stats['users_total'] }}</span>
            <span class="stat-sub">{{ $stats['users_pengguna'] }} pengguna · {{ $stats['users_petugas'] }} petugas</span>
        </div>
        <a class="stat-card" href="{{ route('admin.users.pending') }}">
            <span class="stat-label">Menunggu Verifikasi</span>
            <span class="stat-value">{{ $stats['users_pending'] }}</span>
            <span class="stat-sub">akun pengguna baru</span>
        </a>
        <a class="stat-card" href="{{ route('admin.facilities.index') }}">
            <span class="stat-label">Fasilitas</span>
            <span class="stat-value">{{ $stats['facilities_total'] }}</span>
            <span class="stat-sub">{{ $stats['facilities_repair'] }} dalam perbaikan</span>
        </a>
        <div class="stat-card">
            <span class="stat-label">Reservasi</span>
            <span class="stat-value">{{ $stats['reservations_total'] }}</span>
            <span class="stat-sub">{{ $stats['reservations_pending'] }} menunggu persetujuan</span>
        </div>
        <a class="stat-card" href="{{ route('admin.reports.index') }}">
            <span class="stat-label">Laporan Kerusakan Aktif</span>
            <span class="stat-value">{{ $stats['reports_open'] }}</span>
            <span class="stat-sub">pending / diproses</span>
        </a>
    </div>

    <div class="card quick-access">
        <div class="quick-access-head">
            <p class="quick-access-title">Akses Cepat</p>
            <span class="quick-access-note">Kelola administrasi dari satu tempat</span>
        </div>
        <div class="quick-access-body">
            <div class="quick-access-col">
                <div class="quick-access-info">
                    <div class="quick-access-label">
                        @include('partials.icon', ['name' => 'users', 'size' => 20])
                        Manajemen Akun
                    </div>
                    <p class="quick-access-desc">Daftarkan pengguna dan petugas, atau tinjau akun yang masuk.</p>
                </div>
                <div class="quick-access-actions">
                    <div class="quick-access-row">
                        <a href="{{ route('admin.users.createPetugas') }}" class="btn btn-primary">+ Daftar Petugas</a>
                        <a href="{{ route('admin.users.createPengguna') }}" class="btn btn-primary">+ Daftar Pengguna</a>
                    </div>
                    <div class="quick-access-row">
                        <a href="{{ route('admin.users.pending') }}" class="btn btn-outline-secondary">Verifikasi Akun</a>
                        <a href="{{ route('admin.users.all') }}" class="btn btn-outline-secondary">Lihat Semua Akun</a>
                    </div>
                </div>
            </div>

            <div class="quick-access-divider"></div>

            <div class="quick-access-col">
                <div class="quick-access-info">
                    <div class="quick-access-label">
                        @include('partials.icon', ['name' => 'type', 'size' => 20])
                        Manajemen Fasilitas
                    </div>
                    <p class="quick-access-desc">Kelola informasi fasilitas kampus dan tambahkan fasilitas baru.</p>
                </div>
                <div class="quick-access-actions">
                    <a href="{{ route('admin.facilities.index') }}" class="btn btn-primary">Kelola Fasilitas</a>
                    <a href="{{ route('admin.facilities.create') }}" class="btn btn-outline-secondary">+ Tambah Fasilitas</a>
                </div>
            </div>

            <div class="quick-access-divider"></div>

            <div class="quick-access-col">
                <div class="quick-access-info">
                    <div class="quick-access-label">
                        @include('partials.icon', ['name' => 'file', 'size' => 20])
                        Laporan &amp; Rekap
                    </div>
                    <p class="quick-access-desc">Tinjau rekap administrasi fasilitas kampus.</p>
                </div>
                <div class="quick-access-actions">
                    <a href="{{ route('admin.reports.index') }}" class="btn btn-primary">Lihat Rekap</a>
                </div>
            </div>
        </div>
    </div>
@endsection
