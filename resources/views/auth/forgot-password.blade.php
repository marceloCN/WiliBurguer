@extends('layouts.app')

@section('title', 'Recuperar contraseña · '.$configuracionSitio->get('SITIO_NOMBRE'))

@section('content')
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-12 col-sm-10 col-md-7 col-lg-5 col-xl-4">
                <div class="card tarjeta-producto border-0 mb-0">
                    <div class="card-body p-4 p-md-5">
                        <div class="text-center mb-4">
                            <div class="display-4 text-brand">🔑</div>
                            <h1 class="h3 fw-bold mb-1">Recuperar contraseña</h1>
                            <p class="text-muted mb-0">Te enviaremos un enlace a tu correo para cambiarla</p>
                        </div>

                        @if (session('status'))
                            <div class="alert alert-success">{{ session('status') }}</div>
                        @endif

                        <form method="POST" action="{{ route('password.email') }}">
                            @csrf

                            <div class="mb-4">
                                <label for="correo" class="form-label">Correo</label>
                                <input type="email" class="form-control @error('correo') is-invalid @enderror"
                                       id="correo" name="correo" value="{{ old('correo') }}"
                                       placeholder="tu@correo.com" autofocus required>
                                @error('correo')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-brand btn-lg">Enviar enlace</button>
                            </div>

                            <p class="text-center text-muted small mt-3 mb-2">
                                <a href="{{ route('login') }}" class="text-decoration-none">← Volver a iniciar sesión</a>
                            </p>
                            <p class="text-center text-muted small mt-2 mb-0">
                                El enlace llegará desde <strong>ventas@burguerwilly.com</strong>. Al abrir el mensaje,
                                te redirigirá al sistema para cambiar y confirmar tu contraseña.
                            </p>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection