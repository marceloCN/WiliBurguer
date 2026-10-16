@extends('layouts.app')

@section('title', 'Gestión de permisos · ' . $configuracionSitio->get('SITIO_NOMBRE'))

@section('content')
    <div class="container py-4">
        <div class="row g-4">
            <div class="col-12">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-2">
                    <div>
                        <h1 class="h3 fw-bold mb-1">Gestión de permisos</h1>
                        <p class="text-muted mb-0">Administra los permisos asignados a cada rol del sistema.</p>
                    </div>
                </div>
            </div>
        </div>

        @include('partials.mensajes')

        {{-- Selector de roles --}}
        <div class="row g-3 mt-1 mb-4">
            @foreach ($roles as $rol)
                <div class="col-6 col-md-3 col-lg-2">
                    <a href="{{ route('dashboard.permisos.index', ['rol' => $rol->id]) }}"
                       class="text-decoration-none">
                        <div class="rol-card card h-100 border-0 {{ $rol->id === $rolSeleccionado->id ? 'rol-active' : 'rol-inactive' }}">
                            <div class="card-body text-center p-3">
                                <div class="rol-icon mb-2">
                                    @switch($rol->claverol)
                                        @case('ADMINISTRADOR')
                                            👑
                                            @break
                                        @case('GERENTE')
                                            🧑‍💼
                                            @break
                                        @case('CAJERO')
                                            💵
                                            @break
                                        @case('MESERO')
                                            🍽️
                                            @break
                                        @default
                                            🏷️
                                    @endswitch
                                </div>
                                <h3 class="h6 fw-bold mb-0">{{ $rol->nombrerol }}</h3>
                                <small class="text-muted">{{ $rol->permisos_count ?? $rol->permisos()->where('estado', true)->count() }} permisos</small>
                            </div>
                        </div>
                    </a>
                </div>
            @endforeach
        </div>

        {{-- Panel de permisos --}}
        <form method="POST" action="{{ route('dashboard.permisos.actualizar', $rolSeleccionado) }}" id="permisosForm">
            @csrf

            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-header bg-white border-bottom-0 pt-4 pb-0 px-4">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                        <div>
                            <h2 class="h5 fw-bold mb-1 text-brand">
                                Permisos de {{ $rolSeleccionado->nombrerol }}
                            </h2>
                            <p class="text-muted small mb-0">Marca o desmarca los permisos que tendrá este rol.</p>
                        </div>
                        <div class="input-group input-group-sm w-100 w-md-auto" style="max-width: 320px;">
                            <span class="input-group-text bg-light border-end-0">
                                🔍
                            </span>
                            <input type="text" class="form-control border-start-0 bg-light" id="buscadorPermisos"
                                   placeholder="Buscar permiso...">
                        </div>
                    </div>
                </div>

                <div class="card-body p-4">
                    @forelse ($permisosPorCategoria as $categoria => $permisos)
                        <div class="categoria-section mb-4">
                            <div class="d-flex align-items-center gap-2 mb-3">
                                <span class="categoria-icon">{{ $loop->iteration }}</span>
                                <h3 class="h6 fw-bold text-uppercase mb-0">{{ $categoria }}</h3>
                                <hr class="flex-grow-1 ms-2 opacity-25">
                            </div>

                            <div class="row g-3">
                                @foreach ($permisos as $permiso)
                                    <div class="col-12 col-md-6 col-xl-4 permiso-item"
                                         data-nombre="{{ strtolower($permiso->nombre) }}"
                                         data-codigo="{{ $permiso->codigo }}">
                                        <label class="permiso-card d-block position-relative rounded-3 p-3 {{ in_array($permiso->id, $permisosAsignados) ? 'permiso-activo' : 'permiso-inactivo' }}">
                                            <input type="checkbox" name="permisos[]" value="{{ $permiso->id }}"
                                                   class="form-check-input permiso-check"
                                                   {{ in_array($permiso->id, $permisosAsignados) ? 'checked' : '' }}>
                                            <span class="permiso-estado position-absolute top-0 end-0 mt-2 me-2">
                                                <span class="badge bg-success estado-activo">Activo</span>
                                                <span class="badge bg-secondary estado-inactivo">Inactivo</span>
                                            </span>
                                            <div class="d-flex align-items-start gap-3">
                                                <span class="permiso-codigo fw-bold rounded-2 px-2 py-1 small">
                                                    {{ $permiso->codigo }}
                                                </span>
                                                <div>
                                                    <span class="permiso-nombre d-block fw-semibold mb-1">
                                                        {{ $permiso->nombre }}
                                                    </span>
                                                    <small class="text-muted">ID: {{ $permiso->id }}</small>
                                                </div>
                                            </div>
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-5 text-muted">
                            <div class="display-6 mb-2">📭</div>
                            <p class="mb-0">No hay permisos activos en el sistema.</p>
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- Barra de acciones sticky --}}
            <div class="acciones-sticky">
                <div class="container">
                    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-center gap-3">
                        <div class="text-center text-sm-start">
                            <span class="fw-semibold text-brand" id="contadorPermisos">0</span>
                            <span class="text-muted">permisos seleccionados para {{ $rolSeleccionado->nombrerol }}</span>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="desmarcarTodos">
                                Desmarcar todos
                            </button>
                            <button type="button" class="btn btn-outline-brand btn-sm" id="marcarTodos">
                                Marcar todos
                            </button>
                            <button type="submit" class="btn btn-brand btn-sm">
                                💾 Guardar cambios
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
@endsection

@push('styles')
    <style>
        .rol-card {
            border-radius: 1rem;
            box-shadow: 0 .25rem 1rem rgba(47, 43, 38, .08);
            transition: transform .15s ease, box-shadow .15s ease, border-color .15s ease;
            cursor: pointer;
        }

        .rol-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 .5rem 1.5rem rgba(47, 43, 38, .14);
        }

        .rol-active {
            background: linear-gradient(135deg, var(--brand) 0%, var(--brand-dark) 100%);
            color: #fff;
        }

        .rol-active h3,
        .rol-active small {
            color: #fff !important;
        }

        .rol-inactive {
            background: #fff;
            color: var(--brand-ink);
        }

        .rol-icon {
            font-size: 1.75rem;
            line-height: 1;
        }

        .categoria-section {
            scroll-margin-top: 100px;
        }

        .categoria-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 28px;
            height: 28px;
            background: var(--brand);
            color: #fff;
            border-radius: 50%;
            font-size: .75rem;
            font-weight: 700;
        }

        .permiso-card {
            border: 2px solid transparent;
            background: #fff;
            box-shadow: 0 2px 8px rgba(47, 43, 38, .06);
            cursor: pointer;
            transition: all .15s ease;
            min-height: 90px;
        }

        .permiso-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(47, 43, 38, .12);
        }

        .permiso-activo {
            border-color: var(--brand);
            background: color-mix(in srgb, var(--brand) 8%, #fff);
        }

        .permiso-inactivo {
            border-color: #e9e5dd;
            background: #fff;
        }

        .permiso-check {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }

        .permiso-check:checked + .permiso-estado .estado-activo {
            display: inline-block;
        }

        .permiso-check:checked + .permiso-estado .estado-inactivo {
            display: none;
        }

        .permiso-check:not(:checked) + .permiso-estado .estado-activo {
            display: none;
        }

        .permiso-check:not(:checked) + .permiso-estado .estado-inactivo {
            display: inline-block;
        }

        .permiso-codigo {
            background: color-mix(in srgb, var(--brand) 15%, transparent);
            color: var(--brand-dark);
        }

        .permiso-activo .permiso-codigo {
            background: var(--brand);
            color: #fff;
        }

        .permiso-activo .permiso-nombre {
            color: var(--brand-dark);
        }

        .acciones-sticky {
            position: sticky;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(255, 255, 255, .96);
            backdrop-filter: blur(8px);
            border-top: 1px solid #e9e5dd;
            padding: 1rem 0;
            margin-top: 2rem;
            z-index: 1030;
        }

        .permiso-item.oculto {
            display: none !important;
        }

        .categoria-vacia {
            display: none !important;
        }
    </style>
@endpush

@push('scripts')
    <script type="module">
        $(function() {
            const $form = $('#permisosForm');
            const $contador = $('#contadorPermisos');
            const $buscador = $('#buscadorPermisos');
            const $permisoItems = $('.permiso-item');
            const $categoriaSections = $('.categoria-section');

            function actualizarCard($card, isChecked) {
                if (isChecked) {
                    $card.removeClass('permiso-inactivo').addClass('permiso-activo');
                } else {
                    $card.removeClass('permiso-activo').addClass('permiso-inactivo');
                }
            }

            function actualizarContador() {
                const seleccionados = $form.find('.permiso-check:checked').length;
                $contador.text(seleccionados);
            }

            // Al hacer clic sobre el <label>, el navegador activa el checkbox nativamente.
            // Solo escuchamos 'change' para sincronizar diseño y contador sin conflictos.
            $form.on('change', '.permiso-check', function() {
                const $check = $(this);
                actualizarCard($check.closest('.permiso-card'), $check.is(':checked'));
                actualizarContador();
            });

            // Buscador en tiempo real por nombre y código
            $buscador.on('input', function() {
                const termino = $(this).val().toLowerCase().trim();

                $permisoItems.each(function() {
                    const $item = $(this);
                    const nombre = String($item.data('nombre') || '');
                    const codigo = String($item.data('codigo') || '');
                    const coincide = nombre.includes(termino) || codigo.includes(termino);
                    $item.toggleClass('oculto', !coincide);
                });

                $categoriaSections.each(function() {
                    const $seccion = $(this);
                    const visibles = $seccion.find('.permiso-item:not(.oculto)').length;
                    $seccion.toggleClass('categoria-vacia', visibles === 0);
                });
            });

            // Botón: Marcar todos los visibles
            $('#marcarTodos').on('click', function() {
                $form.find('.permiso-item:not(.oculto) .permiso-check').each(function() {
                    const $check = $(this);
                    $check.prop('checked', true);
                    actualizarCard($check.closest('.permiso-card'), true);
                });
                actualizarContador();
            });

            // Botón: Desmarcar todos los visibles
            $('#desmarcarTodos').on('click', function() {
                $form.find('.permiso-item:not(.oculto) .permiso-check').each(function() {
                    const $check = $(this);
                    $check.prop('checked', false);
                    actualizarCard($check.closest('.permiso-card'), false);
                });
                actualizarContador();
            });

            // Inicializar contador al cargar la vista
            actualizarContador();
        });
    </script>
@endpush
