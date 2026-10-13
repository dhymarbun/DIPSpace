@extends('layouts.main')

@section('title', 'DIPSpace - Beranda')
@section('body-class', 'page-landing')
@section('backdrop-class', 'backdrop--plain')

@section('content')
    <section class="hero">
        <div class="hero-overlay hero-overlay--batik" aria-hidden="true"></div>
        <div class="hero-inner">
            <div class="hero-copy">
                <h1 class="hero-title">Ruang untuk<br>setiap kegiatanmu.</h1>
                <p class="hero-lead">Temukan fasilitas Universitas Diponegoro, ajukan reservasi untuk kegiatanmu, dan laporkan kerusakan. Semua dalam satu ruang yang mudah diakses.</p>
                <div class="hero-actions">
                    <a href="{{ route('home') }}" class="hero-btn hero-btn-primary">
                        Jelajahi Fasilitas
                        @include('partials.icon', ['name' => 'arrow-right', 'size' => 20])
                    </a>
                    <a href="{{ route('login') }}" class="hero-btn hero-btn-outline">Masuk Akun</a>
                </div>
                <p class="hero-note">Lihat fasilitas tanpa login.<br>Pengajuan reservasi memerlukan akun.</p>
            </div>

            <div class="hero-media">
                <img src="{{ asset('images/hero-landing.png') }}" alt="Kegiatan di lingkungan Universitas Diponegoro">
                <div class="hero-media-caption">
                    <span>Universitas Diponegoro</span>
                    <span>Widya Puraya</span>
                </div>
            </div>
        </div>
    </section>

    <section class="services" id="layanan">
        <p class="services-eyebrow">SATU AKSES, TIGA LAYANAN</p>
        <div class="services-grid">
            <div class="service-item">
                <span class="service-icon">@include('partials.icon', ['name' => 'search', 'size' => 24])</span>
                <div class="service-body">
                    <h3 class="service-title">Cari Fasilitas</h3>
                    <p class="service-desc">Temukan ruang sesuai lokasi, kapasitas, dan kebutuhan kegiatanmu.</p>
                </div>
            </div>

            <div class="services-divider" aria-hidden="true"></div>

            <div class="service-item">
                <span class="service-icon">@include('partials.icon', ['name' => 'calendar', 'size' => 24])</span>
                <div class="service-body">
                    <h3 class="service-title">Ajukan Reservasi</h3>
                    <p class="service-desc">Cek ketersediaan jadwal dan ajukan penggunaan fasilitas dengan akunmu.</p>
                </div>
            </div>

            <div class="services-divider" aria-hidden="true"></div>

            <div class="service-item">
                <span class="service-icon">@include('partials.icon', ['name' => 'tools', 'size' => 24])</span>
                <div class="service-body">
                    <h3 class="service-title">Laporkan Kerusakan</h3>
                    <p class="service-desc">Sampaikan kondisi fasilitas agar lingkungan kampus tetap nyaman.</p>
                </div>
            </div>
        </div>
    </section>

    <footer class="site-footer">
        <span>&copy; {{ date('Y') }} DIPSpace &middot; Universitas Diponegoro</span>
        <span>Layanan fasilitas kampus</span>
    </footer>
@endsection
