<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? __('site.title') }}</title>
    <link rel="stylesheet" href="{{ asset('marketplace.css') }}">
</head>
<body class="locale-{{ app()->getLocale() }}">
    <div class="topbar">
        <div class="container nav">
            <a class="brand" href="{{ route('home') }}">
                <span class="brand-mark">GK</span>
                <span class="brand-text">
                    <strong>{{ __('site.brand') }}</strong>
                    <small>{{ __('site.country') }}</small>
                </span>
            </a>
            <div class="nav-links">
                <a href="{{ route('home') }}#services">{{ __('site.nav_services') }}</a>
            </div>
            <div class="nav-actions">
                @auth
                    <span class="badge nav-user-badge">{{ auth()->user()->name }} - {{ auth()->user()->role }}</span>
                    <a class="btn secondary nav-mini-btn" href="{{ route('dashboard') }}">Dashboard</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="btn secondary" type="submit">{{ __('site.logout') }}</button>
                    </form>
                @else
                    <a class="btn secondary" href="{{ route('login') }}">{{ __('site.login') }}</a>
                    <a class="btn brand" href="{{ route('register') }}">{{ __('site.create_account') }}</a>
                @endauth
            </div>
        </div>
    </div>
    <main id="top">
        <div class="container">
            @if(session('success'))
                <div class="flash success">{{ session('success') }}</div>
            @endif
            @if($errors->any())
                <div class="flash error">{{ $errors->first() }}</div>
            @endif
        </div>
        @yield('content')
    </main>
    
</body>
</html>

