<form method="POST" action="{{ route('dashboard.generar-pedidos.store', ['idsucursal' => $sucursalActual->id]) }}"
      id="form-pedido"
      data-accion-crear="{{ route('dashboard.generar-pedidos.store', ['idsucursal' => $sucursalActual->id]) }}">
    @csrf
    <input type="hidden" name="idsucursal" value="{{ $sucursalActual->id }}">

    {{-- Se habilitan solo en modo edición: los campos deshabilitados no viajan
         al servidor, así que al registrar un pedido nuevo no se envían. --}}
    <input type="hidden" name="_method" value="PUT" id="edicion-metodo" disabled>
    <input type="hidden" name="idpedido" id="edicion-idpedido" value="{{ old('idpedido') }}">

    {{-- Recuerda en qué modo estaba el carrito («editar» o «adicionar») cuando
         el servidor devuelve el formulario con errores: sin esto, el envío
         fallido volvería a «crear» y registraría un pedido nuevo en vez de
         actualizar o adicionar al mismo. --}}
    <input type="hidden" name="modocarrito" id="edicion-modo" value="{{ old('modocarrito') }}">

    {{-- Solo en modo adicionar: como los controles de tipo y mesa quedan
         deshabilitados, su valor viaja aquí para que el servidor lo reciba. --}}
    <div id="adicion-campos"></div>

    <div id="aviso-edicion" class="alert alert-info py-2 d-none">
        <div class="d-flex justify-content-between align-items-center gap-2">
            <span class="small">
                ✏️ <span id="edicion-etiqueta">Editando</span> el pedido <strong id="edicion-nropedido"></strong>
            </span>
            <button type="button" class="btn btn-sm btn-outline-info" id="cancelar-edicion">Cancelar</button>
        </div>
    </div>

    <div class="card tarjeta-producto mb-4 sticky-lg-top" style="top: 1rem; z-index: 1020;">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <span class="fw-bold">🛒 Pedido en curso</span>
            <span class="badge text-bg-dark" id="carrito-items">0</span>
        </div>

        <div class="card-body">
            @php($tipoActual = in_array(old('tipopedido'), App\Http\Controllers\PedidoController::TIPOS_PEDIDO, true)
                ? old('tipopedido')
                : ($mesas->isNotEmpty() ? 'MESA' : 'PARA_LLEVAR'))

            <div class="mb-3">
                <span class="form-label d-block mb-1">Tipo de pedido</span>
                <div class="btn-group w-100" role="group" aria-label="Tipo de pedido">
                    <input type="radio" class="btn-check" name="tipopedido" id="tipo-pedido-mesa" value="MESA"
                           autocomplete="off" @checked($tipoActual === 'MESA')>
                    <label class="btn btn-outline-secondary" for="tipo-pedido-mesa">🪑 Mesa</label>

                    <input type="radio" class="btn-check" name="tipopedido" id="tipo-pedido-llevar" value="PARA_LLEVAR"
                           autocomplete="off" @checked($tipoActual === 'PARA_LLEVAR')>
                    <label class="btn btn-outline-secondary" for="tipo-pedido-llevar">🥡 Para llevar</label>
                </div>
                @error('tipopedido')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
            </div>

            <div id="bloque-mesa" class="mb-3 d-none">
                <label class="form-label" for="pedido-mesa">Mesa</label>
                <select class="form-select" name="idmesa" id="pedido-mesa">
                    <option value="">Selecciona una mesa…</option>
                    @foreach ($mesas as $mesa)
                        <option value="{{ $mesa->id }}" @selected((string) old('idmesa') === (string) $mesa->id)>
                            Mesa {{ $mesa->numeromesa }}
                        </option>
                    @endforeach
                </select>
                @error('idmesa')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
            </div>

            <div id="carrito-vacio" class="text-center text-muted py-3">
                Agrega productos del catálogo para empezar.
            </div>

            <ul class="list-group list-group-flush d-none" id="carrito-lista"></ul>

            <div class="d-flex justify-content-between align-items-center mt-3 d-none" id="carrito-total">
                <span class="fw-bold">Total</span>
                <span class="precio h4 mb-0" id="carrito-total-valor">Bs. 0.00</span>
            </div>

            <div id="items-pedido"></div>

            <div class="mt-3">
                <label class="form-label" for="observaciones-pedido">
                    Observaciones <span class="text-muted">(opcional)</span>
                </label>
                <textarea class="form-control" name="observaciones" id="observaciones-pedido" rows="2"
                          maxlength="1000" placeholder="Sin cebolla, extra salsa…">{{ old('observaciones') }}</textarea>
            </div>
            @error('items')
                <div class="text-danger small mt-1">{{ $message }}</div>
            @enderror

            <button type="submit" class="btn btn-brand w-100 mt-3" id="btn-guardar-pedido" disabled>
                <span id="btn-guardar-etiqueta">Registrar pedido</span>
            </button>
            <p class="small text-muted text-center mt-2 mb-0">
                El pedido se registra con los precios de la sucursal y se cobra después desde «Cobrar».
            </p>
        </div>
    </div>
</form>
