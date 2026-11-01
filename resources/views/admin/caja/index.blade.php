@extends('layouts.app')

@section('title', 'Caja · '.$configuracionSitio->get('SITIO_NOMBRE'))

@section('content')
    <div class="container py-4">
        @include('admin.caja._header')
        @include('partials.mensajes')

        <div class="row g-4">
            <div class="col-12 col-xl-8">
                @if ($miTurno)
                    @include('admin.caja._turno')

                    @if ($puedeMovimiento || $puedeVerMovimientos)
                        @include('admin.caja._movimientos')
                    @endif
                @else
                    @if ($puedeAbrir && $cajasLibres->isNotEmpty())
                        @include('admin.caja._abrir')
                    @else
                        <div class="alert alert-light border d-flex align-items-start gap-2 mb-4" role="alert">
                            <span class="fs-5">💰</span>
                            <div>
                                <strong>No tienes una jornada de caja abierta.</strong>
                                @if ($puedeAbrir)
                                    @if ($cajas->isEmpty())
                                        Esta sucursal no tiene cajas registradas. Pide al administrador que registre una caja.
                                    @else
                                        Todas las cajas de esta sucursal están ocupadas; espera a que se liberen.
                                    @endif
                                @else
                                    Tu rol no tiene permiso para abrir una jornada de caja (permiso 410).
                                @endif
                            </div>
                        </div>
                    @endif
                @endif

                @if ($puedeHistorial)
                    @include('admin.caja._historial')
                @endif
            </div>

            <div class="col-12 col-xl-4">
                @include('admin.caja._estado')

                @if ($miTurno && $puedeAbrir && $cajasLibres->isNotEmpty())
                    @include('admin.caja._abrir')
                @endif
            </div>
        </div>
    </div>

    @include('admin.caja._modal_cerrar')

    @push('scripts')
        <script type="module">
            // Un solo modal de cierre para todas las jornadas: el botón que lo
            // dispara aporta la ruta, la caja, quién la abrió y el efectivo esperado.
            $('#cerrarCajaModal').on('show.bs.modal', function (event) {
                const $boton = $(event.relatedTarget);
                if (!$boton.length) return;

                const accion = $boton.data('accion');
                if (accion) {
                    $('#form-cerrar-jornada').attr('action', accion);
                }

                $('#cerrar-jornada-caja').text($boton.data('caja') || '—');
                $('#cerrar-jornada-empleado').text($boton.data('empleado') || '—');
                $('#cerrar-jornada-esperado').text('Bs. ' + ($boton.attr('data-esperado') || '0.00'));
            });
        </script>
    @endpush
@endsection
