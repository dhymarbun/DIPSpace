<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'DIPSpace')</title>
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    @yield('styles')
</head>
<body class="@yield('body-class')">
    <nav class="navbar">
        <a class="navbar-brand" href="{{ route('landing') }}">
            <img class="navbar-logo-img brand-logo" src="{{ asset('images/logo-dipspace.png') }}" alt="DIPSpace">
            <span class="navbar-title">DIPSpace</span>
            <img class="cat-peek" src="{{ asset('images/cat-peek.png') }}" alt="" aria-hidden="true">
        </a>
        <div class="navbar-links">
            <a class="nav-link {{ request()->routeIs('home') ? 'active' : '' }}" href="{{ route('home') }}">Fasilitas</a>

            @auth
                <span class="hello">Halo, {{ Auth::user()->name }}</span>
                @if (Auth::user()->role === 'admin')
                    <a class="nav-link {{ request()->routeIs('admin.*') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">Dashboard</a>
                @endif
                @if (Auth::user()->role === 'petugas')
                    <a class="nav-link {{ request()->routeIs('petugas.*') ? 'active' : '' }}" href="{{ route('petugas.dashboard') }}">Antrian</a>
                @endif
                @if (Auth::user()->role === 'pengguna')
                    @if (Auth::user()->isPendingVerification())
                        <a class="nav-badge" href="{{ route('verification.notice') }}" title="Akun Anda belum diverifikasi admin">Menunggu Verifikasi</a>
                    @endif
                    <a class="nav-link {{ request()->routeIs('reservations.*') ? 'active' : '' }}" href="{{ route('reservations.index') }}">Reservasi</a>
                    <a class="nav-link {{ request()->routeIs('reports.*') ? 'active' : '' }}" href="{{ route('reports.index') }}">Laporkan Kerusakan</a>
                @endif
                <a class="nav-link {{ request()->routeIs('profile.*') ? 'active' : '' }}" href="{{ route('profile.edit') }}">Profil</a>
                <form method="POST" action="{{ route('logout') }}" class="navbar-logout">
                    @csrf
                    <button type="submit" class="nav-link">Logout</button>
                </form>
            @else
                <a class="nav-link" href="{{ route('login') }}">Masuk</a>
                @if (Route::has('register'))
                    <a class="nav-link" href="{{ route('register') }}">Daftar</a>
                @endif
            @endauth

            <span class="navbar-divider" aria-hidden="true"></span>
            <div class="navbar-uni">
                <img src="{{ asset('images/logo-undip.png') }}" alt="Universitas Diponegoro">
                <span class="navbar-uni-text">Universitas<br><strong>Diponegoro</strong></span>
            </div>
        </div>
    </nav>

    <div class="backdrop @yield('backdrop-class')">
        @if (session('notice'))
            <div class="alert alert-info">{{ session('notice') }}</div>
        @endif
        @if (session('warning'))
            <div class="alert alert-warning">{{ session('warning') }}</div>
        @endif
        @yield('content')
    </div>

    @yield('scripts')
</body>
</html>
