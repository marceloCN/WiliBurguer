@php
$rutaActual = request()->route()->getName();
@endphp

<aside class="card tarjeta-producto mb-3 mb-lg-0">
    <div class="card-body p-2">
        <div class="list-group list-group-flush">
            <a href="{{ route('dashboard.usuarios.dashboard') }}"
               class="list-group-item list-group-item-action d-flex align-items-center gap-2 {{ $rutaActual === 'dashboard.usuarios.dashboard' ? 'active' : '' }}">
                <span>📊</span>
                <span>Dashboard</span>
            </a>
            <a href="{{ route('dashboard.usuarios.datos') }}"
               class="list-group-item list-group-item-action d-flex align-items-center gap-2 {{ $rutaActual === 'dashboard.usuarios.datos' ? 'active' : '' }}">
                <span>👤</span>
                <span>Datos de usuario</span>
            </a>
            <a href="{{ route('dashboard.usuarios.roles') }}"
               class="list-group-item list-group-item-action d-flex align-items-center gap-2 {{ $rutaActual === 'dashboard.usuarios.roles' ? 'active' : '' }}">
                <span>🛡️</span>
                <span>Roles</span>
            </a>
            <a href="{{ route('dashboard.usuarios.seguridad') }}"
               class="list-group-item list-group-item-action d-flex align-items-center gap-2 {{ $rutaActual === 'dashboard.usuarios.seguridad' ? 'active' : '' }}">
                <span>🔒</span>
                <span>Seguridad</span>
            </a>
            @if ($permisosUsuario->contains('103'))
                <a href="{{ route('dashboard.usuarios.pdf', request()->query()) }}"
                   class="list-group-item list-group-item-action d-flex align-items-center gap-2 {{ $rutaActual === 'dashboard.usuarios.pdf' ? 'active' : '' }}">
                    <span>📄</span>
                    <span>Informe PDF</span>
                </a>
            @else
                <button type="button" class="list-group-item list-group-item-action d-flex align-items-center gap-2 text-muted"
                    onclick="UtilidadesFormulario.mostrarModalPermisoDenegado('Crear PDF de informe de auditoría de usuarios', '103')">
                    <span>📄</span>
                    <span>Informe PDF</span>
                </button>
            @endif
        </div>
    </div>
</aside>
