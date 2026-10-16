@extends('layouts.app')

@section('title', 'Nueva sucursal · '.$configuracionSitio->get('SITIO_NOMBRE'))

@section('content')
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
            <div>
                <h1 class="h3 fw-bold mb-0">Nueva sucursal</h1>
                <p class="text-muted mb-0">Registra una nueva sucursal y asígnale un gerente del sistema.</p>
            </div>
            <a href="{{ route('dashboard.sucursales.index') }}" class="btn btn-outline-secondary btn-sm">← Volver</a>
        </div>

        @include('partials.mensajes')

        <form method="POST" action="{{ route('dashboard.sucursales.store') }}">
            @csrf
            <div class="card tarjeta-producto">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nombre *</label>
                            <input type="text" name="nombre" class="form-control" required
                                   value="{{ old('nombre') }}" maxlength="255" placeholder="Ej: Sucursal Centro...">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Teléfono *</label>
                            <input type="text" name="telefono" class="form-control" required
                                   value="{{ old('telefono') }}" maxlength="15" placeholder="Ej: +591 75535749">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Dirección *</label>
                            <input type="text" name="direccion" class="form-control" required
                                   value="{{ old('direccion') }}" maxlength="255"
                                   placeholder="Santa Cruz, La Guardia">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Gerente asignado</label>
                            <select name="idempleadogerente" class="form-select">
                                <option value="">— Sin gerente (se puede asignar después) —</option>
                                @foreach ($gerentesDisponibles as $gerente)
                                    <option value="{{ $gerente->id }}" @selected(old('idempleadogerente') == $gerente->id)>
                                        {{ $gerente->nombre }} {{ $gerente->apellido }}
                                    </option>
                                @endforeach
                            </select>
                            <p class="small text-muted mt-1 mb-0">
                                Solo se muestran gerentes activos del sistema que aún no están asignados a otra sucursal.
                            </p>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Ubicación en el mapa</label>
                            <p class="small text-muted mb-2">
                                Haz clic en el mapa o arrastra el marcador para fijar la ubicación exacta.
                            </p>
                            <div id="mapa-crear" class="mapa-crear mb-2"></div>
                            <div class="row g-2">
                                <div class="col-6">
                                    <label class="form-label small fw-bold">Latitud *</label>
                                    <input type="number" step="any" name="latitud" id="latitud" class="form-control"
                                           value="{{ old('latitud') }}" required>
                                </div>
                                <div class="col-6">
                                    <label class="form-label small fw-bold">Longitud *</label>
                                    <input type="number" step="any" name="longitud" id="longitud" class="form-control"
                                           value="{{ old('longitud') }}" required>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-light-subtle border-top">
                    <button type="submit" class="btn btn-brand">Crear sucursal</button>
                </div>
            </div>
        </form>
    </div>
@endsection

@push('styles')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <style>
        .mapa-crear {
            height: 320px;
            border-radius: 0.75rem;
            background: #eee;
            z-index: 0;
        }
    </style>
@endpush

@push('scripts')
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script type="module">
        $(document).ready(function () {
            const latInicial = {{ (float) (old('latitud') ?? -17.892256) }};
            const lngInicial = {{ (float) (old('longitud') ?? -63.328629) }};

            const $campoLat = $('#latitud');
            const $campoLng = $('#longitud');

            const mapa = L.map('mapa-crear').setView([latInicial, lngInicial], 16);
            L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap'
            }).addTo(mapa);

            const marcador = L.marker([latInicial, lngInicial], { draggable: true }).addTo(mapa);

            function fijarCoordenadas(lat, lng) {
                $campoLat.val(lat.toFixed(6));
                $campoLng.val(lng.toFixed(6));
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
        });
    </script>
@endpush
