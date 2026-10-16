@extends('layouts.app')

@section('title', 'Mi perfil · '.$configuracionSitio->get('SITIO_NOMBRE'))

@section('content')
    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-12 col-md-10 col-lg-8 col-xl-6">
                <h1 class="h3 fw-bold mb-4">Mi perfil</h1>

                @if (session('exito'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('exito') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
                    </div>
                @endif

                @if ($errors->all())
                    <div class="alert alert-danger">
                        <strong>Revisa los datos:</strong>
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="card tarjeta-producto mb-4">
                    <div class="card-body p-4">
                        <h2 class="h5 fw-bold mb-3">Datos personales y de acceso</h2>
                        <form method="POST" action="{{ route('dashboard.perfil.update') }}">
                            @csrf

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Nombre *</label>
                                    <input type="text" name="nombre" class="form-control"
                                           value="{{ old('nombre', $empleado->nombre) }}" required maxlength="100">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Apellido *</label>
                                    <input type="text" name="apellido" class="form-control"
                                           value="{{ old('apellido', $empleado->apellido) }}" required maxlength="100">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Teléfono</label>
                                    <input type="text" name="telefono" class="form-control"
                                           value="{{ old('telefono', $empleado->telefono) }}" maxlength="15">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Rol</label>
                                    <input type="text" class="form-control"
                                           value="{{ $empleado->rol?->nombrerol }}" disabled>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Usuario *</label>
                                    <input type="text" name="usuario" class="form-control"
                                           value="{{ old('usuario', $cuenta->usuario) }}" required maxlength="255">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Correo *</label>
                                    <input type="email" name="correo" class="form-control"
                                           value="{{ old('correo', $cuenta->correo) }}" required maxlength="255">
                                </div>
                            </div>

                            <hr class="my-4">

                            <h3 class="h5 fw-bold mb-3">Cambiar contraseña</h3>
                            <p class="text-muted small mb-3">Opcional. Solo rellena estos campos si deseas cambiarla.</p>

                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label">Contraseña actual</label>
                                    <input type="password" name="password_actual" class="form-control" autocomplete="current-password">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Nueva contraseña</label>
                                    <input type="password" name="password" class="form-control" minlength="6" autocomplete="new-password">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Confirmar nueva contraseña</label>
                                    <input type="password" name="password_confirmation" class="form-control" autocomplete="new-password">
                                </div>
                            </div>

                            <div class="d-flex justify-content-end gap-2 mt-4">
                                <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary">Cancelar</a>
                                <button type="submit" class="btn btn-brand">Guardar cambios</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection