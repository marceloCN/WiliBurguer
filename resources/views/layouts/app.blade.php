<!DOCTYPE html>
<html lang="es" class="h-100">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', $configuracionSitio->get('SITIO_NOMBRE'))</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @fonts

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        :root {
            --brand: {{ $configuracionSitio->get('TEMA_COLOR_PRIMARIO') }};
            --brand-dark: {{ $configuracionSitio->get('TEMA_COLOR_PRIMARIO_OSCURO') }};
            --brand-bg: {{ $configuracionSitio->get('TEMA_COLOR_FONDO') }};
            --brand-ink: {{ $configuracionSitio->get('TEMA_COLOR_TEXTO') }};
            --brand-gold: {{ $configuracionSitio->get('TEMA_COLOR_DETALLE') }};
        }
    </style>

    @stack('styles')
</head>

<body class="d-flex flex-column h-100 overflow-hidden">
    <nav class="navbar navbar-expand-lg navbar-dark bg-brand shadow-sm flex-shrink-0">
        <div class="container">
            <a class="navbar-brand" href="{{ route('inicio') }}">
                {{ $configuracionSitio->get('SITIO_NOMBRE') }}
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain"
                aria-controls="navbarMain" aria-expanded="false" aria-label="Abrir menú">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarMain">
                <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-lg-center">
                    @auth
                        @if (auth()->user()->empleado?->rol)
                            <li class="nav-item"><a class="nav-link" href="{{ route('dashboard') }}">Panel</a></li>
                        @endif
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle d-flex align-items-center gap-2" href="#"
                                role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <span class="d-none d-lg-inline">👤</span>
                                {{ auth()->user()->empleado?->nombre ?? auth()->user()->usuario }}
                                <span
                                    class="badge text-bg-light ms-1">{{ auth()->user()->empleado?->rol?->claverol }}</span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="{{ route('dashboard.perfil.index') }}">👤 Mi perfil</a>
                                </li>
                                @if (auth()->user()->empleado?->rol?->claverol === 'ADMINISTRADOR')
                                    <li><a class="dropdown-item" href="{{ route('dashboard.configuracion.index') }}">⚙️
                                            configuración</a></li>
                                    <li><a class="dropdown-item"
                                            href="{{ route('dashboard.bitacora-accesos.index') }}">📜 Bitácora de accesos</a>
                                    </li>
                                    <li><a class="dropdown-item"
                                            href="{{ route('dashboard.bitacora-acciones.index') }}">📋 Bitácora de acciones</a>
                                    </li>
                                    <li>
                                        <hr class="dropdown-divider">
                                    </li>
                                    <li><a class="dropdown-item" href="{{ route('dashboard.permisos.index') }}">🔐 Gestión de permisos</a></li>
                                @endif
                                @if (auth()->user()->empleado?->rol?->claverol === 'GERENTE' || auth()->user()->empleado?->rol?->claverol === 'CAJERO')
                                    <li><a class="dropdown-item" href="{{ route('dashboard', ['panel' => 1]) }}">🗂️ Panel de
                                            administración</a></li>
                                @endif
                                <li>
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <button type="submit" class="dropdown-item text-danger">Cerrar sesión</button>
                                    </form>
                                </li>
                            </ul>
                        </li>
                    @else
                        <li class="nav-item"><a class="nav-link" href="{{ route('inicio') }}#menu">Menú</a></li>
                        <li class="nav-item"><a class="nav-link" href="{{ route('inicio') }}#promociones">Promociones</a>
                        </li>
                        <li class="nav-item"><a class="nav-link" href="{{ route('inicio') }}#sucursales">Sucursales y
                                horarios</a></li>
                        <li class="nav-item ms-lg-3">
                            <a class="btn btn-outline-light btn-sm" href="{{ route('login') }}">Iniciar sesión</a>
                        </li>
                    @endauth
                </ul>
            </div>
        </div>
    </nav>

    <main class="flex-fill overflow-auto" style="min-height: 0;">
        @yield('content')
    </main>

    <footer class="bg-dark text-white-50 py-3 flex-shrink-0">
        <div class="container text-center small">
            © {{ date('Y') }} {{ $configuracionSitio->get('SITIO_NOMBRE') }}
        </div>
    </footer>

    @stack('scripts')
</body>

</html>
