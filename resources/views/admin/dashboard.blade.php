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

    <div class="card">
        <p class="card-title">Manajemen Akun</p>
        <div style="display:flex; gap:10px; flex-wrap:wrap;">
            <a href="{{ route('admin.users.createPetugas') }}" class="btn btn-primary">+ Daftar Petugas</a>
            <a href="{{ route('admin.users.createPengguna') }}" class="btn btn-primary">+ Daftar Pengguna</a>
            <a href="{{ route('admin.users.pending') }}" class="btn btn-outline-secondary">Verifikasi Akun</a>
        </div>
    </div>

    <div class="card">
        <p class="card-title">Manajemen Fasilitas</p>
        <div style="display:flex; gap:10px; flex-wrap:wrap;">
            <a href="{{ route('admin.facilities.index') }}" class="btn btn-primary">Kelola Fasilitas</a>
            <a href="{{ route('admin.facilities.create') }}" class="btn btn-outline-secondary">+ Tambah Fasilitas</a>
        </div>
    </div>

    <div class="card">
        <p class="card-title">Laporan & Rekap</p>
        <div style="display:flex; gap:10px; flex-wrap:wrap;">
            <a href="{{ route('admin.reports.index') }}" class="btn btn-primary">Lihat Rekap</a>
        </div>
    </div>
@endsection
