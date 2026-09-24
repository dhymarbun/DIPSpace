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
                @if (in_array(Auth::user()->role, ['petugas', 'admin'], true))
                    <div class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                        <a href="{{ route('dashboard') }}">Antrian</a>
                        @if(request()->routeIs('dashboard')) <div class="indicator"></div> @endif
                    </div>
                @endif
                <div class="nav-item {{ request()->routeIs('reservations.*') ? 'active' : '' }}">
                    <a href="{{ route('reservations.index') }}">Reservasi</a>
                    @if(request()->routeIs('reservations.*')) <div class="indicator"></div> @endif
                </div>
                <div class="nav-item {{ request()->routeIs('reports.*') ? 'active' : '' }}">
                    <a href="{{ route('reports.index') }}">Laporkan Kerusakan</a>
                    @if(request()->routeIs('reports.*')) <div class="indicator"></div> @endif
                </div>
                <form method="POST" action="{{ route('logout') }}" style="display:inline;">
                    @csrf
                    <button type="submit" class="nav-item" style="background:none; border:none; cursor:pointer; font-family:inherit; font-size:inherit; font-weight:inherit; color:inherit; padding:0;">Logout</button>
                </form>
            @else
                <a class="nav-item" href="{{ route('login') }}">Login</a>
            @endauth
        </div>
    </nav>

    <div class="backdrop">
        @yield('content')
    </div>

    @yield('scripts')
</body>
</html>
