@extends('layouts.main')

@section('title', 'Detail Fasilitas - ' . $facility->nama)

@section('content')
    @php
        $isActive = $facility->status === \App\Models\Facility::STATUS_AKTIF;
        $statusLabel = $isActive ? 'Tersedia' : 'Dalam Perbaikan';
    @endphp

    <a href="{{ route('home') }}" class="detail-breadcrumb">
        @include('facilities.partials.icon', ['name' => 'arrow-left', 'size' => 20])
        Kembali ke daftar fasilitas
    </a>

    <div class="detail-heading">
        <h1 class="detail-title">Detail Fasilitas</h1>
        <p class="detail-subtitle">Kenali fasilitas dan panduan penggunaannya sebelum mengajukan reservasi.</p>
    </div>

    {{-- Kartu identitas fasilitas --}}
    <div class="detail-card">
        <div class="detail-head">
            <h2 class="detail-name">{{ $facility->nama }}</h2>
            <span class="detail-status-pill {{ $facility->status }}">
                <span class="detail-status-dot"></span>{{ $statusLabel }}
            </span>
        </div>

        <hr class="detail-divider">

        <div class="detail-meta">
            <div class="detail-meta-item">
                <span class="detail-meta-icon">
                    @include('facilities.partials.icon', ['name' => 'location'])
                </span>
                <span class="detail-meta-text">
                    <span class="detail-meta-label">Lokasi</span>
                    <span class="detail-meta-value">{{ $facility->lokasi }}</span>
                </span>
            </div>
            <div class="detail-meta-item">
                <span class="detail-meta-icon">
                    @include('facilities.partials.icon', ['name' => 'type'])
                </span>
                <span class="detail-meta-text">
                    <span class="detail-meta-label">Tipe fasilitas</span>
                    <span class="detail-meta-value">{{ $facility->tipe }}</span>
                </span>
            </div>
            <div class="detail-meta-item">
                <span class="detail-meta-icon">
                    @include('facilities.partials.icon', ['name' => 'capacity'])
                </span>
                <span class="detail-meta-text">
                    <span class="detail-meta-label">Kapasitas</span>
                    <span class="detail-meta-value">{{ $facility->kapasitas }} orang</span>
                </span>
            </div>
        </div>
    </div>

    <div class="detail-columns">
        {{-- Kolom utama --}}
        <div class="detail-col-main detail-card">
            <h3 class="detail-section-title">Tentang fasilitas</h3>
            <p class="detail-text">{{ $profile['deskripsi'] }}</p>

            <div class="detail-illustration">
                @include('facilities.partials.icon', ['name' => 'type', 'size' => 54])
                <span class="detail-illustration-label">{{ $facility->tipe }}</span>
            </div>
            <p class="detail-caption">Ilustrasi fasilitas</p>

            <hr class="detail-divider">

            <h3 class="detail-section-title">Sarana pendukung</h3>
            <div class="detail-sarana">
                @foreach ($profile['sarana'] as $sarana)
                    <div class="detail-sarana-item">
                        <span class="detail-sarana-icon">
                            @include('facilities.partials.icon', ['name' => $sarana['icon'], 'size' => 20])
                        </span>
                        <span class="detail-sarana-text">
                            <span class="detail-sarana-name">{{ $sarana['nama'] }}</span>
                            <span class="detail-sarana-desc">{{ $sarana['keterangan'] }}</span>
                        </span>
                    </div>
                @endforeach
            </div>

            <p class="detail-note">
                Deskripsi dan sarana di atas merupakan gambaran umum fasilitas.
                Konfirmasi rincian fasilitas kepada pengelola sebelum digunakan.
            </p>
        </div>

        {{-- Kolom ketersediaan --}}
        <div class="detail-col-side detail-card">
            <h3 class="detail-side-title">Ketersediaan &amp; reservasi</h3>

            <div class="detail-avail">
                <span class="detail-avail-icon {{ $isActive ? 'is-active' : 'is-repair' }}">
                    @include('facilities.partials.icon', ['name' => $isActive ? 'check' : 'alert'])
                </span>
                <span class="detail-avail-text">
                    @if ($isActive)
                        <span class="detail-avail-title">Fasilitas tersedia untuk reservasi</span>
                        <span class="detail-avail-desc">
                            {{ $facility->nama }} dapat diajukan untuk reservasi pada jam operasional
                            07:00&ndash;20:00 sesuai ketersediaan jadwal.
                        </span>
                    @else
                        <span class="detail-avail-title">Fasilitas sedang dalam perbaikan</span>
                        <span class="detail-avail-desc">
                            {{ $facility->nama }} tidak tersedia untuk reservasi sementara.
                            Jadwal akan dibuka kembali setelah perbaikan selesai.
                        </span>
                    @endif
                </span>
            </div>

            <div class="detail-steps">
                @if ($isActive)
                    <div class="detail-step">
                        <span class="detail-step-num">1</span>
                        <span>Periksa jadwal agar tidak bertabrakan dengan reservasi lain.</span>
                    </div>
                    <div class="detail-step">
                        <span class="detail-step-num">2</span>
                        <span>Ajukan reservasi dengan tanggal, waktu, dan tujuan penggunaan.</span>
                    </div>
                    <div class="detail-step">
                        <span class="detail-step-num">3</span>
                        <span>Tunggu persetujuan petugas sebelum fasilitas digunakan.</span>
                    </div>
                @else
                    <div class="detail-step">
                        <span class="detail-step-num">1</span>
                        <span>Periksa status fasilitas sebelum mengajukan reservasi.</span>
                    </div>
                    <div class="detail-step">
                        <span class="detail-step-num">2</span>
                        <span>Tunggu pengumuman ketersediaan fasilitas.</span>
                    </div>
                    <div class="detail-step">
                        <span class="detail-step-num">3</span>
                        <span>Ajukan reservasi setelah fasilitas kembali aktif.</span>
                    </div>
                @endif
            </div>

            @unless ($isActive)
                <div class="detail-warning">
                    @include('facilities.partials.icon', ['name' => 'alert', 'size' => 18])
                    <span>Reservasi tidak tersedia sementara karena fasilitas sedang dalam perbaikan.</span>
                </div>
            @endunless

            <div class="detail-actions">
                @if ($isActive)
                    <a href="{{ route('reservations.index', ['facility' => $facility->id]) }}" class="detail-btn-primary">
                        @include('facilities.partials.icon', ['name' => 'calendar', 'size' => 18])
                        Ajukan Reservasi
                    </a>
                @else
                    <span class="detail-btn-disabled">Reservasi Tidak Tersedia</span>
                @endif

                <a href="{{ route('reports.index', ['facility' => $facility->id]) }}" class="detail-btn-outline">
                    @include('facilities.partials.icon', ['name' => 'tools', 'size' => 18])
                    Laporkan Kerusakan
                </a>
            </div>
        </div>
    </div>
@endsection
