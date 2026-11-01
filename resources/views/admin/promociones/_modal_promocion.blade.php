@php
    $tiposDescuento = [
        'PORCENTAJE' => 'Porcentaje de descuento (%)',
        'DESCUENTO_MONTO' => 'Descuento en monto (Bs.)',
        'PRECIO_PROMOCIONAL' => 'Precio promocional (Bs.)',
    ];
@endphp

<div class="modal fade" id="promocionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-fullscreen-sm-down modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            @if ($puedeCrear || $puedeEditar)
                <form method="POST" id="form-promocion" enctype="multipart/form-data"
                      action="{{ route('dashboard.promociones.store') }}"
                      data-update-url-template="{{ route('dashboard.promociones.update', 'ID') }}"
                      class="d-flex flex-column flex-grow-1" style="min-height: 0;">
                    @csrf
                    <div class="modal-header bg-light">
                        <h5 class="modal-title fw-bold" id="promocionTitulo">Nueva promoción</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label for="promocionNombre" class="form-label">Nombre de la promoción *</label>
                                <input type="text" name="nombre" id="promocionNombre" class="form-control"
                                       maxlength="100" required placeholder="Ej.: 2x1 los martes">
                            </div>

                            <div class="col-12 d-none" id="bloqueSucursal">
                                <label for="promocionSucursal" class="form-label">Sucursal *</label>
                                <select name="idsucursal" id="promocionSucursal" class="form-select">
                                    @foreach ($sucursales as $sucursal)
                                        <option value="{{ $sucursal->id }}" @selected($sucursalActual->id === $sucursal->id)>
                                            {{ $sucursal->nombre }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="form-text">La promoción se publica en esta sucursal y no se puede mover.</div>
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="promocionTipo" class="form-label">Tipo de descuento *</label>
                                <select name="tipodescuento" id="promocionTipo" class="form-select" required>
                                    @foreach ($tiposDescuento as $valor => $etiqueta)
                                        <option value="{{ $valor }}" @selected(old('tipodescuento') === $valor)>{{ $etiqueta }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 col-md-6">
                                <label for="promocionValor" class="form-label">Valor del descuento *</label>
                                <input type="number" name="valordescuento" id="promocionValor" class="form-control"
                                       min="0" step="0.01" required placeholder="Ej.: 20">
                            </div>
                            <div class="col-12 col-md-6">
                                <label for="promocionInicio" class="form-label">Vigente desde *</label>
                                <input type="date" name="fechainicio" id="promocionInicio" class="form-control" required>
                            </div>
                            <div class="col-12 col-md-6">
                                <label for="promocionFin" class="form-label">Hasta *</label>
                                <input type="date" name="fechafin" id="promocionFin" class="form-control" required>
                            </div>

                            <div class="col-12">
                                <label class="form-label d-block">Días en que aplica *</label>
                                <div class="d-flex flex-wrap gap-2">
                                    @foreach ([1 => 'Lun', 2 => 'Mar', 3 => 'Mié', 4 => 'Jue', 5 => 'Vie', 6 => 'Sáb', 7 => 'Dom'] as $numero => $dia)
                                        <div class="form-check">
                                            <input class="form-check-input promocion-dias" type="checkbox"
                                                   name="dias[]" id="dia{{ $numero }}" value="{{ $numero }}">
                                            <label class="form-check-label" for="dia{{ $numero }}">{{ $dia }}</label>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            <div class="col-12">
                                <label class="form-label d-block">Foto de la promoción</label>
                                <div id="fotoActual" class="d-none align-items-start gap-3 p-3 bg-light border rounded mb-3">
                                    <div class="flex-shrink-0">
                                        <img id="fotoActualImg" src="" alt="Foto actual de la promoción"
                                             class="rounded border object-fit-cover bg-white"
                                             style="width: 96px; height: 96px;">
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="fw-semibold mb-2">Foto actual</div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="quitar_photo"
                                                   id="quitarPhoto" value="1">
                                            <label class="form-check-label text-danger" for="quitarPhoto">
                                                Quitar la foto actual
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div id="fotoPreview" class="d-none align-items-start gap-3 p-3 bg-light border rounded mb-3">
                                    <div class="flex-shrink-0">
                                        <img id="fotoPreviewImg" src="" alt="Vista previa de la nueva foto"
                                             class="rounded border object-fit-cover bg-white"
                                             style="width: 96px; height: 96px;">
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="fw-semibold mb-2">Vista previa</div>
                                        <div class="form-text mb-0">Así se verá la foto si guardas los cambios.</div>
                                    </div>
                                </div>
                                <label for="promocionPhoto" class="form-label">Cambiar foto</label>
                                <input type="file" name="photo" id="promocionPhoto" class="form-control"
                                       accept="image/jpeg,image/png,image/webp">
                                <div class="form-text">JPG, PNG o WEBP de hasta 2 MB. Opcional.</div>
                            </div>

                            <div class="col-12">
                                <label class="form-label d-block">Productos participantes *</label>

                                @if ($productosDisponibles->isEmpty())
                                    <div class="alert alert-warning mb-0">
                                        Esta sucursal aún no tiene productos asignados con disponibilidad.
                                        Asignalos primero en
                                        <a href="{{ route('dashboard.productos-sucursal.index', ['idsucursal' => $sucursalActual->id]) }}" class="alert-link">
                                            Productos por sucursal
                                        </a>.
                                    </div>
                                @else
                                    <div class="table-responsive border rounded d-none d-md-block">
                                        <table class="table table-sm align-middle mb-0">
                                            <thead class="bg-light">
                                                <tr>
                                                    <th style="width: 48px;">Incl.</th>
                                                    <th>Producto</th>
                                                    <th style="width: 140px;">Precio</th>
                                                    <th style="width: 160px;">Cantidad</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($productosDisponibles as $productoSucursal)
                                                    <tr>
                                                        <td>
                                                            <input class="form-check-input promocion-producto"
                                                                   type="checkbox" name="productos[]"
                                                                   id="producto{{ $productoSucursal->id }}"
                                                                   value="{{ $productoSucursal->id }}">
                                                        </td>
                                                        <td>
                                                            <label class="form-check-label" for="producto{{ $productoSucursal->id }}">
                                                                {{ $productoSucursal->producto?->nombre }}
                                                            </label>
                                                        </td>
                                                        <td class="text-muted small">
                                                            Bs. {{ number_format((float) $productoSucursal->precioproducto, 2) }}
                                                        </td>
                                                        <td>
                                                            <input type="number" name="cantidades[{{ $productoSucursal->id }}]"
                                                                   class="form-control form-control-sm promocion-cantidad"
                                                                   data-id="{{ $productoSucursal->id }}"
                                                                   min="1" max="999" value="1" disabled>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>

                                    <div class="d-md-none border rounded p-2">
                                        @foreach ($productosDisponibles as $productoSucursal)
                                            <div class="form-check d-flex align-items-center gap-2 py-2 {{ ! $loop->last ? 'border-bottom' : '' }}">
                                                <input class="form-check-input promocion-producto mt-0"
                                                       type="checkbox"
                                                       id="productoMovil{{ $productoSucursal->id }}"
                                                       value="{{ $productoSucursal->id }}">
                                                <label class="form-check-label flex-grow-1" for="productoMovil{{ $productoSucursal->id }}">
                                                    <div>{{ $productoSucursal->producto?->nombre }}</div>
                                                    <div class="small text-muted">Bs. {{ number_format((float) $productoSucursal->precioproducto, 2) }}</div>
                                                </label>
                                                <input type="number"
                                                       class="form-control form-control-sm promocion-cantidad"
                                                       data-id="{{ $productoSucursal->id }}"
                                                       min="1" max="999" value="1" disabled
                                                       style="width: 80px;">
                                            </div>
                                        @endforeach
                                    </div>

                                    <div class="form-text">
                                        La cantidad es cuántas unidades intervienen en la oferta, por ejemplo 2 en un 2x1.
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-brand" id="promocionSubmit">Publicar promoción</button>
                    </div>
                </form>
            @else
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold">Promociones</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning mb-0">
                        <strong>Sin permiso.</strong>
                        No tienes autorización para crear ni editar promociones.
                        Solicita al administrador los permisos <strong>350</strong> y <strong>351</strong>
                        si necesitas realizar estas acciones.
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            @endif
        </div>
    </div>
</div>
