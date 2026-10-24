<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('titulo', 'Dashboard') · LinuxCore</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="{{ asset('css/linuxcore.css') }}" rel="stylesheet">
</head>
<body class="menu-abierto">
@php
    $menu = [
        ['dashboard', 'bi-house-door-fill', 'Dashboard', 'dashboard'],
        ['procesos', 'bi-file-earmark-text-fill', 'Procesos', 'procesos*'],
        ['cpu', 'bi-cpu', 'CPU y Memoria', 'cpu*'],
        ['planificacion', 'bi-calendar3', 'Planificación', 'planificacion*'],
        ['interbloqueos', 'bi-diagram-3-fill', 'Interbloqueos', 'interbloqueos*'],
        ['almacenamiento', 'bi-database-fill', 'Almacenamiento', 'almacenamiento,explorador'],
        ['usuarios', 'bi-people-fill', 'Usuarios y Grupos', 'usuarios'],
        ['bitacora', 'bi-journal-text', 'Bitácora', 'bitacora'],
    ];
@endphp
<aside class="lc-sidebar">
    <div class="lc-logo">
        <div class="lc-ln">LN</div>
        <div class="lc-marca">Linux<span>Core</span></div>
    </div>
    <ul class="lc-menu">
        @foreach ($menu as [$ruta, $icono, $texto, $patron])
            <li><a href="{{ route($ruta) }}" class="{{ request()->routeIs(...explode(',', $patron)) ? 'activo' : '' }}"><i class="bi {{ $icono }}"></i>{{ $texto }}</a></li>
        @endforeach
    </ul>
</aside>

<div class="lc-main">
    <header class="lc-top">
        <button class="btn-icon" id="btn-menu" aria-label="Menú"><i class="bi bi-list"></i></button>
        <div class="lc-user">
            <div class="dropdown">
                <a class="dropdown-toggle" href="#" data-bs-toggle="dropdown"><i class="bi bi-person-fill me-1"></i> {{ auth()->user()->name }}</a>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><span class="dropdown-item-text">Rol: <strong>{{ auth()->user()->esAdmin() ? 'Administrador' : 'Observador' }}</strong></span></li>
                </ul>
            </div>
            <form method="POST" action="{{ route('logout') }}">@csrf
                <button class="lc-salir"><i class="bi bi-box-arrow-right fs-5"></i> Cerrar sesión</button>
            </form>
        </div>
    </header>
    <main class="lc-contenido">
        <h1 class="lc-titulo">@yield('titulo')</h1>
        <div class="lc-sub">@yield('subtitulo')</div>
        @yield('contenido')
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script src="{{ asset('js/linuxcore.js') }}"></script>
@stack('scripts')
</body>
</html>
