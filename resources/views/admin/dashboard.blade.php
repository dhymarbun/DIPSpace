@extends('layouts.main')

@section('title', 'Admin Dashboard - DIPSpace')

@section('content')
    <h1 class="page-title">Admin Dashboard</h1>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

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
