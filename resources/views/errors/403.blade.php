@extends('layouts.app')

@section('title', 'Acceso restringido · '.$configuracionSitio->get('SITIO_NOMBRE'))

@section('content')
    <div class="d-flex align-items-center justify-content-center p-4" style="min-height: 70vh;">
        <div class="text-center">
            <div class="display-1 mb-3">🔒</div>
            <h1 class="display-4 fw-bold mb-2">403</h1>
            <h2 class="h4 fw-semibold mb-3">Acceso restringido</h2>
            <p class="lead text-muted mx-auto" style="max-width: 32rem;">
                {{ $exception?->getMessage() ?: 'No tienes permiso para acceder a esta sección.' }}
            </p>
            <p class="small text-muted mb-4">Esta sección está reservada para el administrador del sistema.</p>
            <div class="d-flex flex-wrap justify-content-center gap-2">
                <a href="{{ route('inicio') }}" class="btn btn-brand px-4">
                    🏠 Ir al inicio
                </a>
                <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('inicio') }}"
                   class="btn btn-outline-secondary">
                    ← Volver atrás
                </a>
            </div>
        </div>
    </div>
@endsection