<div class="card tarjeta-producto mb-4">
    <div class="card-header bg-light d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-2">
        <span class="fw-bold">🧾 Pedidos de hoy · {{ $sucursalActual->nombre }}</span>
        <div class="d-flex gap-2 flex-wrap">
            <span class="badge text-bg-dark">Total: {{ $conteoPedidos->sum() }}</span>
            @foreach ($estadosPedido as $estado => $configuracion)
                <span class="badge text-bg-{{ $configuracion['color'] }}">
                    {{ $configuracion['icono'] }} {{ $configuracion['etiqueta'] }}: {{ $conteoPedidos[$estado] ?? 0 }}
                </span>
            @endforeach
        </div>
    </div>

    <div class="card-body">
        <div class="table-responsive mb-3 d-none d-md-block">
            <table class="table table-sm table-borderless mb-0">
                <thead>
                    <tr>
                        <th class="text-center" style="min-width: 80px;">#</th>
                        <th style="min-width: 120px;">Hora / Tipo</th>
                        <th style="min-width: 120px;">Pago</th>
                        <th class="text-end" style="min-width: 100px;">Total</th>
                        <th class="text-end" style="min-width: 220px;">Acción</th>
                    </tr>
                </thead>
            </table>
        </div>

        <ul class="nav nav-pills mb-3 flex-nowrap overflow-auto pb-1" role="tablist">
            @foreach ($estadosPedido as $estado => $configuracion)
                <li class="nav-item flex-shrink-0" role="presentation">
                    <button class="nav-link {{ $loop->first ? 'active' : '' }}"
                            id="pestana-pedidos-{{ $configuracion['pagina'] }}"
                            type="button"
                            role="tab"
                            data-bs-toggle="pill"
                            data-bs-target="#panel-pedidos-{{ $configuracion['pagina'] }}"
                            aria-controls="panel-pedidos-{{ $configuracion['pagina'] }}"
                            aria-selected="{{ $loop->first ? 'true' : 'false' }}">
                        {{ $configuracion['icono'] }} {{ $configuracion['etiqueta'] }}
                        <span class="badge text-bg-{{ $configuracion['color'] }}">
                            {{ $conteoPedidos[$estado] ?? 0 }}
                        </span>
                    </button>
                </li>
            @endforeach
        </ul>

        <div class="tab-content">
            @foreach ($estadosPedido as $estado => $configuracion)
                @php($paginador = $pedidosPorEstado[$estado] ?? null)

                <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}"
                     id="panel-pedidos-{{ $configuracion['pagina'] }}"
                     role="tabpanel"
                     aria-labelledby="pestana-pedidos-{{ $configuracion['pagina'] }}">
                    @if (! $paginador || $paginador->isEmpty())
                        <div class="alert alert-light text-center mb-0">
                            No hay pedidos {{ strtolower($configuracion['etiqueta']) }} hoy en {{ $sucursalActual->nombre }}.
                        </div>
                    @else
                        <div class="table-responsive d-none d-md-block">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Hora</th>
                                        <th>Tipo</th>
                                        <th>Pago</th>
                                        <th class="text-end">Total</th>
                                        <th class="text-end">Acción</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($paginador as $pedido)
                                        <tr>
                                            <td class="fw-semibold">{{ $pedido->nropedido }}</td>
                                            <td>{{ $pedido->fecha?->format('H:i') }}</td>
                                            <td>
                                                <span class="badge text-bg-light border">
                                                    {{ $pedido->tipopedido === 'MESA'
                                                        ? 'Mesa '.($pedido->mesa?->numeromesa ?? '—')
                                                        : 'Para llevar' }}
                                                </span>
                                            </td>
                                            @php($pendienteCobro = $pedido->saldoPendiente())
                                            @php($metodosCobro = $pedido->ventas->flatMap(fn ($venta) => $venta->pagos)->pluck('metodoPago.nombre')->filter()->unique()->values())
                                            <td>
                                                @if ($metodosCobro->isEmpty())
                                                    Sin cobro
                                                @else
                                                    {{ $metodosCobro->implode(', ') }}
                                                    @if ($pendienteCobro > 0)
                                                        · Bs. {{ number_format($pendienteCobro, 2) }} pendiente
                                                    @endif
                                                @endif
                                            </td>
                                            <td class="text-end">Bs. {{ number_format($pedido->total, 2) }}</td>
                                            <td class="text-end">
                                                <div class="d-flex gap-1 justify-content-end flex-wrap">
                                                    <button type="button"
                                                            class="btn btn-sm btn-outline-info ver-detalle"
                                                            data-accion="{{ route('dashboard.generar-pedidos.detalle', $pedido) }}"
                                                            data-nropedido="{{ $pedido->nropedido }}">
                                                        Ver
                                                    </button>

                                                    @if ($estado === 'PENDIENTE')
                                                        @unless ($puedeEditar)
                                                            <span class="text-muted small align-self-center">
                                                                Requiere el permiso 501
                                                            </span>
                                                        @else
                                                            <button type="button"
                                                                    class="btn btn-sm btn-outline-secondary editar-pedido"
                                                                    data-accion="{{ route('dashboard.generar-pedidos.editar', $pedido) }}">
                                                                Editar
                                                            </button>

                                                            <form method="POST"
                                                                  action="{{ route('dashboard.generar-pedidos.ejecutar', $pedido) }}"
                                                                  class="d-inline">
                                                                @csrf
                                                                <button type="submit" class="btn btn-sm btn-success">
                                                                    Ejecutar
                                                                </button>
                                                            </form>

                                                            <button type="button"
                                                                    class="btn btn-sm btn-primary cobrar-pedido"
                                                                    data-accion="{{ route('dashboard.generar-pedidos.cobrar', $pedido) }}"
                                                                    data-nropedido="{{ $pedido->nropedido }}"
                                                                    data-total="{{ number_format($pedido->saldoPendiente(), 2, '.', '') }}" data-cuentas='@json($pedido->cuentas())'>
                                                                Cobrar
                                                            </button>

                                                            @if ($puedeAnular)
                                                                <form method="POST"
                                                                      action="{{ route('dashboard.generar-pedidos.anular', $pedido) }}"
                                                                      class="d-inline"
                                                                      onsubmit="return confirm('¿Anular el pedido #{{ $pedido->nropedido }}? Perderá todas sus acciones.')">
                                                                    @csrf
                                                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                                                        Anular
                                                                    </button>
                                                                </form>
                                                            @else
                                                                <span class="text-muted small align-self-center">
                                                                    Requiere el permiso 505
                                                                </span>
                                                            @endif
                                                        @endunless

                                                    @elseif ($estado === 'EJECUTADO')
                                                        @unless ($puedeEditar)
                                                            <span class="text-muted small align-self-center">
                                                                Requiere el permiso 501
                                                            </span>
                                                        @else
                                                            <button type="button"
                                                                    class="btn btn-sm btn-outline-success adicionar-pedido"
                                                                    data-accion="{{ route('dashboard.generar-pedidos.adicionar', $pedido) }}"
                                                                    data-nropedido="{{ $pedido->nropedido }}">
                                                                Adicionar
                                                            </button>

                                                            <button type="button"
                                                                    class="btn btn-sm btn-primary cobrar-pedido"
                                                                    data-accion="{{ route('dashboard.generar-pedidos.cobrar', $pedido) }}"
                                                                    data-nropedido="{{ $pedido->nropedido }}"
                                                                    data-total="{{ number_format($pedido->saldoPendiente(), 2, '.', '') }}" data-cuentas='@json($pedido->cuentas())'>
                                                                Cobrar
                                                            </button>
                                                        @endunless
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        {{-- Vista móvil: cards en lugar de tabla --}}
                        <div class="d-md-none">
                            <div class="vstack gap-3">
                                @foreach ($paginador as $pedido)
                                    @php($pendienteCobro = $pedido->saldoPendiente())
                                    @php($metodosCobro = $pedido->ventas->flatMap(fn ($venta) => $venta->pagos)->pluck('metodoPago.nombre')->filter()->unique()->values())

                                    <div class="card card-body border shadow-sm">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <div>
                                                <div class="fw-bold h5 mb-0">#{{ $pedido->nropedido }}</div>
                                                <div class="small text-muted">{{ $pedido->fecha?->format('H:i') }}</div>
                                            </div>
                                            <span class="badge text-bg-light border">
                                                {{ $pedido->tipopedido === 'MESA'
                                                    ? 'Mesa '.($pedido->mesa?->numeromesa ?? '—')
                                                    : 'Para llevar' }}
                                            </span>
                                        </div>

                                        <div class="small mb-2">
                                            <span class="text-muted">Pago:</span>
                                            @if ($metodosCobro->isEmpty())
                                                Sin cobro
                                            @else
                                                {{ $metodosCobro->implode(', ') }}
                                            @endif
                                            @if ($pendienteCobro > 0)
                                                <div class="text-warning">Bs. {{ number_format($pendienteCobro, 2) }} pendiente</div>
                                            @endif
                                        </div>

                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <span class="text-muted small">Total</span>
                                            <span class="fw-bold">Bs. {{ number_format($pedido->total, 2) }}</span>
                                        </div>

                                        <div class="d-flex flex-wrap gap-2">
                                            <button type="button"
                                                    class="btn btn-sm btn-outline-info ver-detalle flex-grow-1"
                                                    data-accion="{{ route('dashboard.generar-pedidos.detalle', $pedido) }}"
                                                    data-nropedido="{{ $pedido->nropedido }}">
                                                Ver
                                            </button>

                                            @if ($estado === 'PENDIENTE' && $puedeEditar)
                                                <button type="button"
                                                        class="btn btn-sm btn-outline-secondary editar-pedido flex-grow-1"
                                                        data-accion="{{ route('dashboard.generar-pedidos.editar', $pedido) }}">
                                                    Editar
                                                </button>

                                                <form method="POST"
                                                      action="{{ route('dashboard.generar-pedidos.ejecutar', $pedido) }}"
                                                      class="d-inline flex-grow-1">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-success w-100">
                                                        Ejecutar
                                                    </button>
                                                </form>

                                                <button type="button"
                                                        class="btn btn-sm btn-primary cobrar-pedido flex-grow-1"
                                                        data-accion="{{ route('dashboard.generar-pedidos.cobrar', $pedido) }}"
                                                        data-nropedido="{{ $pedido->nropedido }}"
                                                        data-total="{{ number_format($pedido->saldoPendiente(), 2, '.', '') }}" data-cuentas='@json($pedido->cuentas())'>
                                                    Cobrar
                                                </button>

                                                @if ($puedeAnular)
                                                    <form method="POST"
                                                          action="{{ route('dashboard.generar-pedidos.anular', $pedido) }}"
                                                          class="d-inline flex-grow-1"
                                                          onsubmit="return confirm('¿Anular el pedido #{{ $pedido->nropedido }}? Perderá todas sus acciones.')">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-outline-danger w-100">
                                                            Anular
                                                        </button>
                                                    </form>
                                                @endif
                                            @elseif ($estado === 'EJECUTADO' && $puedeEditar)
                                                <button type="button"
                                                        class="btn btn-sm btn-outline-success adicionar-pedido flex-grow-1"
                                                        data-accion="{{ route('dashboard.generar-pedidos.adicionar', $pedido) }}"
                                                        data-nropedido="{{ $pedido->nropedido }}">
                                                    Adicionar
                                                </button>

                                                <button type="button"
                                                        class="btn btn-sm btn-primary cobrar-pedido flex-grow-1"
                                                        data-accion="{{ route('dashboard.generar-pedidos.cobrar', $pedido) }}"
                                                        data-nropedido="{{ $pedido->nropedido }}"
                                                        data-total="{{ number_format($pedido->saldoPendiente(), 2, '.', '') }}" data-cuentas='@json($pedido->cuentas())'>
                                                    Cobrar
                                                </button>
                                            @elseif (! $puedeEditar && in_array($estado, ['PENDIENTE', 'EJECUTADO']))
                                                <span class="text-muted small align-self-center">
                                                    Requiere el permiso 501
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        {{ $paginador->onEachSide(1)->links() }}
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</div>

{{-- El cobro vive en una modal y no en la fila de la tabla: en efectivo hay que
     ver el total, escribir lo recibido y ver el cambio antes de confirmar, para
     no cobrar dejando esos datos en blanco. --}}
@if ($puedeEditar)
    <div class="modal fade" id="modal-cobro" tabindex="-1" aria-labelledby="cobro-titulo" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" action="" id="form-cobro">
                @csrf

                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="cobro-titulo">Cobrar pedido</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>

                    <div class="modal-body">
                        <p class="mb-3">
                            Total a cobrar:
                            <span class="fw-bold fs-5" id="cobro-total">Bs. 0.00</span>
                        </p>

                        {{-- Con cuentas separadas el cliente decide si paga el
                             total pendiente o solo una de sus cuentas. El
                             bloque aparece únicamente cuando hay más de una
                             cuenta pendiente; el resto del tiempo la lista
                             manda el total y viaja vacío. --}}
                        <div class="mb-3 d-none" id="bloque-cuenta">
                            <label class="form-label" for="cobro-cuenta">Qué se cobra</label>
                            <select class="form-select" name="nrocuenta" id="cobro-cuenta">
                                <option value="">Total pendiente</option>
                            </select>
                            <div class="form-text">
                                Este pedido tiene varias cuentas: pagan todas juntas o cada quien la suya.
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="cobro-metodo">Método de pago</label>
                            <select class="form-select" name="idmetodopago" id="cobro-metodo" required>
                                @foreach ($metodosPago as $metodo)
                                    <option value="{{ $metodo->id }}"
                                            data-gateway="{{ strtoupper($metodo->gateway ?? '') }}">
                                        {{ $metodo->nombre }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Solo aparece en efectivo, y ahí es obligatorio: sin el
                             monto recibido no hay cambio que entregar. --}}
                        <div id="bloque-recibido" class="d-none">
                            <label class="form-label" for="cobro-recibido">Monto recibido (Bs.)</label>
                            <input type="number" class="form-control" name="montorecibido" id="cobro-recibido"
                                   min="0" step="0.01" inputmode="decimal" required>
                            <div class="form-text" id="cobro-cambio">Cambio: Bs. 0.00</div>
                        </div>

                        {{-- Campos de tarjeta: aparecen solo cuando el método es
                             TARJETA. El pago se procesa contra WizPay desde el
                             frontend y la referencia viaja al backend. --}}
                        <div id="bloque-tarjeta" class="d-none">
                            <hr class="my-3">
                            <div class="d-flex align-items-center gap-2 mb-3">
                                <span class="badge text-bg-dark">💳 Datos de la tarjeta</span>
                                <span class="small text-muted">Procesado por WizPay</span>
                            </div>

                            <input type="hidden" name="wizpay_reference" id="wizpay-reference">

                            <div class="row g-2">
                                <div class="col-12">
                                    <label class="form-label" for="card-number">Número de tarjeta</label>
                                    <input type="text" class="form-control" id="card-number"
                                           placeholder="0000 0000 0000 0000" inputmode="numeric"
                                           maxlength="19" autocomplete="cc-number">
                                </div>
                                <div class="col-6">
                                    <label class="form-label" for="card-expiry">Expiración</label>
                                    <input type="text" class="form-control" id="card-expiry"
                                           placeholder="MM/AAAA" inputmode="numeric" maxlength="7"
                                           autocomplete="cc-exp">
                                </div>
                                <div class="col-6">
                                    <label class="form-label" for="card-cvv">CVV</label>
                                    <input type="password" class="form-control" id="card-cvv"
                                           placeholder="123" inputmode="numeric" maxlength="4"
                                           autocomplete="cc-csc">
                                </div>
                                <div class="col-12">
                                    <label class="form-label" for="card-name">Nombre del titular</label>
                                    <input type="text" class="form-control" id="card-name"
                                           placeholder="Como figura en la tarjeta" autocomplete="cc-name">
                                </div>
                                <div class="col-6">
                                    <label class="form-label" for="card-email">Email</label>
                                    <input type="email" class="form-control" id="card-email"
                                           placeholder="cliente@email.com" autocomplete="email">
                                </div>
                                <div class="col-6">
                                    <label class="form-label" for="card-phone">Teléfono</label>
                                    <input type="tel" class="form-control" id="card-phone"
                                           placeholder="70000000" autocomplete="tel">
                                </div>
                            </div>

                            <div class="mt-2 small text-muted" id="wizpay-estado"></div>

                            {{-- iframe oculto para la Device Data Collection de 3DS --}}
                            <div id="wizpay-ddc" class="d-none"></div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary" id="cobro-confirmar">Confirmar cobro</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endif

{{-- «Ver» detalle: solo lectura y disponible en los cuatro estados, así que vive
     fuera del guard de permiso de edición. Los datos llegan en JSON desde
     PedidoController::detalle(). --}}
<div class="modal fade" id="modal-detalle" tabindex="-1" aria-labelledby="detalle-titulo" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable modal-fullscreen-sm-down">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="detalle-titulo">Detalle del pedido <span id="detalle-nropedido"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>

            <div class="modal-body">
                <div class="row g-3 mb-4">
                    <div class="col-6 col-md-3">
                        <span class="text-muted small d-block">Estado</span>
                        <span class="badge text-bg-dark" id="detalle-estado"></span>
                    </div>
                    <div class="col-6 col-md-3">
                        <span class="text-muted small d-block">Tipo</span>
                        <strong id="detalle-tipo"></strong>
                    </div>
                    <div class="col-6 col-md-3">
                        <span class="text-muted small d-block">Fecha</span>
                        <strong id="detalle-hora"></strong>
                    </div>
                    <div class="col-6 col-md-3">
                        <span class="text-muted small d-block">Atendido por</span>
                        <strong id="detalle-empleado"></strong>
                    </div>
                    <div class="col-12" id="detalle-observaciones-bloque">
                        <span class="text-muted small d-block">Observaciones</span>
                        <span id="detalle-observaciones"></span>
                    </div>
                    <div class="col-12" id="detalle-pago-bloque">
                        <span class="text-muted small d-block">Pago</span>
                        <strong id="detalle-pago"></strong>
                    </div>

                    {{-- Cada «Adicionar» abre una cuenta: aquí se ve cuántas
                         tiene el pedido y cuáles ya están cobradas. --}}
                    <div class="col-12 d-none" id="detalle-cuentas-bloque">
                        <span class="text-muted small d-block">Cuentas</span>
                        <div class="d-flex flex-wrap gap-2" id="detalle-cuentas"></div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th>Item</th>
                                <th class="text-end">Precio</th>
                                <th class="text-end">Cant.</th>
                                <th class="text-end">Desc.</th>
                                <th class="text-end">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody id="detalle-items"></tbody>
                        <tfoot>
                            <tr>
                                <td colspan="4" class="text-end text-muted">Subtotal</td>
                                <td class="text-end" id="detalle-subtotal">Bs. 0.00</td>
                            </tr>
                            <tr>
                                <td colspan="4" class="text-end text-muted">Descuento</td>
                                <td class="text-end" id="detalle-descuento">Bs. 0.00</td>
                            </tr>
                            <tr class="fw-bold">
                                <td colspan="4" class="text-end">Total</td>
                                <td class="text-end" id="detalle-total">Bs. 0.00</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
