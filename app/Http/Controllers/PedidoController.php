<?php

namespace App\Http\Controllers;

use App\Models\AperturaCaja;
use App\Models\Categoria;
use App\Models\DetallePedido;
use App\Models\Empleado;
use App\Models\Mesa;
use App\Models\MetodoPago;
use App\Models\Pago;
use App\Models\Pedido;
use App\Models\PromocionSucursal;
use App\Models\Sucursal;
use App\Models\SucursalProducto;
use App\Models\Venta;
use App\Services\WizPayService;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PedidoController extends Controller
{
    /**
     * Valores de pedido.tipopedido que reconoce la base de datos.
     */
    public const TIPOS_PEDIDO = ['MESA', 'PARA_LLEVAR'];

    /**
     * Estados del pedido en el orden en que se listan. Cada uno trae su
     * etiqueta, icono, color de Bootstrap y el nombre del parámetro de
     * paginación propio (para que las tres pestañas no se pisen).
     *
     * @var array<string, array{pagina: string, etiqueta: string, icono: string, color: string}>
     */
    public const ESTADOS_PEDIDO = [
        'PENDIENTE' => ['pagina' => 'pendientes', 'etiqueta' => 'Pendientes', 'icono' => '⏳', 'color' => 'warning'],
        'EJECUTADO' => ['pagina' => 'ejecutados', 'etiqueta' => 'Ejecutados', 'icono' => '✅', 'color' => 'success'],
        'COBRADO' => ['pagina' => 'cobrados', 'etiqueta' => 'Cobrados', 'icono' => '💰', 'color' => 'info'],
        'ANULADO' => ['pagina' => 'anulados', 'etiqueta' => 'Anulados', 'icono' => '❌', 'color' => 'secondary'],
    ];

    /**
     * Permisos que habilitan la ventana de caja (para enlazar la apertura).
     */
    private const PERMISOS_VENTANA_CAJA = ['410', '412', '420', '422', '430', '436', '437'];

    /**
     * Ventana de atención (POS): sucursal, estado de la apertura de caja,
     * categorías con productos, promociones, mesas, métodos de pago y los
     * pedidos de hoy divididos por estado.
     */
    public function index(Request $request): View
    {
        $empleado = auth()->user()->empleado->load('rol');
        $rol = $empleado->rol;

        $sucursales = $this->sucursalesPermitidas($empleado);

        $sucursalActual = $sucursales->firstWhere('id', $request->integer('idsucursal'))
            ?? $sucursales->first();

        abort_unless($sucursalActual, 403, 'No tienes una sucursal asignada para atender pedidos.');

        $aperturaCaja = AperturaCaja::with(['caja', 'empleado'])
            ->where('estado', 'ABIERTA')
            ->whereHas('caja', fn ($query) => $query->where('idsucursal', $sucursalActual->id))
            ->latest('fechaapertura')
            ->first();

        $catalogo = $this->catalogoSucursal($sucursalActual->id);

        $promociones = $this->promocionesVigentes($sucursalActual->id);

        // Mesas activas de la sucursal, ordenadas por número. La asignación al
        // pedido se hace desde el carrito (solo cuando el tipo es MESA).
        $mesas = Mesa::where('idsucursal', $sucursalActual->id)
            ->where('estado', true)
            ->orderBy('numeromesa')
            ->get();

        $metodosPago = MetodoPago::where('activo', true)
            ->orderBy('nombre')
            ->get();

        $pedidosBase = Pedido::where('idsucursal', $sucursalActual->id)
            ->whereDate('fecha', today());

        $conteoPedidos = (clone $pedidosBase)
            ->selectRaw('estadopedido, COUNT(*) AS totalpedidos')
            ->groupBy('estadopedido')
            ->pluck('totalpedidos', 'estadopedido');

        $pedidosPorEstado = [];

        foreach (self::ESTADOS_PEDIDO as $estado => $configuracionEstado) {
            $pedidosPorEstado[$estado] = (clone $pedidosBase)
                ->with(['mesa', 'empleado', 'ventas.pagos.metodoPago', 'detalles'])
                ->where('estadopedido', $estado)
                ->orderByDesc('fecha')
                ->paginate(10, ['*'], $configuracionEstado['pagina'])
                ->withQueryString();
        }

        $permisos = $this->codigosPermisoActuales($empleado);

        return view('admin.pedidos.index', compact(
            'empleado',
            'sucursales',
            'sucursalActual',
            'aperturaCaja',
            'catalogo',
            'promociones',
            'mesas',
            'metodosPago',
            'pedidosPorEstado',
            'conteoPedidos',
        ))->with([
            'claverol' => $rol->claverol,
            'estadosPedido' => self::ESTADOS_PEDIDO,
            'puedeVerCaja' => $permisos->intersect(self::PERMISOS_VENTANA_CAJA)->isNotEmpty(),
            'puedeCrear' => $permisos->contains('500'),
            'puedeEditar' => $permisos->contains('501'),
            'puedeListar' => $permisos->contains('502'),
            'puedeAnular' => $permisos->contains('505'),
            'puedeEditarDetalle' => $permisos->contains('506'),
        ]);
    }

    /**
     * Registra un pedido sin cobrarlo.
     *
     * Exige una caja abierta en la sucursal (si no, no se guarda nada), toma
     * el tipo de pedido (mesa o para llevar) y calcula los totales con los
     * precios de la sucursal: nunca con los del navegador. El pedido nace en
     * PENDIENTE con su total guardado; el cobro lo hace cobrar() y el paso a
     * EJECUTADO, ejecutar().
     */
    public function store(Request $request): RedirectResponse
    {
        $empleado = auth()->user()->empleado->load('rol');

        $sucursales = $this->sucursalesPermitidas($empleado);

        $sucursal = $sucursales->firstWhere('id', $request->integer('idsucursal'))
            ?? $sucursales->first();

        if (! $sucursal) {
            return back()->with('error', 'No tienes una sucursal asignada para registrar pedidos.');
        }

        $aperturaCaja = $this->cajaAbiertaDe($sucursal);

        if (! $aperturaCaja) {
            return back()->with('error', "No hay una caja abierta en {$sucursal->nombre}; abre la caja del turno antes de registrar pedidos.");
        }

        $validated = $request->validate($this->reglasPedido($sucursal));

        $preparacion = $this->prepararDetalles($validated['items'], $sucursal);

        if (is_string($preparacion)) {
            return back()->withInput()->withErrors(['items' => $preparacion]);
        }

        ['detalles' => $detalles, 'subtotal' => $subtotal, 'descuento' => $descuento] = $preparacion;

        // El pedido nace sin cobro: el total se guarda para que la caja lo
        // cobre después desde «Cobrar», que es lo que crea la venta y el pago.
        $total = round($subtotal - $descuento, 2);

        try {
            $pedido = DB::transaction(function () use ($validated, $sucursal, $empleado, $aperturaCaja, $detalles, $subtotal, $descuento, $total): Pedido {
                $nropedido = (int) Pedido::where('idsucursal', $sucursal->id)
                    ->whereDate('fecha', today())
                    ->orderByDesc('nropedido')
                    ->value('nropedido') + 1;

                $pedido = Pedido::create([
                    'nropedido' => $nropedido,
                    'fecha' => now(),
                    'total' => $total,
                    'estadopedido' => 'PENDIENTE',
                    'tipopedido' => $validated['tipopedido'],
                    'idsucursal' => $sucursal->id,
                    'idempleado' => $empleado->id,
                    'idmesa' => $validated['tipopedido'] === 'MESA' ? $validated['idmesa'] : null,
                    'subtotalpedido' => $subtotal,
                    'descuentopedido' => $descuento,
                    'observaciones' => $validated['observaciones'] ?? null,
                    'idaperturacaja' => $aperturaCaja->id,
                ]);

                foreach ($detalles as $detalle) {
                    $pedido->detalles()->create($detalle);
                }

                return $pedido;
            });
        } catch (QueryException) {
            return back()->with('error', 'No se pudo registrar el pedido; revisa los datos e inténtalo de nuevo.');
        }

        $this->registrarAccion($empleado->id, 'CREATE', 'pedido', $pedido->id, '500');

        $tipo = $pedido->tipopedido === 'MESA' ? 'mesa' : 'para llevar';

        return redirect()->route('dashboard.generar-pedidos.index', ['idsucursal' => $sucursal->id])
            ->with('exito', "Pedido #{$pedido->nropedido} ({$tipo}) registrado por Bs. ".number_format($total, 2).'.');
    }

    /**
     * Datos del pedido en JSON para cargarlo en el carrito y editarlo.
     *
     * Solo los pedidos pendientes se pueden editar: los cobrados ya son una
     * cuenta aparte y no se tocan. Devuelve también la acción de guardado para
     * que el navegador no tenga que armar la URL.
     */
    public function editar(Pedido $pedido): JsonResponse
    {
        $empleado = auth()->user()->empleado->load('rol');

        $sucursales = $this->sucursalesPermitidas($empleado);

        if (! $sucursales->firstWhere('id', $pedido->idsucursal)) {
            return response()->json(['mensaje' => 'Ese pedido no pertenece a una sucursal que puedas atender.'], 403);
        }

        if ($pedido->estadopedido !== 'PENDIENTE') {
            return response()->json([
                'mensaje' => $this->mensajeNoEditable($pedido),
            ], 422);
        }

        $detalles = $pedido->detalles()
            ->with(['sucursalProducto.producto', 'promocionSucursal.promocion'])
            ->get();

        return response()->json([
            'id' => $pedido->id,
            'nropedido' => (int) $pedido->nropedido,
            'tipopedido' => $pedido->tipopedido,
            'idmesa' => $pedido->idmesa !== null ? (int) $pedido->idmesa : null,
            'observaciones' => $pedido->observaciones,
            'accion' => route('dashboard.generar-pedidos.update', $pedido),
            'items' => $this->detallesEnJson($detalles, soloLectura: true),
        ]);
    }

    /**
     * Datos del pedido en JSON para «Ver» detalle: cabecera, totales, líneas y
     * el cobro si ya tiene venta. Es de solo lectura y sirve para cualquier
     * estado (pendiente, ejecutado, cobrado o anulado).
     */
    public function detalle(Pedido $pedido): JsonResponse
    {
        $empleado = auth()->user()->empleado->load('rol');

        $sucursales = $this->sucursalesPermitidas($empleado);

        if (! $sucursales->firstWhere('id', $pedido->idsucursal)) {
            return response()->json(['mensaje' => 'Ese pedido no pertenece a una sucursal que puedas atender.'], 403);
        }

        $pedido->load(['mesa', 'empleado', 'ventas.pagos.metodoPago']);

        $detalles = $pedido->detalles()
            ->with(['sucursalProducto.producto', 'promocionSucursal.promocion'])
            ->get();

        $pagos = $pedido->ventas->flatMap(fn (Venta $venta) => $venta->pagos)->sortBy('fechapago');

        return response()->json([
            'nropedido' => (int) $pedido->nropedido,
            'estado' => $pedido->estadopedido,
            'tipo' => $pedido->tipopedido === 'MESA'
                ? 'Mesa '.($pedido->mesa?->numeromesa ?? '—')
                : 'Para llevar',
            'hora' => $pedido->fecha?->format('d/m/Y H:i'),
            'empleado' => trim(($pedido->empleado?->nombre ?? '').' '.($pedido->empleado?->apellido ?? '')),
            'observaciones' => $pedido->observaciones,
            'pago' => $pagos->isEmpty()
                ? null
                : $pagos
                    ->map(fn (Pago $pago): string => $pago->metodoPago?->nombre
                        .' · Bs. '.number_format((float) $pago->montopago, 2))
                    ->implode(' — '),
            'cuentas' => $pedido->cuentas(),
            'saldo' => $pedido->saldoPendiente(),
            'subtotal' => (float) $pedido->subtotalpedido,
            'descuento' => (float) $pedido->descuentopedido,
            'total' => (float) $pedido->total,
            'items' => $this->detallesEnJson($detalles, soloLectura: true),
        ]);
    }

    /**
     * Datos de un pedido EJECUTADO en JSON para «Adicionar»: sus líneas vienen
     * marcadas como solo lectura (no se pueden quitar) y solo se agregan items
     * nuevos. El guardado va a adicionarStore().
     */
    public function adicionar(Pedido $pedido): JsonResponse
    {
        $empleado = auth()->user()->empleado->load('rol');

        $sucursales = $this->sucursalesPermitidas($empleado);

        if (! $sucursales->firstWhere('id', $pedido->idsucursal)) {
            return response()->json(['mensaje' => 'Ese pedido no pertenece a una sucursal que puedas atender.'], 403);
        }

        if ($pedido->estadopedido !== 'EJECUTADO') {
            return response()->json([
                'mensaje' => "El pedido #{$pedido->nropedido} no está ejecutado; solo se le pueden adicionar items a un pedido ejecutado y sin cobrar.",
            ], 422);
        }

        // Con cuentas separadas se sigue adicionando mientras quede saldo por
        // cobrar: lo cobrado no se toca y lo nuevo abre una cuenta nueva.
        if ($pedido->saldoPendiente() <= 0) {
            return response()->json([
                'mensaje' => "El pedido #{$pedido->nropedido} ya está cobrado; no se le pueden adicionar items.",
            ], 422);
        }

        $detalles = $pedido->detalles()
            ->with(['sucursalProducto.producto', 'promocionSucursal.promocion'])
            ->get();

        return response()->json([
            'id' => $pedido->id,
            'nropedido' => (int) $pedido->nropedido,
            'tipopedido' => $pedido->tipopedido,
            'idmesa' => $pedido->idmesa !== null ? (int) $pedido->idmesa : null,
            'observaciones' => $pedido->observaciones,
            'accion' => route('dashboard.generar-pedidos.adicionar.store', $pedido),
            'items' => $this->detallesEnJson($detalles, soloLectura: true),
        ]);
    }

    /**
     * Adiciona items nuevos a un pedido EJECUTADO sin cobrar, sin tocar las
     * líneas existentes ni el tipo de pedido.
     *
     * Recalcula subtotal, descuento y total sumando todas las líneas (las que
     * ya tenía y las nuevas) con los precios de la sucursal del momento.
     */
    public function adicionarStore(Request $request, Pedido $pedido): RedirectResponse
    {
        $empleado = auth()->user()->empleado->load('rol');

        $sucursal = $this->sucursalesPermitidas($empleado)->firstWhere('id', $pedido->idsucursal);

        if (! $sucursal) {
            return back()->with('error', 'Ese pedido no pertenece a una sucursal que puedas atender.');
        }

        if ($pedido->estadopedido !== 'EJECUTADO') {
            return back()->with('error', "El pedido #{$pedido->nropedido} no está ejecutado; solo se le pueden adicionar items a un pedido ejecutado y sin cobrar.");
        }

        if ($pedido->saldoPendiente() <= 0) {
            return back()->with('error', "El pedido #{$pedido->nropedido} ya está cobrado; no se le pueden adicionar items.");
        }

        if (! $this->cajaAbiertaDe($sucursal)) {
            return back()->with('error', "No hay una caja abierta en {$sucursal->nombre}; abre la caja del turno antes de adicionar items.");
        }

        $validated = $request->validate($this->reglasPedido($sucursal));

        $preparacion = $this->prepararDetalles($validated['items'], $sucursal);

        if (is_string($preparacion)) {
            return back()->withInput()->withErrors(['items' => $preparacion]);
        }

        ['detalles' => $detalles] = $preparacion;

        try {
            DB::transaction(function () use ($pedido, $detalles): void {
                // Cada adición abre una cuenta nueva: así las líneas cobradas
                // quedan intactas y lo recién agregado se paga aparte.
                $siguienteCuenta = ((int) $pedido->detalles()->max('nrocuenta')) + 1;

                foreach ($detalles as $detalle) {
                    $pedido->detalles()->create($detalle + ['nrocuenta' => $siguienteCuenta]);
                }

                // Se recalculan los totales del pedido sumando todas sus líneas,
                // porque al adicionar solo crecen: las existentes no se tocan.
                $suma = $pedido->detalles()
                    ->selectRaw('COALESCE(SUM(subtotal), 0) AS subtotal, COALESCE(SUM(descuento), 0) AS descuento, COALESCE(SUM(total), 0) AS total')
                    ->first();

                $pedido->update([
                    'subtotalpedido' => round((float) $suma->subtotal, 2),
                    'descuentopedido' => round((float) $suma->descuento, 2),
                    'total' => round((float) $suma->total, 2),
                ]);
            });
        } catch (QueryException) {
            return back()->with('error', 'No se pudieron adicionar los items; revisa los datos e inténtalo de nuevo.');
        }

        $this->registrarAccion($empleado->id, 'UPDATE', 'pedido', $pedido->id, '501');

        return redirect()->route('dashboard.generar-pedidos.index', ['idsucursal' => $sucursal->id])
            ->with('exito', "Items adicionados al pedido #{$pedido->nropedido}; su total ahora es Bs. ".number_format((float) $pedido->fresh()->total, 2).'.');
    }

    /**
     * Convierte los detalles del pedido en el arreglo de items que consume el
     * carrito del navegador. En modo solo lectura (adicionar) cada línea se
     * marca para que no se pueda quitar.
     *
     * @param  Collection<int, DetallePedido>  $detalles
     * @return Collection<int, array<string, mixed>>
     */
    private function detallesEnJson(Collection $detalles, bool $soloLectura = false): Collection
    {
        return $detalles->map(fn (DetallePedido $detalle): array => [
            'tipo' => $detalle->idpromocionsucursal !== null ? 'promocion' : 'producto',
            'id' => (int) ($detalle->idpromocionsucursal ?? $detalle->idproductosucursal),
            'nombre' => $detalle->idpromocionsucursal !== null
                ? $detalle->promocionSucursal?->promocion?->nombre
                : $detalle->sucursalProducto?->producto?->nombre,
            'precio' => (float) $detalle->preciounitario,
            // El descuento del carrito es por unidad; el de la fila es el
            // total de la línea.
            'descuento' => round((float) $detalle->descuento / max(1, $detalle->cantidad), 2),
            'cantidad' => $detalle->cantidad,
            // Cuenta a la que pertenece la línea (1 = pedido original).
            'cuenta' => (int) ($detalle->nrocuenta ?? 1),
            'soloLectura' => $soloLectura,
        ])->values();
    }

    /**
     * Guarda los cambios de un pedido todavía no cobrado.
     *
     * Recalcula subtotal, descuento y total con los precios de la sucursal del
     * momento, igual que al registrarlo: nada de lo que mande el navegador se
     * guarda tal cual. Los detalles se rehacen desde cero, que es la única
     * forma de que las líneas borradas desaparezcan de verdad.
     */
    public function update(Request $request, Pedido $pedido): RedirectResponse
    {
        $empleado = auth()->user()->empleado->load('rol');

        $sucursal = $this->sucursalesPermitidas($empleado)->firstWhere('id', $pedido->idsucursal);

        if (! $sucursal) {
            return back()->with('error', 'Ese pedido no pertenece a una sucursal que puedas atender.');
        }

        if ($pedido->estadopedido !== 'PENDIENTE') {
            return back()->with('error', $this->mensajeNoEditable($pedido));
        }

        if (! $this->cajaAbiertaDe($sucursal)) {
            return back()->with('error', "No hay una caja abierta en {$sucursal->nombre}; abre la caja del turno antes de editar pedidos.");
        }

        $validated = $request->validate($this->reglasPedido($sucursal));

        $preparacion = $this->prepararDetalles($validated['items'], $sucursal);

        if (is_string($preparacion)) {
            return back()->withInput()->withErrors(['items' => $preparacion]);
        }

        ['detalles' => $detalles, 'subtotal' => $subtotal, 'descuento' => $descuento] = $preparacion;

        $total = round($subtotal - $descuento, 2);

        try {
            DB::transaction(function () use ($pedido, $validated, $detalles, $subtotal, $descuento, $total): void {
                $pedido->update([
                    'tipopedido' => $validated['tipopedido'],
                    'idmesa' => $validated['tipopedido'] === 'MESA' ? $validated['idmesa'] : null,
                    'observaciones' => $validated['observaciones'] ?? null,
                    'subtotalpedido' => $subtotal,
                    'descuentopedido' => $descuento,
                    'total' => $total,
                ]);

                $pedido->detalles()->delete();

                foreach ($detalles as $detalle) {
                    $pedido->detalles()->create($detalle);
                }
            });
        } catch (QueryException) {
            return back()->with('error', 'No se pudo actualizar el pedido; revisa los datos e inténtalo de nuevo.');
        }

        $this->registrarAccion($empleado->id, 'UPDATE', 'pedido', $pedido->id, '501');

        return redirect()->route('dashboard.generar-pedidos.index', ['idsucursal' => $sucursal->id])
            ->with('exito', "Pedido #{$pedido->nropedido} actualizado por Bs. ".number_format($total, 2).'.');
    }

    /**
     * Motivo por el que un pedido que ya no está pendiente no se puede editar,
     * según su estado: anulado, cobrado o entregado al cliente.
     */
    private function mensajeNoEditable(Pedido $pedido): string
    {
        $motivo = match (true) {
            $pedido->estadopedido === 'ANULADO' => 'está anulado',
            $pedido->estadopedido === 'COBRADO' => 'ya está cobrado',
            $pedido->estadopedido === 'EJECUTADO' => 'ya fue entregado al cliente',
            default => 'ya no está pendiente',
        };

        return "El pedido #{$pedido->nropedido} {$motivo}; solo los pedidos pendientes se pueden editar.";
    }

    /**
     * Caja abierta de la sucursal en este momento, o null si no hay turno.
     */
    private function cajaAbiertaDe(Sucursal $sucursal): ?AperturaCaja
    {
        return AperturaCaja::where('estado', 'ABIERTA')
            ->whereHas('caja', fn ($query) => $query->where('idsucursal', $sucursal->id))
            ->latest('fechaapertura')
            ->first();
    }

    /**
     * Reglas del formulario de pedido, iguales al registrar y al editar.
     *
     * @return array<string, array<int, mixed>>
     */
    private function reglasPedido(Sucursal $sucursal): array
    {
        return [
            'tipopedido' => ['required', Rule::in(self::TIPOS_PEDIDO)],
            'idmesa' => [
                'exclude_unless:tipopedido,MESA',
                'integer',
                'required_if:tipopedido,MESA',
                Rule::exists('mesa', 'id')->where('idsucursal', $sucursal->id)->where('estado', true),
            ],
            'observaciones' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1', 'max:50'],
            // Cada item lleva un producto o una promoción. Sin «distinct»: con
            // dos campos anulables, las líneas de promoción dejarían el producto
            // en nulo y Laravel las marcaría todas como duplicadas entre sí.
            'items.*.idproductosucursal' => [
                'nullable',
                'integer',
                Rule::exists('sucursal_producto', 'id')
                    ->where('idsucursal', $sucursal->id)
                    ->where('disponibilidad', true),
            ],
            'items.*.idpromocionsucursal' => [
                'nullable',
                'integer',
                Rule::exists('promocion_sucursal', 'id')
                    ->where('idsucursal', $sucursal->id)
                    ->where('activo', true),
            ],
            'items.*.cantidad' => ['required', 'integer', 'min:1', 'max:999'],
        ];
    }

    /**
     * Valida las líneas del pedido y arma sus detalles con los precios de la
     * sucursal, nunca con los del navegador.
     *
     * @param  array<int, array<string, mixed>>  $items
     * @return array{detalles: array<int, array<string, mixed>>, subtotal: float, descuento: float}|string
     */
    private function prepararDetalles(array $items, Sucursal $sucursal): array|string
    {
        $items = collect($items);

        // Cada línea lleva un producto o una promoción: nunca las dos cosas
        // ni ninguna, porque detallepedido admite un solo tipo por fila.
        $lineasInvalidas = $items->filter(
            fn (array $item): bool => isset($item['idproductosucursal']) === isset($item['idpromocionsucursal'])
        );

        if ($lineasInvalidas->isNotEmpty()) {
            return 'Cada línea del pedido debe llevar un producto o una promoción, nunca las dos cosas ni ninguna.';
        }

        $idsProducto = $items->filter(fn (array $item): bool => isset($item['idproductosucursal']))
            ->pluck('idproductosucursal')
            ->unique();

        $idsPromocion = $items->filter(fn (array $item): bool => isset($item['idpromocionsucursal']))
            ->pluck('idpromocionsucursal')
            ->unique();

        $productos = SucursalProducto::with('producto')
            ->where('idsucursal', $sucursal->id)
            ->whereIn('id', $idsProducto->all())
            ->where('disponibilidad', true)
            ->whereHas('producto', fn ($query) => $query->where('estado', true))
            ->get()
            ->keyBy('id');

        if ($productos->count() !== $idsProducto->count()) {
            return "Uno o más productos ya no están disponibles en {$sucursal->nombre}; revisa el catálogo.";
        }

        // Reutiliza el mismo criterio de vigencia que usa el panel del cajero:
        // la promoción tiene que estar activa, dentro de su rango de fechas y
        // caer en uno de sus días, o no se puede vender.
        $promociones = $this->promocionesVigentes($sucursal->id)
            ->whereIn('id', $idsPromocion->all())
            ->keyBy('id');

        if ($promociones->count() !== $idsPromocion->count()) {
            return "Una o más promociones ya no están vigentes en {$sucursal->nombre}; revisa el panel de ofertas.";
        }

        $errorPromocion = $this->validarPromocionVendible($promociones, $sucursal->nombre);

        if ($errorPromocion !== null) {
            return $errorPromocion;
        }

        $subtotal = 0.0;
        $descuento = 0.0;
        $detalles = [];

        foreach ($items as $item) {
            if (isset($item['idpromocionsucursal'])) {
                $promocion = $promociones->get($item['idpromocionsucursal']);
                $lineaSubtotal = round($promocion->subtotalCombo() * $item['cantidad'], 2);
                $lineaDescuento = round($promocion->descuentoCombo() * $item['cantidad'], 2);

                $detalles[] = [
                    'idpromocionsucursal' => $promocion->id,
                    'cantidad' => $item['cantidad'],
                    'preciounitario' => $promocion->subtotalCombo(),
                    'subtotal' => $lineaSubtotal,
                    'descuento' => $lineaDescuento,
                    'total' => round($lineaSubtotal - $lineaDescuento, 2),
                ];

                $subtotal = round($subtotal + $lineaSubtotal, 2);
                $descuento = round($descuento + $lineaDescuento, 2);

                continue;
            }

            $productoSucursal = $productos->get($item['idproductosucursal']);
            $linea = round((float) $productoSucursal->precioproducto * $item['cantidad'], 2);

            $subtotal = round($subtotal + $linea, 2);

            $detalles[] = [
                'idproductosucursal' => $productoSucursal->id,
                'cantidad' => $item['cantidad'],
                'preciounitario' => $productoSucursal->precioproducto,
                'subtotal' => $linea,
                'descuento' => 0,
                'total' => $linea,
            ];
        }

        return [
            'detalles' => $detalles,
            'subtotal' => $subtotal,
            'descuento' => $descuento,
        ];
    }

    /**
     * Ejecuta un pedido: lo pasa de PENDIENTE a EJECUTADO (en preparación o
     * entregado, quizá todavía sin pagar).
     *
     * Ejecutar y cobrar son cosas distintas: un pedido pendiente puede pasar a
     * EJECUTADO sin pagarse, del cual todavía se puede cobrar. Solo se ejecutan
     * pedidos pendientes; los que ya están ejecutados o cobrados no se tocan.
     */
    public function ejecutar(Pedido $pedido): RedirectResponse
    {
        $empleado = auth()->user()->empleado->load('rol');

        $sucursales = $this->sucursalesPermitidas($empleado);

        if (! $sucursales->firstWhere('id', $pedido->idsucursal)) {
            return back()->with('error', 'Ese pedido no pertenece a una sucursal que puedas atender.');
        }

        if ($pedido->estadopedido !== 'PENDIENTE') {
            return back()->with('error', "El pedido #{$pedido->nropedido} no está pendiente; solo se ejecutan pedidos pendientes.");
        }

        $pedido->update(['estadopedido' => 'EJECUTADO']);

        $this->registrarAccion($empleado->id, 'UPDATE', 'pedido', $pedido->id, '501');

        return redirect()->route('dashboard.generar-pedidos.index', ['idsucursal' => $pedido->idsucursal])
            ->with('exito', "Pedido #{$pedido->nropedido} ejecutado.");
    }

    /**
     * Cobra el pedido: registra la venta y el pago con el método elegido.
     *
     * Cobra desde cualquier estado salvo ANULADO: un pedido recién generado
     * (PENDIENTE) y uno ya entregado (EJECUTADO) se pueden cobrar; lo que no se
     * puede es cobrar dos veces ni cobrar algo anulado, y ahí están las
     * comprobaciones de estado.
     *
     * Con cuentas separadas se puede cobrar el total pendiente o solo una
     * cuenta (parámetro nrocuenta). El importe sale del servidor —el saldo del
     * pedido o las líneas de la cuenta elegida—, nunca del navegador. Mientras
     * quede saldo el pedido sigue EJECUTADO (todavía admite adiciones y otros
     * cobros) y recién cuando no queda nada por cobrar pasa a COBRADO.
     */
    public function cobrar(Request $request, Pedido $pedido): RedirectResponse
    {
        $empleado = auth()->user()->empleado->load('rol');

        $sucursales = $this->sucursalesPermitidas($empleado);

        if (! $sucursales->firstWhere('id', $pedido->idsucursal)) {
            return back()->with('error', 'Ese pedido no pertenece a una sucursal que puedas atender.');
        }

        if ($pedido->estadopedido === 'ANULADO') {
            return back()->with('error', "El pedido #{$pedido->nropedido} está anulado y no se puede cobrar.");
        }

        $saldoPendiente = $pedido->saldoPendiente();

        if ($pedido->estadopedido === 'COBRADO' || $saldoPendiente <= 0) {
            return back()->with('error', "El pedido #{$pedido->nropedido} ya está cobrado.");
        }

        $aperturaCaja = AperturaCaja::where('estado', 'ABIERTA')
            ->whereHas('caja', fn ($query) => $query->where('idsucursal', $pedido->idsucursal))
            ->latest('fechaapertura')
            ->first();

        if (! $aperturaCaja) {
            return back()->with('error', 'No hay una caja abierta en la sucursal del pedido; abre la caja del turno antes de cobrar.');
        }

        $validated = $request->validate([
            'idmetodopago' => [
                'required',
                'integer',
                Rule::exists('metodopago', 'id')->where('activo', true),
            ],
            'montorecibido' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
            // Cuenta que se paga en este cobro; vacío = total pendiente.
            'nrocuenta' => ['nullable', 'integer', 'min:1'],
            // Referencia WizPay cuando el pago es con tarjeta.
            'wizpay_reference' => ['nullable', 'string', 'max:100'],
        ]);

        $metodoPago = MetodoPago::findOrFail($validated['idmetodopago']);
        $esTarjeta = strtoupper((string) $metodoPago->gateway) === 'TARJETA';
        $wizpayReference = $validated['wizpay_reference'] ?? null;

        // Pagos con tarjeta: la pasarela ya autorizó el cobro; verificamos el
        // estado final antes de registrar la venta.
        if ($esTarjeta) {
            if ($wizpayReference === null) {
                return back()->with('error', 'Falta la referencia del pago con tarjeta; procesa el pago primero.');
            }

            try {
                $wizpay = app(WizPayService::class);
                $estadoWizPay = $wizpay->status($wizpayReference);
            } catch (\Exception $e) {
                return back()->with('error', 'No se pudo verificar el pago con WizPay: '.$e->getMessage());
            }

            if (($estadoWizPay['status'] ?? '') !== 'APROBADO') {
                $estado = $estadoWizPay['status'] ?? 'desconocido';

                return back()->with('error', "El pago con tarjeta no está aprobado en WizPay (estado: {$estado}).");
            }

            // La pasarela cobró en BOB; verificamos que coincida con lo esperado.
            $montoWizPay = round((float) ($estadoWizPay['amount'] ?? 0), 2);

            if ($montoWizPay !== round($total, 2)) {
                return back()->with('error', 'El monto cobrado por WizPay (Bs. '.number_format($montoWizPay, 2).') no coincide con el total del pedido (Bs. '.number_format($total, 2).').');
            }
        }

        $nrocuenta = $validated['nrocuenta'] ?? null;
        $cuentasPendientes = array_values(array_filter(
            $pedido->cuentas(),
            fn (array $cuenta): bool => ! $cuenta['pagada'],
        ));

        if ($nrocuenta !== null) {
            $cuenta = collect($cuentasPendientes)->firstWhere('n', $nrocuenta);

            if (! $cuenta) {
                return back()->with('error', "La cuenta {$nrocuenta} no está pendiente de cobro en el pedido #{$pedido->nropedido}.");
            }

            $total = (float) $cuenta['total'];
            $queSeCobra = "la cuenta {$nrocuenta} del pedido";
        } else {
            $total = $saldoPendiente;
            $queSeCobra = 'el total pendiente del pedido';
        }

        $esEfectivo = strtoupper((string) $metodoPago->gateway) === 'EFECTIVO'
            || strtolower($metodoPago->nombre) === 'efectivo';
        $recibido = $validated['montorecibido'] ?? null;

        if ($esEfectivo) {
            // En efectivo el monto recibido no puede faltar: sin ese dato no hay
            // cambio que entregar y la caja se queda sin constancia de lo que
            // entró. Si falta, no se cobra.
            if ($recibido === null) {
                return back()->withInput()->withErrors([
                    'montorecibido' => 'En efectivo hay que indicar el monto recibido; sin ese dato no se puede calcular el cambio.',
                ]);
            }

            $montoRecibido = round((float) $recibido, 2);

            if ($montoRecibido < $total) {
                return back()->withInput()->withErrors([
                    'montorecibido' => 'El monto recibido (Bs. '.number_format($montoRecibido, 2).') no puede ser menor a '.$queSeCobra.' (Bs. '.number_format($total, 2).').',
                ]);
            }

            $cambio = round($montoRecibido - $total, 2);
        } else {
            $montoRecibido = null;
            $cambio = 0.0;
        }

        // El estado depende de lo que quede después de este cobro: con cuentas
        // separadas un pago parcial deja el pedido entregado y sin cerrar.
        $estadoPosterior = round($saldoPendiente - $total, 2) > 0 ? 'EJECUTADO' : 'COBRADO';

        try {
            DB::transaction(function () use ($pedido, $empleado, $aperturaCaja, $metodoPago, $total, $montoRecibido, $cambio, $nrocuenta, $estadoPosterior, $esTarjeta, $wizpayReference): void {
                $venta = Venta::create([
                    'fechaventa' => now(),
                    'totalventa' => $total,
                    'estadoventa' => 'PAGADA',
                    'idempleado' => $empleado->id,
                    'idpedido' => $pedido->id,
                    // Vacío cuando el cobro cubrió el total pendiente.
                    'nrocuenta' => $nrocuenta,
                ]);

                Pago::create([
                    'idventa' => $venta->id,
                    'idmetodopago' => $metodoPago->id,
                    'idaperturacaja' => $aperturaCaja->id,
                    'montopago' => $total,
                    'estadopago' => 'APROBADO',
                    'montorecibido' => $montoRecibido,
                    'cambioentregado' => $cambio,
                    'fechapago' => now(),
                    // Referencia de la pasarela para conciliar el cobro con tarjeta.
                    'referenciaexterna' => $esTarjeta ? $wizpayReference : null,
                    'gateway' => $esTarjeta ? 'WIZPAY' : strtoupper((string) $metodoPago->gateway),
                ]);

                $pedido->update(['estadopedido' => $estadoPosterior]);
            });
        } catch (QueryException) {
            return back()->with('error', 'No se pudo registrar el cobro; revisa los datos e inténtalo de nuevo.');
        }

        $this->registrarAccion($empleado->id, 'UPDATE', 'pedido', $pedido->id, '501');

        $restante = round($saldoPendiente - $total, 2);
        $mensaje = $nrocuenta !== null
            ? "Cobrada la cuenta {$nrocuenta} del pedido #{$pedido->nropedido} por Bs. ".number_format($total, 2)
                .($restante > 0 ? '; quedan Bs. '.number_format($restante, 2).' pendientes.' : '.')
            : "Pedido #{$pedido->nropedido} cobrado por Bs. ".number_format($total, 2).'.';

        return redirect()->route('dashboard.generar-pedidos.index', ['idsucursal' => $pedido->idsucursal])
            ->with('exito', $mensaje);
    }

    /**
     * Anula un pedido todavía pendiente: pierde todos los demás acciones
     * (editar, entregar y cobrar) y queda como registro en la pestaña de
     * anulados.
     *
     * Solo se anula desde PENDIENTE: lo entregado (EJECUTADO) ya está en manos
     * del cliente y lo cobrado (COBRADO) es una cuenta cerrada; ninguno de los
     * dos se puede anular por esta vía.
     */
    public function anular(Pedido $pedido): RedirectResponse
    {
        $empleado = auth()->user()->empleado->load('rol');

        $sucursales = $this->sucursalesPermitidas($empleado);

        if (! $sucursales->firstWhere('id', $pedido->idsucursal)) {
            return back()->with('error', 'Ese pedido no pertenece a una sucursal que puedas atender.');
        }

        if ($pedido->estadopedido === 'ANULADO') {
            return back()->with('error', "El pedido #{$pedido->nropedido} ya está anulado.");
        }

        // Primero el estado de entrega: con cuentas separadas un pedido puede
        // estar EJECUTADO y ya tener un cobro parcial, y en ese caso el motivo
        // es que la comida ya salió, no que la cuenta esté cerrada.
        if ($pedido->estadopedido === 'EJECUTADO') {
            return back()->with('error', "El pedido #{$pedido->nropedido} ya fue entregado al cliente; no se puede anular.");
        }

        if ($pedido->estadopedido === 'COBRADO' || $pedido->saldoPendiente() <= 0) {
            return back()->with('error', "El pedido #{$pedido->nropedido} ya está cobrado; no se puede anular.");
        }

        try {
            $pedido->update(['estadopedido' => 'ANULADO']);
        } catch (QueryException) {
            return back()->with('error', 'No se pudo anular el pedido; inténtalo de nuevo.');
        }

        $this->registrarAccion($empleado->id, 'UPDATE', 'pedido', $pedido->id, '505');

        return redirect()->route('dashboard.generar-pedidos.index', ['idsucursal' => $pedido->idsucursal])
            ->with('exito', "Pedido #{$pedido->nropedido} anulado.");
    }

    // ── WizPay: cobro con tarjeta vía pasarela boliviana ──────────────────────

    /**
     * Crea una transacción en WizPay y devuelve la reference + fingerprint.
     */
    public function wizpayInit(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'pedido_id' => ['required', 'integer', 'exists:pedido,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'nombre' => ['nullable', 'string', 'max:255'],
        ]);

        $pedido = Pedido::findOrFail($validated['pedido_id']);
        $this->autorizarSucursalPedido($pedido);

        try {
            $wizpay = app(WizPayService::class);

            $init = $wizpay->init([
                'name' => $validated['nombre'] ?? "Pedido #{$pedido->nropedido}",
                'amount' => round((float) $validated['amount'], 2),
                'id_currency' => 1, // BOB
                'order_id' => 'PED-'.$pedido->id.'-'.time(),
                'description' => 'Cobro pedido #'.$pedido->nropedido,
                'metadata' => ['pedido_id' => $pedido->id],
            ]);

            return response()->json($init, 201);
        } catch (\Exception $e) {
            return response()->json([
                'mensaje' => 'No se pudo iniciar la transacción en WizPay: '.$e->getMessage(),
            ], 502);
        }
    }

    /**
     * Authentication Setup de 3D Secure: devuelve tokens para la DDC.
     */
    public function wizpayAuthSetup(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'reference' => ['required', 'string', 'max:100'],
            'card' => ['required', 'array'],
            'card.type' => ['required', 'string', 'in:001,002,003'],
            'card.number' => ['required', 'string', 'min:13', 'max:19'],
            'card.expiry' => ['required', 'string', 'regex:/^\d{2}\/\d{4}$/'],
            'card.cvv' => ['required', 'string', 'min:3', 'max:4'],
            'fingerprint_session_id' => ['nullable', 'string', 'max:100'],
        ]);

        try {
            $wizpay = app(WizPayService::class);

            $auth = $wizpay->authSetup([
                'reference' => $validated['reference'],
                'card' => $validated['card'],
                'fingerprint_session_id' => $validated['fingerprint_session_id'] ?? null,
            ]);

            return response()->json($auth, 201);
        } catch (\Exception $e) {
            return response()->json([
                'mensaje' => 'No se pudo preparar la autenticación 3DS: '.$e->getMessage(),
            ], 502);
        }
    }

    /**
     * Autoriza y captura el cobro con tarjeta.
     *
     * Devuelve APROBADO, RECHAZADO o PENDING_3DS según la respuesta de WizPay.
     */
    public function wizpayProcess(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'reference' => ['required', 'string', 'max:100'],
            'card' => ['required', 'array'],
            'card.type' => ['required', 'string', 'in:001,002,003'],
            'card.number' => ['required', 'string', 'min:13', 'max:19'],
            'card.expiry' => ['required', 'string', 'regex:/^\d{2}\/\d{4}$/'],
            'card.cvv' => ['required', 'string', 'min:3', 'max:4'],
            'billing' => ['required', 'array'],
            'billing.address1' => ['required', 'string', 'max:60'],
            'billing.city' => ['required', 'string', 'max:60'],
            'billing.country_code' => ['required', 'string', 'size:2'],
            'personal' => ['required', 'array'],
            'personal.full_name' => ['required', 'string', 'max:255'],
            'personal.email' => ['required', 'email', 'max:255'],
            'personal.phone' => ['required', 'string', 'max:20'],
            'cs_reference_id' => ['nullable', 'string', 'max:100'],
            'fingerprint_session_id' => ['nullable', 'string', 'max:100'],
        ]);

        try {
            $wizpay = app(WizPayService::class);

            $payload = [
                'reference' => $validated['reference'],
                'card' => $validated['card'],
                'billing' => $validated['billing'],
                'personal' => $validated['personal'],
                'save_card' => false,
                'return_url' => config('services.wizpay.return_url'),
            ];

            if (! empty($validated['cs_reference_id'])) {
                $payload['cs_reference_id'] = $validated['cs_reference_id'];
            }

            if (! empty($validated['fingerprint_session_id'])) {
                $payload['fingerprint_session_id'] = $validated['fingerprint_session_id'];
            }

            $result = $wizpay->process($payload);

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'mensaje' => 'No se pudo procesar el pago: '.$e->getMessage(),
            ], 502);
        }
    }

    /**
     * Consulta el estado final de una transacción WizPay.
     */
    public function wizpayStatus(string $reference): JsonResponse
    {
        try {
            $wizpay = app(WizPayService::class);
            $status = $wizpay->status($reference);

            return response()->json($status);
        } catch (\Exception $e) {
            return response()->json([
                'mensaje' => 'No se pudo consultar el estado: '.$e->getMessage(),
            ], 502);
        }
    }

    /**
     * Verifica que el pedido pertenece a una sucursal del empleado autenticado.
     */
    private function autorizarSucursalPedido(Pedido $pedido): void
    {
        $empleado = auth()->user()->empleado->load('rol');
        $sucursales = $this->sucursalesPermitidas($empleado);

        abort_unless($sucursales->firstWhere('id', $pedido->idsucursal), 403, 'Ese pedido no pertenece a una sucursal que puedas atender.');
    }

    /**
     * Categorías activas con los productos disponibles (y activos) de la sucursal,
     * cada uno con su precio de sucursal y sus promociones vinculadas.
     *
     * @return Collection<int, array{categoria: Categoria, productos: Collection<int, SucursalProducto>}>
     */
    private function catalogoSucursal(int $idsucursal): Collection
    {
        $productosSucursal = SucursalProducto::with(['producto.categoria', 'promocionesSucursal'])
            ->where('idsucursal', $idsucursal)
            ->where('disponibilidad', true)
            ->whereHas('producto', fn ($query) => $query->where('estado', true))
            ->get();

        return Categoria::where('estado', true)
            ->orderBy('nombre')
            ->get()
            ->map(fn (Categoria $categoria): array => [
                'categoria' => $categoria,
                'productos' => $productosSucursal
                    ->filter(fn (SucursalProducto $sp) => $sp->producto->idcategoria === $categoria->id)
                    ->values(),
            ])
            ->filter(fn (array $item) => $item['productos']->isNotEmpty())
            ->values();
    }

    /**
     * Promociones de la sucursal activas hoy: configuración activa, rango de fechas
     * vigente y día de la semana (1 = lunes … 7 = domingo) incluido.
     */
    private function promocionesVigentes(int $idsucursal): Collection
    {
        return PromocionSucursal::with(['promocion', 'dias', 'productosSucursal.producto'])
            ->where('idsucursal', $idsucursal)
            ->where('activo', true)
            ->whereHas('promocion', fn ($query) => $query->where('activo', true))
            ->whereDate('fechainicio', '<=', today())
            ->whereDate('fechafin', '>=', today())
            ->whereHas('dias', fn ($query) => $query->where('diasemana', now()->isoWeekday()))
            ->orderByDesc('fechafin')
            ->get();
    }

    /**
     * Comprueba que cada promoción pedida se pueda vender: necesita tener
     * productos participantes y todos ellos disponibles, y el precio
     * promocional no puede superar al valor del combo (con ese dato
     * no habría descuento y la fila rompería el CHECK de descuento >= 0).
     *
     * @param  Collection<int, PromocionSucursal>  $promociones
     */
    private function validarPromocionVendible(Collection $promociones, string $nombreSucursal): ?string
    {
        foreach ($promociones as $promocion) {
            $nombre = $promocion->promocion?->nombre ?? 'sin nombre';

            if ($promocion->productosSucursal->isEmpty()) {
                return "La promoción «{$nombre}» no tiene productos participantes.";
            }

            $agotados = $promocion->productosSucursal->reject(
                fn (SucursalProducto $producto): bool => $producto->disponibilidad
                    && $producto->producto?->estado
            );

            if ($agotados->isNotEmpty()) {
                $nombres = $agotados
                    ->map(fn (SucursalProducto $producto): string => $producto->producto?->nombre ?? 'producto')
                    ->implode(', ');

                return "La promoción «{$nombre}» incluye productos que ya no están disponibles en {$nombreSucursal}: {$nombres}.";
            }

            if ($promocion->tipodescuento === 'PRECIO_PROMOCIONAL'
                && $promocion->totalCombo() > $promocion->subtotalCombo()) {
                return "La promoción «{$nombre}» cuesta Bs. ".number_format($promocion->totalCombo(), 2)
                    .' sobre un combo de Bs. '.number_format($promocion->subtotalCombo(), 2)
                    .'; corrige su precio antes de poder venderla.';
            }
        }

        return null;
    }

    private function sucursalesPermitidas(Empleado $empleado): Collection
    {
        $claverol = $empleado->rol?->claverol;

        if ($claverol === 'ADMINISTRADOR') {
            return Sucursal::orderBy('nombre')->get();
        }

        if ($claverol === 'GERENTE') {
            return $empleado->sucursalesGerenciadas()
                ->where('estado', true)
                ->orderBy('nombre')
                ->get();
        }

        return $empleado->idsucursal
            ? Sucursal::where('id', $empleado->idsucursal)->get()
            : collect();
    }
}
