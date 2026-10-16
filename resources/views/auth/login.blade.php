@extends('layouts.app')

@section('title', 'Iniciar sesión· ' . $configuracionSitio->get('SITIO_NOMBRE'))

@section('content')
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-12 col-sm-10 col-md-7 col-lg-5 col-xl-4">
                <div class="card tarjeta-producto border-0 mb-0">
                    <div class="card-body p-4 p-md-5">
                        <div class="text-center mb-4">
                            <div class="display-4 text-brand">Bienvenidos</div>
                            <h1 class="h3 fw-bold mb-1">{{ $configuracionSitio->get('SITIO_NOMBRE') }}</h1>
                            <p class="text-muted mb-0">Sistema de gestión y administracion para BurguerWilli and Grill</p>
                        </div>

                        <form method="POST" id="login-form" novalidate>
                            @csrf

                            <div class="alert alert-danger d-none" data-alerta>
                                <span data-alerta-msg></span>
                            </div>

                            <div class="mb-3">
                                <label for="usuario" class="form-label">Usuario o correo</label>
                                <input type="text" class="form-control" id="usuario" name="usuario"
                                    placeholder="usuario@correo.com" autofocus required>
                                <div class="invalid-feedback"></div>
                            </div>

                            <div class="mb-4">
                                <label for="password" class="form-label">Contraseña</label>
                                <input type="password" class="form-control" id="password" name="password"
                                    placeholder="**************" required>
                                <div class="invalid-feedback"></div>
                            </div>

                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="remember" name="remember">
                                <label class="form-check-label small" for="remember">Mantenerme conectado</label>
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-brand btn-lg" id="loginBtn">Ingresar</button>
                            </div>

                            <div class="text-center mt-3">
                                <a href="{{ route('password.request') }}" class="small text-decoration-none">¿Olvidaste tu
                                    contraseña?</a>
                            </div>

                            <p class="text-center text-muted small mt-3 mb-0">
                                ¿No tienes cuenta? Contacta con tu administrador.
                            </p>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script type="module">
        $(document).ready(function() {
            $('#login-form').on('submit', function(event) {
                event.preventDefault();

                if (typeof window.UtilidadesFormulario === 'undefined') {
                    console.error('UtilidadesFormulario no está cargado. Ejecuta: npm run build');
                    return;
                }

                const usuario = $('#usuario').val().trim();
                const password = $('#password').val().trim();

                window.UtilidadesFormulario.limpiarFormulario('#login-form');

                // Validar usuario
                if (usuario === '') {
                    window.UtilidadesFormulario.mostrarError('#usuario',
                        'Debe ingresar su usuario o correo.');
                    $('#usuario').focus();
                    return;
                }

                // Validar contraseña
                if (password === '') {
                    window.UtilidadesFormulario.mostrarError('#password',
                        'Debe ingresar su contraseña.');
                    $('#password').focus();
                    return;
                }

                const url = "{{ route('login.store') }}";
                const formData = {
                    usuario: usuario,
                    password: password,
                    remember: $('#remember').is(':checked') ? 1 : 0
                };

                const $boton = $('#loginBtn');
                $boton.prop('disabled', true).text('Ingresando...');

                $.ajax({
                        url: url,
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        data: formData,
                        dataType: 'json'
                    })
                    .done(function(respuesta) {
                        if (respuesta.success) {
                            window.location.href = respuesta.redirect;
                            return;
                        }

                        $boton.prop('disabled', false).text('Ingresar');

                        if (respuesta.errors && typeof respuesta.errors === 'object') {
                            let mostrados = 0;

                            Object.keys(respuesta.errors).forEach(function(campo) {
                                const mensajes = respuesta.errors[campo];
                                const texto = Array.isArray(mensajes) ? mensajes[0] : mensajes;

                                if ($('#' + campo).length) {
                                    window.UtilidadesFormulario.mostrarError('#' + campo, texto);
                                    mostrados++;
                                } else {
                                    window.UtilidadesFormulario.mostrarAlerta(texto);
                                }
                            });

                            if (mostrados > 0) {
                                return;
                            }
                        }

                        window.UtilidadesFormulario.mostrarAlerta(
                            respuesta.message ||
                            'No se pudo iniciar sesión. Inténtalo nuevamente.'
                        );
                    })
                    .fail(function(xhr) {
                        $boton.prop('disabled', false).text('Ingresar');

                        const respuesta = xhr.responseJSON || {};
                        let mensaje = respuesta.message;

                        if (xhr.status === 419) {
                            mensaje =
                                'Tu sesión ha expirado. Recarga la página e inténtalo nuevamente.';
                        } else if (xhr.status === 0) {
                            mensaje =
                                'No se pudo conectar con el servidor. Verifica tu conexión.';
                        }

                        window.UtilidadesFormulario.mostrarAlerta(
                            mensaje ||
                            'Ocurrió un error al iniciar sesión. Inténtalo nuevamente.'
                        );
                    });
            });
        });
    </script>
@endpush
