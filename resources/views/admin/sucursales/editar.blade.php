@extends('layouts.app')

@section('title', 'Editar sucursal · ' . $sucursal->nombre . ' · ' . $configuracionSitio->get('SITIO_NOMBRE'))

@section('content')
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
            <div>
                <h1 class="h3 fw-bold mb-0">Editar sucursal</h1>
                <p class="text-muted mb-0">
                    Estás editando: <strong>{{ $sucursal->nombre }}</strong>
                    <span class="badge {{ $sucursal->estado ? 'text-bg-success' : 'text-bg-secondary' }} ms-1">
                        {{ $sucursal->estado ? 'Activa' : 'Inactiva' }}
                    </span>
                </p>
            </div>
            <a href="{{ route('dashboard.sucursales.index') }}" class="btn btn-outline-secondary btn-sm">← Volver</a>
        </div>

        @include('partials.mensajes')

        @include('admin.sucursales._nav')

        <div class="tab-content" id="sucursalTabsContent">
            @include('admin.sucursales._datos')
            @include('admin.sucursales._horarios')
            @include('admin.sucursales._mesas')
            @include('admin.sucursales._cajas')
        </div>

        @include('admin.sucursales._modal_baja')
    </div>
@endsection

@push('styles')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <style>
        .mapa-editar {
            height: 340px;
            border-radius: 0.75rem;
            background: #eee;
            z-index: 0;
        }

        .seccion-tabs .nav-link {
            border-radius: 2rem;
            padding: .5rem 1.1rem;
            font-weight: 600;
        }

        .seccion-tabs .nav-link.active {
            background-color: var(--brand);
            color: #fff;
        }

        @media (max-width: 575.98px) {
            .mapa-editar {
                height: 280px;
            }
        }
    </style>
@endpush

@push('scripts')
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script type="module">
        $(document).ready(function () {
            const latInicial = {{ (float) $sucursal->latitud }};
            const lngInicial = {{ (float) $sucursal->longitud }};

            const $campoLat = $('#latitud');
            const $campoLng = $('#longitud');
            const $latitudEnvio = $('#latitud-envio');
            const $longitudEnvio = $('#longitud-envio');

            function sincronizarEnvio() {
                $latitudEnvio.val($campoLat.val());
                $longitudEnvio.val($campoLng.val());
            }

            const mapa = L.map('mapa-editar').setView([latInicial, lngInicial], 16);
            L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap'
            }).addTo(mapa);

            const marcador = L.marker([latInicial, lngInicial], { draggable: true }).addTo(mapa);

            function fijarCoordenadas(lat, lng) {
                $campoLat.val(lat.toFixed(6));
                $campoLng.val(lng.toFixed(6));
                sincronizarEnvio();
            }

            fijarCoordenadas(latInicial, lngInicial);

            marcador.on('dragend', function () {
                const posicion = marcador.getLatLng();
                fijarCoordenadas(posicion.lat, posicion.lng);
            });

            mapa.on('click', function (evento) {
                marcador.setLatLng(evento.latlng);
                fijarCoordenadas(evento.latlng.lat, evento.latlng.lng);
            });

            [$campoLat, $campoLng].forEach(function ($input) {
                $input.on('change', function () {
                    const lat = parseFloat($campoLat.val());
                    const lng = parseFloat($campoLng.val());
                    if (Number.isFinite(lat) && Number.isFinite(lng)) {
                        marcador.setLatLng([lat, lng]);
                        mapa.panTo([lat, lng]);
                        sincronizarEnvio();
                    }
                });
            });

            $('#form-datos-sucursal').on('submit', sincronizarEnvio);

            // Buscador de direcciones (Nominatim / OpenStreetMap)
            const $inputBusqueda = $('#buscarLugar');
            const $botonBusqueda = $('#btnBuscarLugar');

            function buscarLugar() {
                const texto = $inputBusqueda.val().trim();
                if (!texto) return;

                UtilidadesFormulario.get('https://nominatim.openstreetmap.org/search', {
                    format: 'json',
                    limit: 1,
                    q: texto
                })
                .done(function (resultados) {
                    if (!resultados.length) {
                        alert('No se encontró el lugar buscado.');
                        return;
                    }
                    const lat = parseFloat(resultados[0].lat);
                    const lng = parseFloat(resultados[0].lon);
                    const nombre = resultados[0].display_name;

                    const $campoDireccion = $('input[name="direccion"]');
                    if ($campoDireccion.length && !$campoDireccion.val().trim()) {
                        $campoDireccion.val(nombre);
                    }

                    marcador.setLatLng([lat, lng]);
                    mapa.panTo([lat, lng], 16);
                    fijarCoordenadas(lat, lng);
                })
                .fail(function () {
                    alert('No se pudo realizar la búsqueda. Revisa tu conexión.');
                });
            }

            $botonBusqueda.on('click', buscarLugar);
            $inputBusqueda.on('keydown', function (evento) {
                if (evento.key === 'Enter') {
                    evento.preventDefault();
                    buscarLugar();
                }
            });

            // Modal de confirmación de bajas: copia la acción y el mensaje del botón disparador.
            const $modalBaja = $('#modal-confirmar-baja');
            if ($modalBaja.length) {
                $modalBaja.on('show.bs.modal', function (evento) {
                    const $disparador = $(evento.relatedTarget);
                    if (!$disparador.length) return;
                    $('#form-confirmar-baja').attr('action', $disparador.data('accion'));
                    $('#texto-confirmar-baja').text($disparador.data('mensaje') || '¿Confirmas realizar esta acción?');
                });
            }
        });
    </script>
@endpush
