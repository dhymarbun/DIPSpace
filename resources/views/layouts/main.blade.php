<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'DIPSpace')</title>
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    @yield('styles')
</head>
<body>
    <nav class="navbar">
        <a class="navbar-brand" href="{{ url('/') }}">
            <img src="{{ asset('images/logo-undip.png') }}" alt="Undip">
            <span>DIPSpace</span>
        </a>
        <div class="navbar-links">
            @auth
                <span class="hello">Halo, {{ Auth::user()->name }}</span>
                @if (Auth::user()->role === 'admin')
                    <div class="nav-item {{ request()->routeIs('admin.*') ? 'active' : '' }}">
                        <a href="{{ route('admin.dashboard') }}">Dashboard</a>
                        @if(request()->routeIs('admin.*')) <div class="indicator"></div> @endif
                    </div>
                @endif
                @if (Auth::user()->role === 'petugas')
                    <div class="nav-item {{ request()->routeIs('petugas.*') ? 'active' : '' }}">
                        <a href="{{ route('petugas.dashboard') }}">Antrian</a>
                        @if(request()->routeIs('petugas.*')) <div class="indicator"></div> @endif
                    </div>
                @endif
                @if (Auth::user()->role === 'pengguna')
                    @if (Auth::user()->isPendingVerification())
                        <a class="nav-badge" href="{{ route('verification.notice') }}" title="Akun Anda belum diverifikasi admin">Menunggu Verifikasi</a>
                    @endif
                    <div class="nav-item {{ request()->routeIs('reservations.*') ? 'active' : '' }}">
                        <a href="{{ route('reservations.index') }}">Reservasi</a>
                        @if(request()->routeIs('reservations.*')) <div class="indicator"></div> @endif
                    </div>
                    <div class="nav-item {{ request()->routeIs('reports.*') ? 'active' : '' }}">
                        <a href="{{ route('reports.index') }}">Laporkan Kerusakan</a>
                        @if(request()->routeIs('reports.*')) <div class="indicator"></div> @endif
                    </div>
                @endif
                <div class="nav-item {{ request()->routeIs('profile.*') ? 'active' : '' }}">
                    <a href="{{ route('profile.edit') }}">Profil</a>
                    @if(request()->routeIs('profile.*')) <div class="indicator"></div> @endif
                </div>
                <form method="POST" action="{{ route('logout') }}" style="display:inline;">
                    @csrf
                    <button type="submit" class="nav-item" style="background:none; border:none; cursor:pointer; font-family:inherit; font-size:inherit; font-weight:inherit; color:inherit; padding:0;">Logout</button>
                </form>
            @else
                <a class="nav-item" href="{{ route('login') }}">Login</a>
                @if (Route::has('register'))
                    <a class="nav-item" href="{{ route('register') }}">Daftar</a>
                @endif
            @endauth
        </div>
    </nav>

    <div class="backdrop">
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
