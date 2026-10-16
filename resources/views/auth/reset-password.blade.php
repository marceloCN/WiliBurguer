@extends('layouts.app')

@section('title', 'Cambiar contraseña · '.$configuracionSitio->get('SITIO_NOMBRE'))

@section('content')
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-12 col-sm-10 col-md-7 col-lg-5 col-xl-4">
                <div class="card tarjeta-producto border-0 mb-0">
                    <div class="card-body p-4 p-md-5">
                        <div class="text-center mb-4">
                            <div class="display-4 text-brand">🔒</div>
                            <h1 class="h3 fw-bold mb-1">Nueva contraseña</h1>
                            <p class="text-muted mb-0">Elige una contraseña segura (mín. 8 caracteres)</p>
                        </div>

                        <form method="POST" action="{{ route('password.store') }}">
                            @csrf
                            <input type="hidden" name="token" value="{{ $token }}">
                            <input type="hidden" name="correo" value="{{ $correo }}">

                            <div class="mb-3">
                                <label for="password" class="form-label">Nueva contraseña</label>
                                <input type="password" class="form-control @error('password') is-invalid @enderror"
                                       id="password" name="password" placeholder="••••••••" required>
                                @error('password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-4">
                                <label for="password_confirmation" class="form-label">Confirmar contraseña</label>
                                <input type="password" class="form-control"
                                       id="password_confirmation" name="password_confirmation"
                                       placeholder="••••••••" required>
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-brand btn-lg">Guardar contraseña</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection