@extends('layouts.app')

@section('title', 'Generar pedido · '.$configuracionSitio->get('SITIO_NOMBRE'))

@section('content')
    <div class="container py-4">
        @include('admin.pedidos._header')
        @include('partials.mensajes')

        @if (! $aperturaCaja)
            <div class="alert alert-warning d-flex justify-content-between align-items-center gap-3 flex-wrap mb-4"
                 role="alert">
                <div class="d-flex align-items-start gap-2">
                    <span class="fs-5">💰</span>
                    <div>
                        <strong>No hay una caja abierta en {{ $sucursalActual->nombre }}.</strong>
                        No se pueden registrar pedidos hasta abrir la caja del turno.
                    </div>
                </div>
                @if ($puedeVerCaja)
                    <a class="btn btn-sm btn-warning fw-semibold text-nowrap"
                       href="{{ route('dashboard.caja.index', ['idsucursal' => $sucursalActual->id]) }}">
                        Ir a caja
                    </a>
                @endif
            </div>
        @endif

        {{-- Pedidos de hoy en la parte superior, divididos por estado --}}
        @include('admin.pedidos._pedidos')

        <div class="row g-4">
            {{-- En móvil el carrito va primero para que el cajero vea lo que
                 agrega; en escritorio se queda a la derecha. --}}
            <div class="col-12 col-xl-4 order-1 order-xl-2">
                {{-- El formulario de registro solo existe con caja abierta --}}
                @if ($aperturaCaja)
                    @include('admin.pedidos._carrito')
                @endif
            </div>

            <div class="col-12 col-xl-8 order-2 order-xl-1">
                @include('admin.pedidos._catalogo')
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script type="module">
        $(document).ready(function () {
            // Filtro de categorías del catálogo
            $('.categoria-filtro').on('click', function () {
                const categoria = $(this).data('categoria');

                $('.categoria-filtro').removeClass('active').addClass('btn-outline-secondary');
                $(this).addClass('active').removeClass('btn-outline-secondary');

                if (categoria === '') {
                    $('.bloque-categoria').show();
                } else {
                    $('.bloque-categoria').hide();
                    $(`.bloque-categoria[data-categoria="${categoria}"]`).show();
                }
            });

            // Buscador rápido de productos
            $('#busqueda-productos').on('input', function () {
                const texto = $(this).val().toLowerCase().trim();

                $('.bloque-categoria').each(function () {
                    let visibles = 0;

                    $(this).find('.producto-item').each(function () {
                        const coincide = $(this).data('nombre').toLowerCase().includes(texto);
                        $(this).toggleClass('d-none', ! coincide);
                        if (coincide) visibles++;
                    });

                    $(this).toggle(visibles > 0);
                });
            });

            // Las claves llevan el tipo («producto:7» / «promocion:7»): los id de
            // sucursal_producto y de promocion_sucursal son secuencias
            // independientes y sus valores se solapan.
            const carrito = new Map();

            // Líneas que el pedido ya tenía. Se pintan en el carrito sin
            // controles (no se pueden quitar ni cambiar la cantidad) y nunca se
            // envían al servidor: solo viajan las líneas nuevas, que es lo único
            // que cambia al adicionar.
            const carritoBase = new Map();

            // «crear» = pedido nuevo; «editar» = pendiente con items modificables;
            // «adicionar» = ejecutado: solo se agregan líneas nuevas.
            let modoCarrito = 'crear';

            function precioFinal(item) {
                return item.precio - item.descuento;
            }

            // HTML del precio de una línea: si tiene descuento se tacha el
            // precio original y se muestra el unitario ya descontado.
            function precioUnitario(item, unitario) {
                return item.descuento > 0
                    ? `<span class="text-decoration-line-through text-muted me-1">Bs. ${item.precio.toFixed(2)}</span>`
                      + `<span class="text-danger">Bs. ${unitario.toFixed(2)} c/u</span>`
                    : `Bs. ${item.precio.toFixed(2)} c/u`;
            }

            function aplicarTipoPedido() {
                const esMesa = $('input[name="tipopedido"]:checked').val() === 'MESA';

                $('#bloque-mesa').toggleClass('d-none', ! esMesa);
                $('#pedido-mesa').prop('required', esMesa);
            }

            function pintarCarrito() {
                const $lista = $('#carrito-lista');
                $lista.empty();

                let total = 0;
                let unidades = 0;

                // Pinta una línea ya guardada del pedido (solo en modo
                // adicionar): aparece completa pero sin controles, ni cantidad
                // ni quitar, y no se envía al guardar.
                function pintarLineaBase(item) {
                    const unitario = precioFinal(item);

                    total += unitario * item.cantidad;
                    unidades += item.cantidad;

                    $lista.append(`
                        <li class="list-group-item d-flex justify-content-between align-items-center gap-2">
                            <div class="flex-grow-1">
                                <div class="fw-semibold small">${item.nombre}</div>
                                <div class="small">${precioUnitario(item, unitario)}</div>
                            </div>
                            <span class="badge text-bg-secondary">Ya estaba</span>
                        </li>`);
                }

                carritoBase.forEach(pintarLineaBase);

                carrito.forEach((item, id) => {
                    const unitario = precioFinal(item);

                    total += unitario * item.cantidad;
                    unidades += item.cantidad;

                    $lista.append(`
                        <li class="list-group-item d-flex justify-content-between align-items-center gap-2">
                            <div class="flex-grow-1">
                                <div class="fw-semibold small">${item.nombre}</div>
                                <div class="small">${precioUnitario(item, unitario)}</div>
                            </div>
                            <div class="input-group input-group-sm" style="width: 118px;">
                                <button type="button" class="btn btn-outline-secondary quitar-carrito" data-id="${id}">-</button>
                                <input type="text" class="form-control text-center" value="${item.cantidad}" readonly>
                                <button type="button" class="btn btn-outline-secondary sumar-carrito" data-id="${id}">+</button>
                            </div>
                        </li>`);
                });

                const vacio = carritoBase.size + carrito.size === 0;
                $('#carrito-vacio').toggleClass('d-none', ! vacio);
                $lista.toggleClass('d-none', vacio);
                $('#carrito-total').toggleClass('d-none', vacio);
                // El envío solo sirve si hay algo nuevo: en modo adicionar las
                // líneas existentes ya están guardadas.
                $('#btn-guardar-pedido').prop('disabled', carrito.size === 0);
                $('#carrito-items').text(unidades);
                $('#carrito-total-valor').text(`Bs. ${total.toFixed(2)}`);
            }

            $(document).on('click', '.agregar-carrito', function () {
                const id = `producto:${$(this).data('id')}`;
                const existente = carrito.get(id);

                carrito.set(id, {
                    nombre: $(this).data('nombre'),
                    precio: parseFloat($(this).data('precio')),
                    descuento: 0,
                    cantidad: existente ? existente.cantidad + 1 : 1,
                });

                pintarCarrito();
            });

            $(document).on('click', '.agregar-promo', function () {
                const id = `promocion:${$(this).data('id')}`;
                const existente = carrito.get(id);

                carrito.set(id, {
                    nombre: `🔥 ${$(this).data('nombre')}`,
                    precio: parseFloat($(this).data('precio')),
                    descuento: parseFloat($(this).data('descuento')) || 0,
                    cantidad: existente ? existente.cantidad + 1 : 1,
                });

                pintarCarrito();
            });

            // ── Cobro en modal ─────────────────────────────────────────────────
            // El cobro no va en la fila de la tabla sino en una modal: así en
            // efectivo se ve el total, se escribe lo recibido y se ve el cambio
            // antes de confirmar. Si falta el importe, no se cobra. Con cuentas
            // separadas además se elige si se paga el total o una sola cuenta.
            let totalCobro = 0;
            let totalPendienteCobro = 0;

            function esEfectivoCobro() {
                return ($('#cobro-metodo option:selected').data('gateway') || '') === 'EFECTIVO';
            }

            function pintarCobro() {
                const enEfectivo = esEfectivoCobro();
                const recibido = parseFloat($('#cobro-recibido').val());
                const cambio = enEfectivo && ! Number.isNaN(recibido) ? recibido - totalCobro : 0;
                const falta = enEfectivo && (Number.isNaN(recibido) || cambio < 0);

                $('#bloque-recibido').toggleClass('d-none', ! enEfectivo);
                // El importe solo es obligatorio en efectivo; si no, el navegador
                // no dejaría enviar el formulario estando oculto.
                $('#cobro-recibido').prop('required', enEfectivo).toggleClass('is-invalid', falta);
                $('#cobro-cambio').text(`Cambio: Bs. ${(cambio > 0 ? cambio : 0).toFixed(2)}`);
                $('#cobro-confirmar').prop('disabled', falta);

                // Bloque de tarjeta (WizPay): se muestra cuando el gateway es TARJETA.
                const enTarjeta = ($('#cobro-metodo option:selected').data('gateway') || '') === 'TARJETA';
                $('#bloque-tarjeta').toggleClass('d-none', ! enTarjeta);
                if (! enTarjeta) {
                    $('#wizpay-reference').val('');
                    $('#wizpay-estado').text('');
                }
            }

            $(document).on('click', '.cobrar-pedido', function () {
                totalPendienteCobro = parseFloat($(this).data('total')) || 0;
                totalCobro = totalPendienteCobro;

                const cuentas = JSON.parse($(this).attr('data-cuentas') || '[]');
                const pendientes = cuentas.filter((cuenta) => ! cuenta.pagada);

                // La lista ofrece el total y cada cuenta todavía sin pagar:
                // el cliente elige pagar todo o solo lo suyo.
                const $cuenta = $('#cobro-cuenta').empty().append(
                    `<option value="" data-monto="${totalPendienteCobro.toFixed(2)}">`
                    + `Total pendiente — Bs. ${totalPendienteCobro.toFixed(2)}</option>`);

                pendientes.forEach((cuenta) => {
                    $cuenta.append(
                        `<option value="${cuenta.n}" data-monto="${parseFloat(cuenta.total).toFixed(2)}">`
                        + `Cuenta ${cuenta.n} — Bs. ${parseFloat(cuenta.total).toFixed(2)}</option>`);
                });

                // Con una sola cuenta pendiente no hay nada que elegir: esa
                // cuenta es exactamente el total, así que el bloque queda oculto
                // (y el campo vacío le dice al servidor que cobre el total).
                $('#bloque-cuenta').toggleClass('d-none', pendientes.length < 2);

                $('#cobro-titulo').text(`Cobrar pedido #${$(this).data('nropedido')}`);
                $('#cobro-total').text(`Bs. ${totalCobro.toFixed(2)}`);
                $('#form-cobro').attr('action', $(this).data('accion'));

                // Se limpia el importe para que el cambio nunca arrastre el del
                // pedido anterior.
                $('#cobro-recibido').val('').removeClass('is-invalid');
                $('#cobro-metodo').prop('selectedIndex', 0);

                pintarCobro();
                bootstrap.Modal.getOrCreateInstance('#modal-cobro').show();
            });

            $('#cobro-metodo').on('change', pintarCobro);
            $('#cobro-recibido').on('input', pintarCobro);

            // ── WizPay: cobro con tarjeta ──────────────────────────────────────
            // Cuando el método es TARJETA se muestran los campos de la tarjeta
            // y el pago se procesa contra WizPay antes de enviar el formulario.

            // Máscaras simples para los campos de tarjeta.
            $('#card-number').on('input', function () {
                let v = this.value.replace(/\D/g, '').slice(0, 16);
                this.value = v.replace(/(\d{4})(?=\d)/g, '$1 ');
            });

            $('#card-expiry').on('input', function () {
                let v = this.value.replace(/\D/g, '').slice(0, 6);
                if (v.length > 2) v = v.slice(0, 2) + '/' + v.slice(2);
                this.value = v;
            });

            $('#card-cvv').on('input', function () {
                this.value = this.value.replace(/\D/g, '').slice(0, 4);
            });

            function detectarMarca(numero) {
                const n = numero.replace(/\D/g, '');
                if (/^4/.test(n)) return '001';       // Visa
                if (/^5[1-5]/.test(n) || /^2[2-7]/.test(n)) return '002'; // Mastercard
                if (/^3[47]/.test(n)) return '003';   // Amex
                return null;
            }

            function estadoWizPay(texto, clase) {
                $('#wizpay-estado').attr('class', 'mt-2 small ' + (clase || 'text-muted')).text(texto);
            }

            function esperar(ms) {
                return new Promise((resolve) => setTimeout(resolve, ms));
            }

            // Ejecuta la Device Data Collection de 3DS en un iframe oculto.
            // Devuelve una promesa que resuelve cuando el DDC termina (o tras 11s).
            function ejecutarDDC(url, token) {
                return new Promise((resolve) => {
                    const contenedor = document.getElementById('wizpay-ddc');
                    contenedor.classList.remove('d-none');
                    contenedor.innerHTML = '';

                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = url;
                    form.target = 'wizpay-ddc-frame';
                    form.style.display = 'none';

                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'JWT';
                    input.value = token;
                    form.appendChild(input);

                    const iframe = document.createElement('iframe');
                    iframe.name = 'wizpay-ddc-frame';
                    iframe.style.display = 'none';
                    iframe.onload = () => {
                        // El DDC puede no tener contenido visible; resolvemos
                        // tras un breve margen para que envíe su postMessage.
                        setTimeout(resolve, 2000);
                    };

                    // postMessage desde el DDC = señal principal.
                    const onMessage = (event) => {
                        // El origin es el host de device_data_collection_url.
                        const ddcOrigin = new URL(url).origin;
                        if (event.origin === ddcOrigin) {
                            window.removeEventListener('message', onMessage);
                            clearTimeout(respaldo);
                            resolve();
                        }
                    };
                    window.addEventListener('message', onMessage);

                    // Respaldo: 11 segundos máximo.
                    const respaldo = setTimeout(() => {
                        window.removeEventListener('message', onMessage);
                        resolve();
                    }, 11000);

                    contenedor.appendChild(iframe);
                    contenedor.appendChild(form);
                    form.submit();
                });
            }

            // Procesa el pago con tarjeta en WizPay.
            // Devuelve true si el pago fue APROBADO (y la referencia está lista).
            async function procesarPagoWizPay() {
                const numero = $('#card-number').val().replace(/\s/g, '');
                const expiry = $('#card-expiry').val();
                const cvv = $('#card-cvv').val();
                const nombre = $('#card-name').val().trim();
                const email = $('#card-email').val().trim();
                const telefono = $('#card-phone').val().trim();

                if (! numero || ! expiry || ! cvv || ! nombre) {
                    estadoWizPay('Completa todos los datos de la tarjeta.', 'text-danger');
                    return false;
                }

                const marca = detectarMarca(numero);
                if (! marca) {
                    estadoWizPay('Tarjeta no soportada. Usa Visa, Mastercard o Amex.', 'text-danger');
                    return false;
                }

                if (! /^\d{2}\/\d{4}$/.test(expiry)) {
                    estadoWizPay('Fecha de expiración inválida (usa MM/AAAA).', 'text-danger');
                    return false;
                }

                const card = { type: marca, number: numero, expiry, cvv };

                // GUID para el fingerprint del dispositivo.
                const sessionId = crypto.randomUUID().replace(/-/g, '');

                try {
                    // 1. Crear transacción.
                    estadoWizPay('Iniciando transacción…', 'text-muted');
                    const initResp = await fetch('{{ route('dashboard.generar-pedidos.wizpay.init') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}',
                        },
                        body: JSON.stringify({
                            pedido_id: parseInt($('#form-cobro').attr('action').match(/(\d+)(?=\/cobrar)/)?.[1] || '0', 10),
                            amount: totalCobro,
                        }),
                    });
                    const init = await initResp.json();

                    if (! initResp.ok || ! init.reference) {
                        estadoWizPay(init.mensaje || 'No se pudo iniciar la transacción.', 'text-danger');
                        return false;
                    }

                    // 2. Authentication Setup 3DS.
                    estadoWizPay('Preparando autenticación 3DS…', 'text-muted');
                    const authResp = await fetch('{{ route('dashboard.generar-pedidos.wizpay.auth-setup') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}',
                        },
                        body: JSON.stringify({ reference: init.reference, card, fingerprint_session_id: sessionId }),
                    });
                    const auth = await authResp.json();

                    if (! authResp.ok) {
                        estadoWizPay(auth.mensaje || 'No se pudo preparar 3DS.', 'text-danger');
                        return false;
                    }

                    // 3. Device Data Collection en el navegador.
                    estadoWizPay('Verificando datos del dispositivo…', 'text-muted');
                    await ejecutarDDC(auth.device_data_collection_url, auth.access_token);

                    // 4. Procesar el pago.
                    estadoWizPay('Procesando pago…', 'text-muted');
                    const procResp = await fetch('{{ route('dashboard.generar-pedidos.wizpay.process') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}',
                        },
                        body: JSON.stringify({
                            reference: init.reference,
                            card,
                            billing: {
                                address1: 'Av. Principal',
                                city: 'La Paz',
                                state: 'LP',
                                country_code: 'BO',
                                postal_code: '0000',
                            },
                            personal: {
                                full_name: nombre,
                                email: email || 'cobro@pos.local',
                                phone: telefono || '70000000',
                            },
                            cs_reference_id: auth.reference_id,
                            fingerprint_session_id: sessionId,
                        }),
                    });
                    const result = await procResp.json();

                    if (result.status === 'APROBADO') {
                        estadoWizPay('✅ Pago aprobado.', 'text-success');
                        $('#wizpay-reference').val(init.reference);
                        return true;
                    }

                    if (result.status === 'PENDING_3DS' && result.authentication?.step_up_url) {
                        estadoWizPay('El banco requiere verificación 3DS…', 'text-warning');
                        return await abrirStepUp(result, init.reference);
                    }

                    estadoWizPay('Pago rechazado: ' + (result.msg || result.status || 'error'), 'text-danger');
                    return false;
                } catch (e) {
                    estadoWizPay('Error de conexión: ' + e.message, 'text-danger');
                    return false;
                }
            }

            // Abre el popup del Step-Up 3DS y espera la confirmación.
            function abrirStepUp(result, reference) {
                return new Promise((resolve) => {
                    const url = result.authentication.step_up_url;
                    const token = result.authentication.access_token;

                    // Popup con form POST del JWT.
                    const popup = window.open('', 'wizpay-3ds', 'width=450,height=650');
                    if (! popup) {
                        estadoWizPay('Permite las ventanas emergentes para completar 3DS.', 'text-danger');
                        resolve(false);
                        return;
                    }

                    const doc = popup.document;
                    doc.open();
                    doc.write(`
                        <html><body style="font-family:sans-serif;display:flex;align-items:center;justify-content:center;height:100vh;margin:0;background:#f5f5f5;">
                            <p>Verificando con tu banco…</p>
                            <form method="POST" action="${url}">
                                <input type="hidden" name="JWT" value="${token}">
                                <noscript><button type="submit">Continuar</button></noscript>
                            </form>
                            <script>document.forms[0].submit();<\/script>
                        </body></html>
                    `);
                    doc.close();

                    // WizPay redirige a return_url/payment/{success,failed}?reference=...
                    // Escuchamos la redirección para confirmar.
                    const intervalo = setInterval(async () => {
                        try {
                            if (popup.closed) {
                                clearInterval(intervalo);
                                // Popup cerrado: consultamos el estado final.
                                const resp = await fetch('{{ route('dashboard.generar-pedidos.wizpay.status', 'REF') }}'.replace('REF', encodeURIComponent(reference)));
                                const status = await resp.json();
                                if (status.status === 'APROBADO') {
                                    estadoWizPay('✅ Pago aprobado con 3DS.', 'text-success');
                                    $('#wizpay-reference').val(reference);
                                    resolve(true);
                                } else {
                                    estadoWizPay('3DS no completado (estado: ' + status.status + ').', 'text-danger');
                                    resolve(false);
                                }
                            }
                        } catch {
                            // Ignorar errores momentáneos de red.
                        }
                    }, 1500);

                    // Timeout de seguridad: 90 segundos.
                    setTimeout(() => {
                        clearInterval(intervalo);
                        if (! popup.closed) popup.close();
                        resolve(false);
                    }, 90000);
                });
            }

            // Intercepta el submit del formulario de cobro para procesar tarjeta.
            function esTarjetaCobro() {
                return ($('#cobro-metodo option:selected').data('gateway') || '') === 'TARJETA';
            }

            $('#form-cobro').on('submit', async function (evento) {
                if (! esTarjetaCobro()) return; // Efectivo/QR: submit normal.

                // Si ya tenemos referencia (pago aprobado), dejamos pasar.
                if ($('#wizpay-reference').val()) return;

                evento.preventDefault();
                evento.stopPropagation();

                const btn = $('#cobro-confirmar');
                const textoOriginal = btn.text();
                btn.prop('disabled', true).text('Procesando…');

                const aprobado = await procesarPagoWizPay();

                btn.prop('disabled', false).text(textoOriginal);

                if (aprobado) {
                    // Reenviamos el formulario con la referencia ya cargada.
                    this.submit();
                }
            });

            // Al cambiar de cuenta cambia el importe, el total mostrado y se
            // reinicia lo recibido: el cambio se calcula contra lo nuevo.
            $('#cobro-cuenta').on('change', function () {
                totalCobro = parseFloat($('#cobro-cuenta option:selected').data('monto'))
                    || totalPendienteCobro;

                $('#cobro-total').text(`Bs. ${totalCobro.toFixed(2)}`);
                $('#cobro-recibido').val('').removeClass('is-invalid');
                pintarCobro();
            });

            $(document).on('click', '.sumar-carrito', function () {
                const item = carrito.get($(this).data('id'));
                item.cantidad++;
                pintarCarrito();
            });

            $(document).on('click', '.quitar-carrito', function () {
                const id = $(this).data('id');
                const item = carrito.get(id);

                item.cantidad--;
                if (item.cantidad <= 0) {
                    carrito.delete(id);
                }

                pintarCarrito();
            });

            $('input[name="tipopedido"]').on('change', aplicarTipoPedido);

            $('#form-pedido').on('submit', function (evento) {
                // En modo adicionar solo viajan las líneas nuevas de `carrito`:
                // las que el pedido ya tenía viven en `carritoBase` y no se
                // reenvían.
                if (carrito.size === 0) {
                    evento.preventDefault();
                    return;
                }

                const esMesa = $('input[name="tipopedido"]:checked').val() === 'MESA';

                // Solo la mesa del pedido MESA viaja al servidor. En modo
                // adicionar el tipo está bloqueado y viaja oculto, así que no hay
                // nada que elegir ni validar aquí.
                if (modoCarrito !== 'adicionar') {
                    if (! esMesa) {
                        $('#pedido-mesa').val('');
                    }

                    if (esMesa && ! $('#pedido-mesa').val()) {
                        evento.preventDefault();
                        $('#pedido-mesa').addClass('is-invalid').trigger('focus');
                        return;
                    }
                }

                $('#items-pedido').empty();

                let posicion = 0;
                carrito.forEach((item, clave) => {
                    const [tipo, identificador] = clave.split(':');
                    const campo = tipo === 'promocion' ? 'idpromocionsucursal' : 'idproductosucursal';

                    $('#items-pedido')
                        .append(`<input type="hidden" name="items[${posicion}][${campo}]" value="${identificador}">`)
                        .append(`<input type="hidden" name="items[${posicion}][cantidad]" value="${item.cantidad}">`);
                    posicion++;
                });

                $('#btn-guardar-pedido').prop('disabled', true);
                $('#btn-guardar-etiqueta').text(modoCarrito === 'adicionar'
                    ? 'Adicionando…'
                    : (modoCarrito === 'editar' ? 'Guardando…' : 'Registrando…'));
            });

            // Si el navegador devolvió el formulario con errores, se recuperan
            // los productos enviados desde los datos del catálogo. Solo se
            // restauran las líneas editables: las que ya estaban guardadas se
            // repiten desde el servidor al volver a cargar el modo.
            @php($itemsRestaurados = is_array(old('items')) ? array_values(old('items')) : [])

            function restaurarItemsCarrito() {
                @json($itemsRestaurados).forEach((item) => {
                    const esPromocion = Boolean(item.idpromocionsucursal);
                    const identificador = esPromocion ? item.idpromocionsucursal : item.idproductosucursal;
                    const $boton = $(`${esPromocion ? '.agregar-promo' : '.agregar-carrito'}[data-id="${identificador}"]`);

                    if ($boton.length) {
                        carrito.set(`${esPromocion ? 'promocion' : 'producto'}:${identificador}`, {
                            nombre: $boton.data('nombre'),
                            precio: parseFloat($boton.data('precio')),
                            descuento: esPromocion ? (parseFloat($boton.data('descuento')) || 0) : 0,
                            cantidad: Number(item.cantidad) || 1,
                        });
                    }
                });
            }

            // ── Edición de pedidos pendientes ─────────────────────────────────
            // Al pulsar «Editar» el pedido se carga en el mismo carrito y el
            // formulario pasa a apuntar a su actualización. Solo los pedidos
            // pendientes llegan aquí: lo cobrado ya es una cuenta aparte.
            function reactivarControlesPedido() {
                $('input[name="tipopedido"]').prop('disabled', false);
                $('#pedido-mesa').prop('disabled', false);
                $('#observaciones-pedido').prop('disabled', false);
                $('#adicion-campos').empty();
            }

            function activarModoEdicion(datos) {
                modoCarrito = 'editar';

                $('#form-pedido').attr('action', datos.accion);
                $('#edicion-metodo').prop('disabled', false);
                $('#edicion-idpedido').val(datos.id);
                $('#edicion-modo').val('editar');
                reactivarControlesPedido();

                $('#aviso-edicion').removeClass('d-none');
                $('#edicion-etiqueta').text('Editando');
                $('#edicion-nropedido').text(`#${datos.nropedido}`);
                $('#btn-guardar-etiqueta').text(`Guardar cambios del pedido #${datos.nropedido}`);

                // Editar un pedido pendiente toca todas sus líneas, así que no
                // hay base de solo lectura: todo entra al carrito editable.
                carritoBase.clear();
                carrito.clear();
                datos.items.forEach((item) => {
                    carrito.set(`${item.tipo}:${item.id}`, {
                        nombre: item.nombre,
                        precio: parseFloat(item.precio),
                        descuento: parseFloat(item.descuento) || 0,
                        cantidad: Number(item.cantidad) || 1,
                    });
                });

                $('#observaciones-pedido').val(datos.observaciones || '');
                $(`input[name="tipopedido"][value="${datos.tipopedido}"]`).prop('checked', true);
                $('#pedido-mesa').val(datos.idmesa ? String(datos.idmesa) : '');
                aplicarTipoPedido();
                pintarCarrito();

                // El carrito no está en la página si no hay caja abierta.
                $('#form-pedido')[0]?.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }

            // ── Adicionar items a un pedido ejecutado ─────────────────────────
            // Mismo carrito que la edición pero restringido: el tipo de pedido y
            // la mesa quedan bloqueados, las líneas existentes se cargan en solo
            // lectura (no se pueden quitar) y el formulario guarda en la ruta de
            // adición, que es POST y no PUT.
            function activarModoAdicion(datos) {
                modoCarrito = 'adicionar';

                $('#form-pedido').attr('action', datos.accion);
                $('#edicion-metodo').prop('disabled', true);
                $('#edicion-idpedido').val(datos.id);
                $('#edicion-modo').val('adicionar');
                reactivarControlesPedido();

                // Como los controles quedan deshabilitados (y lo deshabilitado
                // no se envía), el tipo y la mesa viajan en campos ocultos para
                // que la validación del servidor siga recibiéndolos.
                $('#adicion-campos').html(
                    `<input type="hidden" name="tipopedido" value="${datos.tipopedido}">`
                    + (datos.idmesa ? `<input type="hidden" name="idmesa" value="${datos.idmesa}">` : '')
                );
                // Se acota a los dos radios: si no, el selector también
                // alcanzaría el campo oculto recién inyectado con el mismo
                // nombre y lo deshabilitaría (y lo deshabilitado no se envía).
                $('#tipo-pedido-mesa, #tipo-pedido-llevar').prop('disabled', true);
                $('#pedido-mesa').prop('disabled', true);
                $('#observaciones-pedido').prop('disabled', true);

                $('#aviso-edicion').removeClass('d-none');
                $('#edicion-etiqueta').text('Adicionando');
                $('#edicion-nropedido').text(`#${datos.nropedido}`);
                $('#btn-guardar-etiqueta').text(`Adicionar items al pedido #${datos.nropedido}`);

                // Las líneas que el pedido ya tenía quedan en la base (solo
                // lectura, no se envían) y el carrito arranca vacío para las
                // nuevas. Así un producto repetido no pisa su línea original.
                carritoBase.clear();
                carrito.clear();
                datos.items.forEach((item) => {
                    const linea = {
                        nombre: item.nombre,
                        precio: parseFloat(item.precio),
                        descuento: parseFloat(item.descuento) || 0,
                        cantidad: Number(item.cantidad) || 1,
                    };

                    const destino = item.soloLectura ? carritoBase : carrito;
                    destino.set(`${item.tipo}:${item.id}`, linea);
                });

                $('#observaciones-pedido').val(datos.observaciones || '');
                $(`input[name="tipopedido"][value="${datos.tipopedido}"]`).prop('checked', true);
                $('#pedido-mesa').val(datos.idmesa ? String(datos.idmesa) : '');
                aplicarTipoPedido();
                pintarCarrito();

                $('#form-pedido')[0]?.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }

            function cancelarModoEdicion() {
                modoCarrito = 'crear';

                $('#form-pedido').attr('action', $('#form-pedido').data('accion-crear'));
                $('#edicion-metodo').prop('disabled', true);
                $('#edicion-idpedido').val('');
                $('#edicion-modo').val('');
                reactivarControlesPedido();

                $('#aviso-edicion').addClass('d-none');
                $('#edicion-etiqueta').text('Editando');
                $('#btn-guardar-etiqueta').text('Registrar pedido');
                $('#observaciones-pedido').val('');

                carritoBase.clear();
                carrito.clear();
                $('#tipo-pedido-mesa').prop('checked', true);
                $('#pedido-mesa').val('');
                aplicarTipoPedido();
                pintarCarrito();
            }

            function cargarPedido(url, alCargar) {
                fetch(url, { headers: { 'Accept': 'application/json' } })
                    .then((respuesta) => respuesta.json().then((datos) => {
                        if (! respuesta.ok) {
                            throw new Error(datos.mensaje || 'No se pudo cargar el pedido.');
                        }

                        return datos;
                    }))
                    .then(alCargar)
                    .catch((error) => window.alert(error.message));
            }

            $(document).on('click', '.editar-pedido', function () {
                cargarPedido($(this).data('accion'), activarModoEdicion);
            });

            $(document).on('click', '.adicionar-pedido', function () {
                cargarPedido($(this).data('accion'), activarModoAdicion);
            });

            // ── Ver detalle (solo lectura, los cuatro estados) ────────────────
            const etiquetasEstado = @json(collect($estadosPedido)->map(fn (array $c) => $c['etiqueta']));

            function pintarDetalle(datos) {
                $('#detalle-nropedido').text(`#${datos.nropedido}`);
                $('#detalle-estado').text(etiquetasEstado[datos.estado] || datos.estado);
                $('#detalle-tipo').text(datos.tipo);
                $('#detalle-hora').text(datos.hora || '—');
                $('#detalle-empleado').text(datos.empleado || '—');

                $('#detalle-observaciones-bloque').toggleClass('d-none', ! datos.observaciones);
                $('#detalle-observaciones').text(datos.observaciones || '');

                $('#detalle-pago-bloque').toggleClass('d-none', ! datos.pago);
                $('#detalle-pago').text(datos.pago || '');

                // Cuentas separadas: una por cada «Adicionar». Con una sola no
                // hay nada que distinguir, así que el bloque no aparece.
                const cuentas = datos.cuentas || [];
                const $cuentas = $('#detalle-cuentas').empty();

                $('#detalle-cuentas-bloque').toggleClass('d-none', cuentas.length < 2);

                cuentas.forEach((cuenta) => {
                    const pagada = Boolean(cuenta.pagada);

                    $cuentas.append(
                        `<span class="badge text-bg-${pagada ? 'success' : 'secondary'}">`
                        + `Cuenta ${cuenta.n}: Bs. ${parseFloat(cuenta.total).toFixed(2)}`
                        + ` · ${pagada ? 'Cobrada' : 'Pendiente'}</span>`);
                });

                if (parseFloat(datos.saldo) > 0) {
                    $cuentas.append(
                        `<span class="badge text-bg-warning">Por cobrar: Bs. ${parseFloat(datos.saldo).toFixed(2)}</span>`);
                }

                const $items = $('#detalle-items').empty();

                datos.items.forEach((item) => {
                    const descuento = (parseFloat(item.descuento) || 0) * item.cantidad;
                    const subtotal = ((parseFloat(item.precio) || 0) - (parseFloat(item.descuento) || 0)) * item.cantidad;
                    // Con varias cuentas cada línea lleva la suya para saber
                    // qué pagó quién.
                    const etiquetaCuenta = cuentas.length > 1 ? `C${item.cuenta} · ` : '';

                    $items.append(`
                        <tr>
                            <td>${etiquetaCuenta}${item.nombre}</td>
                            <td class="text-end">Bs. ${parseFloat(item.precio).toFixed(2)}</td>
                            <td class="text-end">${item.cantidad}</td>
                            <td class="text-end">Bs. ${descuento.toFixed(2)}</td>
                            <td class="text-end">Bs. ${subtotal.toFixed(2)}</td>
                        </tr>`);
                });

                $('#detalle-subtotal').text(`Bs. ${parseFloat(datos.subtotal).toFixed(2)}`);
                $('#detalle-descuento').text(`Bs. ${parseFloat(datos.descuento).toFixed(2)}`);
                $('#detalle-total').text(`Bs. ${parseFloat(datos.total).toFixed(2)}`);

                bootstrap.Modal.getOrCreateInstance('#modal-detalle').show();
            }

            $(document).on('click', '.ver-detalle', function () {
                cargarPedido($(this).data('accion'), pintarDetalle);
            });

            $(document).on('click', '#cancelar-edicion', cancelarModoEdicion);

            // Si el navegador devolvió el formulario con errores se recupera el
            // modo en el que se estaba trabajando, para que el siguiente envío
            // siga apuntando al MISMO pedido. Sin esto, un fallo de validación
            // devolvería el carrito a «crear» y se registraría un pedido nuevo.
            @if (old('idpedido'))
                @if (old('modocarrito') === 'adicionar')
                    // En modo adicionar se vuelve a consultar el pedido: así se
                    // repone la base de solo lectura con sus líneas actuales y
                    // las enviadas vuelven al carrito editable como nuevas.
                    cargarPedido("{{ route('dashboard.generar-pedidos.adicionar', old('idpedido')) }}", (datos) => {
                        activarModoAdicion(datos);
                        restaurarItemsCarrito();
                        pintarCarrito();
                    });
                @else
                    modoCarrito = 'editar';
                    $('#form-pedido').attr('action', "{{ route('dashboard.generar-pedidos.update', old('idpedido')) }}");
                    $('#edicion-metodo').prop('disabled', false);
                    $('#aviso-edicion').removeClass('d-none');
                    $('#edicion-nropedido').text('#{{ old('idpedido') }}');
                    $('#btn-guardar-etiqueta').text('Guardar cambios del pedido #{{ old('idpedido') }}');
                    restaurarItemsCarrito();
                    pintarCarrito();
                @endif
            @endif

            aplicarTipoPedido();
            pintarCarrito();
        });
    </script>
@endpush
