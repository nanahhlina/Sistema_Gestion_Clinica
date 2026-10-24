<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#427cbe">
    <title>@yield('title', 'Inicio') · Clínica Vida Salud</title>
    <link rel="icon" href="{{ asset('assets/favicon.svg') }}" type="image/svg+xml">
    <link rel="stylesheet" href="{{ asset('assets/app.css') }}">
    <script src="{{ asset('assets/app.js') }}" type="module"></script>
    @stack('head')
</head>
<body class="@yield('body-class', 'app-page')">
    <a class="skip-link" href="#contenido">Saltar al contenido</a>
    @unless (View::hasSection('hide-header'))
        <header class="site-header">
            <div class="header-inner">
                <a class="brand" href="{{ route('home') }}" aria-label="Clínica Vida Salud · Inicio"><x-icon name="cross" /><span>Clínica Vida Salud</span></a>
                @auth
                    <div class="header-actions">
                        <span class="user-name"><x-icon name="user" /> {{ auth()->user()->display_name }}</span>
                        <form method="POST" action="{{ route('logout') }}">@csrf<button class="logout-button" type="submit"><x-icon name="logout" /><span>Cerrar sesión</span></button></form>
                    </div>
                @endauth
            </div>
        </header>
    @endunless
    <main id="contenido" class="@yield('main-class', 'container main-content')" tabindex="-1">
        @include('partials.flash')
        @yield('content')
    </main>
    @unless (View::hasSection('hide-footer'))<footer class="site-footer">Clínica Vida Salud <span aria-hidden="true">·</span> Atención y bienestar en cada paso</footer>@endunless
    @stack('scripts')
</body>
</html>
