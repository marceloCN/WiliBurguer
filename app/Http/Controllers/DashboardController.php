<?php

namespace App\Http\Controllers;

use App\Models\AperturaCaja;
use App\Models\Caja;
use App\Models\Empleado;
use App\Models\Insumo;
use App\Models\Mesa;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\Sucursal;
use App\Models\UnidadMedida;
use App\Models\Venta;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Modulos agrupados por dominio. Cada modulo define TODOS los codigos de
     * permiso que exige su ruta en web.php: si el rol no tiene todos, el
     * modulo no se muestra (misma regla que el middleware permiso:...).
     */
    private const MODULOS = [
        'pedidos' => [
            'titulo' => 'Generar pedidos',
            'descripcion' => 'Toma pedidos de mesa y para llevar.',
            'icono' => '🧾',
            'color' => 'text-bg-success',
            'ruta' => 'dashboard.generar-pedidos.index',
            'activo' => true,
            'grupo' => 'operacion',
            'restriccion' => 'con_sucursal',
            'permisos' => ['500', '501', '502', '505', '506'],
        ],
        'caja' => [
            'titulo' => 'Caja del turno',
            'descripcion' => 'Abre y cierra la caja, registra movimientos y consulta el historial.',
            'icono' => '💰',
            'color' => 'text-bg-warning',
            'ruta' => 'dashboard.caja.index',
            'activo' => true,
            'grupo' => 'operacion',
            'restriccion' => 'con_sucursal',
            'permisos' => ['410', '412', '420', '422', '430', '436', '437'],
        ],
        'productos' => [
            'titulo' => 'Productos y categorias',
            'descripcion' => 'Catalogo central de productos y sus categorias.',
            'icono' => '🍔',
            'color' => 'text-bg-primary',
            'ruta' => 'dashboard.productos.index',
            'activo' => true,
            'grupo' => 'catalogo',
            'permisos' => ['302', '312'],
        ],
        'productos-sucursal' => [
            'titulo' => 'Productos por sucursal',
            'descripcion' => 'Asigna precios y disponibilidad de cada producto en la sucursal.',
            'icono' => '🏷️',
            'color' => 'text-bg-info',
            'ruta' => 'dashboard.productos-sucursal.index',
            'activo' => true,
            'grupo' => 'catalogo',
            'permisos' => ['317'],
        ],
        'promociones' => [
            'titulo' => 'Promociones',
            'descripcion' => 'Publica promociones vigentes con sus descuentos y productos.',
            'icono' => '🎉',
            'color' => 'text-bg-danger',
            'ruta' => 'dashboard.promociones.index',
            'activo' => true,
            'grupo' => 'catalogo',
            'permisos' => ['352'],
        ],
        'recetas' => [
            'titulo' => 'Recetas',
            'descripcion' => 'Vincula cada producto con los insumos que necesita prepararlo.',
            'icono' => '📖',
            'color' => 'text-bg-secondary',
            'ruta' => 'dashboard.recetas.index',
            'activo' => true,
            'grupo' => 'catalogo',
            'permisos' => ['340', '341', '345'],
        ],
        'inventario' => [
            'titulo' => 'Inventario',
            'descripcion' => 'Controla el stock de insumos por sucursal.',
            'icono' => '📦',
            'color' => 'text-bg-brand',
            'ruta' => 'dashboard.inventario.index',
            'activo' => true,
            'grupo' => 'inventario',
            'restriccion' => 'con_sucursal',
            'permisos' => ['347'],
        ],
        'insumos' => [
            'titulo' => 'Insumos',
            'descripcion' => 'Catalogo global de insumos y unidades de medida.',
            'icono' => '🧂',
            'color' => 'text-bg-info',
            'ruta' => 'dashboard.insumos.index',
            'activo' => true,
            'grupo' => 'inventario',
            'restriccion' => 'admin_o_gerente',
            'permisos' => ['330', '336'],
        ],
        'sucursales' => [
            'titulo' => 'Sucursales',
            'descripcion' => 'Administra sucursales, horarios, mesas y cajas.',
            'icono' => '🏪',
            'color' => 'text-bg-brand',
            'ruta' => 'dashboard.sucursales.index',
            'activo' => true,
            'grupo' => 'administracion',
            'restriccion' => 'admin_o_gerente_con_sucursal',
            'permisos' => ['200', '201', '202', '208'],
        ],
        'usuarios' => [
            'titulo' => 'Empleados y cuentas',
            'descripcion' => 'Registra y administra empleados, roles y accesos.',
            'icono' => '🧑‍💼',
            'color' => 'text-bg-primary',
            'ruta' => 'dashboard.usuarios.dashboard',
            'activo' => true,
            'grupo' => 'administracion',
            'restriccion' => 'admin_o_gerente_con_sucursal',
            'permisos' => ['102'],
        ],
        'bitacora-accesos' => [
            'titulo' => 'Bitacora de accesos',
            'descripcion' => 'Audita los ingresos y salidas al sistema.',
            'icono' => '📜',
            'color' => 'text-bg-secondary',
            'ruta' => 'dashboard.bitacora-accesos.index',
            'activo' => true,
            'grupo' => 'administracion',
            'permisos' => ['150'],
        ],
        'bitacora-acciones' => [
            'titulo' => 'Bitacora de acciones',
            'descripcion' => 'Audita las acciones realizadas en el sistema.',
            'icono' => '📋',
            'color' => 'text-bg-dark',
            'ruta' => 'dashboard.bitacora-acciones.index',
            'activo' => true,
            'grupo' => 'administracion',
            'permisos' => ['151'],
        ],
        'configuracion' => [
            'titulo' => 'Configuracion del sitio',
            'descripcion' => 'Personaliza nombre, colores, carrusel y secciones.',
            'icono' => '⚙️',
            'color' => 'text-bg-warning',
            'ruta' => 'dashboard.configuracion.index',
            'activo' => true,
            'grupo' => 'administracion',
            'restriccion' => 'solo_admin',
            'permisos' => ['50'],
        ],
        'permisos' => [
            'titulo' => 'Gestion de permisos',
            'descripcion' => 'Define que permisos tiene cada rol.',
            'icono' => '🔐',
            'color' => 'text-bg-info',
            'ruta' => 'dashboard.permisos.index',
            'activo' => true,
            'grupo' => 'administracion',
            'restriccion' => 'solo_admin',
            'permisos' => [],
        ],
    ];

    public function index(Request $request): View|RedirectResponse
    {
        $empleado = auth()->user()->empleado->load('rol.permisos');
        $rol = $empleado->rol;

        // El mesero aterriza directo en la ventana de atencion (Generar
        // pedidos), pero solo si su sucursal ya tiene la caja del turno
        // abierta: sin una apertura activa no puede tomar pedidos y se queda
        // en el panel mostrando el aviso. Cajero y gerente entran al panel de
        // administracion y desde alli abren la caja o generan pedidos.
        $aperturaAbierta = $rol->claverol === 'MESERO'
            && $empleado->idsucursal !== null
            && AperturaCaja::where('estado', 'ABIERTA')
                ->whereHas('caja', fn ($q) => $q->where('idsucursal', $empleado->idsucursal))
                ->exists();

        if ($rol->claverol === 'MESERO' && ! $request->has('panel') && $aperturaAbierta) {
            return redirect()->route('dashboard.generar-pedidos.index', [
                'idsucursal' => $empleado->idsucursal,
            ]);
        }

        // Regla del sistema: los usuarios (no administradores) añadidos a una
        // sucursal son los que ven los menus; sin sucursal no hay menus y el
        // panel explica el motivo.
        $sinSucursal = ! $this->empleadoTieneSucursal($empleado);

        $codigos = $rol->permisos->where('estado', true)->pluck('codigo');

        $modulos = $sinSucursal
            ? []
            : $this->modulosDisponibles($codigos, $empleado);

        $estadisticas = $sinSucursal
            ? []
            : $this->estadisticasPorRol($empleado, $rol);

        $avisoCajaCerrada = $rol->claverol === 'MESERO'
            && ! $aperturaAbierta
            && ! $sinSucursal;

        return view('dashboard', compact('empleado', 'rol', 'modulos', 'sinSucursal', 'estadisticas', 'avisoCajaCerrada'));
    }

    /** Sucursales que el empleado puede operar (misma regla que promociones/caja). */
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

    private function empleadoTieneSucursal(Empleado $empleado): bool
    {
        if ($empleado->rol->claverol === 'ADMINISTRADOR') {
            return true;
        }

        if ($empleado->rol->claverol === 'GERENTE') {
            return $empleado->sucursalesGerenciadas()
                ->where('estado', true)
                ->exists();
        }

        return $empleado->idsucursal !== null;
    }

    private function modulosDisponibles(Collection $codigos, Empleado $empleado): array
    {
        $modulos = [];

        foreach (self::MODULOS as $clave => $modulo) {
            // Todos los permisos que exige la ruta (igual que el middleware
            // permiso:... de web.php): si falta uno, el modulo ni se muestra.
            $puedeVer = empty($modulo['permisos'])
                ? true
                : collect($modulo['permisos'])->diff($codigos)->isEmpty();

            if ($puedeVer && isset($modulo['restriccion'])) {
                $puedeVer = $this->cumpleRestriccion($modulo['restriccion'], $empleado);
            }

            // La gestion de permisos va tras el middleware admin: solo el
            // administrador, unico que puede modificar permisos, la ve.
            if ($clave === 'permisos') {
                $puedeVer = $empleado->rol->claverol === 'ADMINISTRADOR';
            }

            if ($puedeVer) {
                $modulos[$clave] = $modulo;
            }
        }

        return $modulos;
    }

    private function estadisticasPorRol(Empleado $empleado, $rol): array
    {
        return match ($rol->claverol) {
            'ADMINISTRADOR' => $this->estadisticasAdministrador(),
            'GERENTE' => $this->estadisticasGerente($empleado),
            'CAJERO' => $this->estadisticasCajero($empleado),
            'MESERO' => $this->estadisticasMesero($empleado),
            default => [],
        };
    }

    private function estadisticasAdministrador(): array
    {
        return [
            [
                'titulo' => 'Sucursales activas',
                'valor' => Sucursal::where('estado', true)->count(),
                'icono' => '🏪',
                'color' => 'text-bg-brand',
            ],
            [
                'titulo' => 'Empleados activos',
                'valor' => Empleado::where('estado', true)->count(),
                'icono' => '🧑‍💼',
                'color' => 'text-bg-primary',
            ],
            [
                'titulo' => 'Productos activos',
                'valor' => Producto::where('estado', true)->count(),
                'icono' => '🍔',
                'color' => 'text-bg-warning',
            ],
            [
                'titulo' => 'Pedidos de hoy',
                'valor' => Pedido::whereDate('fecha', today())->count(),
                'icono' => '🧾',
                'color' => 'text-bg-success',
            ],
            [
                'titulo' => 'Ventas de hoy',
                'valor' => 'Bs. '.number_format(Venta::whereDate('fechaventa', today())->sum('totalventa') ?: 0, 2),
                'icono' => '💵',
                'color' => 'text-bg-info',
            ],
            [
                'titulo' => 'Pedidos pendientes',
                'valor' => Pedido::where('estadopedido', 'PENDIENTE')->count(),
                'icono' => '⏳',
                'color' => 'text-bg-danger',
            ],
            [
                'titulo' => 'Insumos registrados',
                'valor' => Insumo::count(),
                'icono' => '🧂',
                'color' => 'text-bg-info',
            ],
            [
                'titulo' => 'Unidades de medida',
                'valor' => UnidadMedida::count(),
                'icono' => '📐',
                'color' => 'text-bg-secondary',
            ],
        ];
    }

    private function estadisticasGerente(Empleado $empleado): array
    {
        $idsSucursales = $this->sucursalesPermitidas($empleado)->pluck('id');
        $idsEmpleados = Empleado::whereIn('idsucursal', $idsSucursales)->pluck('id');

        $ventasHoy = Venta::whereDate('fechaventa', today())
            ->whereIn('idempleado', $idsEmpleados)
            ->sum('totalventa');

        return [
            [
                'titulo' => 'Mis sucursales',
                'valor' => $idsSucursales->count(),
                'icono' => '🏪',
                'color' => 'text-bg-brand',
            ],
            [
                'titulo' => 'Empleados a cargo',
                'valor' => $idsEmpleados->count(),
                'icono' => '🧑‍💼',
                'color' => 'text-bg-primary',
            ],
            [
                'titulo' => 'Pedidos de hoy',
                'valor' => Pedido::whereIn('idsucursal', $idsSucursales)->whereDate('fecha', today())->count(),
                'icono' => '🧾',
                'color' => 'text-bg-success',
            ],
            [
                'titulo' => 'Ventas de hoy',
                'valor' => 'Bs. '.number_format($ventasHoy ?: 0, 2),
                'icono' => '💵',
                'color' => 'text-bg-info',
            ],
            [
                'titulo' => 'Pedidos pendientes',
                'valor' => Pedido::whereIn('idsucursal', $idsSucursales)->where('estadopedido', 'PENDIENTE')->count(),
                'icono' => '⏳',
                'color' => 'text-bg-danger',
            ],
            [
                'titulo' => 'Insumos registrados',
                'valor' => Insumo::count(),
                'icono' => '🧂',
                'color' => 'text-bg-info',
            ],
            [
                'titulo' => 'Unidades de medida',
                'valor' => UnidadMedida::count(),
                'icono' => '📐',
                'color' => 'text-bg-secondary',
            ],
        ];
    }

    private function estadisticasCajero(Empleado $empleado): array
    {
        $sucursal = $empleado->idsucursal;

        $cajaAbierta = AperturaCaja::where('estado', 'ABIERTA')
            ->whereHas('caja', fn ($q) => $q->where('idsucursal', $sucursal))
            ->latest('fechaapertura')
            ->first();

        $ventasHoy = Venta::whereDate('fechaventa', today())
            ->where('idempleado', $empleado->id)
            ->sum('totalventa');

        return [
            [
                'titulo' => 'Mi sucursal',
                'valor' => Sucursal::where('id', $sucursal)->value('nombre') ?? 'Sin asignar',
                'icono' => '🏪',
                'color' => 'text-bg-brand',
                'es_texto' => true,
            ],
            [
                'titulo' => 'Caja actual',
                'valor' => $cajaAbierta ? 'Abierta' : 'Cerrada',
                'icono' => '💰',
                'color' => $cajaAbierta ? 'text-bg-success' : 'text-bg-secondary',
                'es_texto' => true,
            ],
            [
                'titulo' => 'Mis ventas de hoy',
                'valor' => 'Bs. '.number_format($ventasHoy ?: 0, 2),
                'icono' => '💵',
                'color' => 'text-bg-info',
            ],
            [
                'titulo' => 'Pedidos pendientes de pago',
                'valor' => Pedido::where('idsucursal', $sucursal)
                    ->where('estadopedido', 'EJECUTADO')
                    ->whereDoesntHave('venta')
                    ->count(),
                'icono' => '⏳',
                'color' => 'text-bg-warning',
            ],
        ];
    }

    private function estadisticasMesero(Empleado $empleado): array
    {
        $sucursal = $empleado->idsucursal;

        $mesasTotal = Mesa::where('idsucursal', $sucursal)->where('estado', true)->count();

        $mesasOcupadas = Mesa::where('idsucursal', $sucursal)
            ->where('estado', true)
            ->whereHas('pedidos', fn ($q) => $q->where('estadopedido', 'PENDIENTE'))
            ->count();

        return [
            [
                'titulo' => 'Mi sucursal',
                'valor' => Sucursal::where('id', $sucursal)->value('nombre') ?? 'Sin asignar',
                'icono' => '🏪',
                'color' => 'text-bg-brand',
                'es_texto' => true,
            ],
            [
                'titulo' => 'Mesas disponibles',
                'valor' => $mesasTotal - $mesasOcupadas,
                'icono' => '🪑',
                'color' => 'text-bg-success',
            ],
            [
                'titulo' => 'Mesas ocupadas',
                'valor' => $mesasOcupadas,
                'icono' => '🍽️',
                'color' => 'text-bg-danger',
            ],
            [
                'titulo' => 'Mis pedidos activos',
                'valor' => Pedido::where('idsucursal', $sucursal)
                    ->where('idempleado', $empleado->id)
                    ->whereIn('estadopedido', ['PENDIENTE', 'EJECUTADO'])
                    ->count(),
                'icono' => '🧾',
                'color' => 'text-bg-info',
            ],
        ];
    }

    private function cumpleRestriccion(string $restriccion, Empleado $empleado): bool
    {
        return match ($restriccion) {
            'admin_o_gerente' => in_array($empleado->rol->claverol, ['ADMINISTRADOR', 'GERENTE'], true),
            'admin_o_gerente_con_sucursal' => $this->empleadoPuedeUsuarios($empleado),
            'admin_o_gerente_con_sucursal_unica' => $this->empleadoEsAdminOGerenteSucursalUnica($empleado),
            'solo_admin' => $empleado->rol->claverol === 'ADMINISTRADOR',
            'con_sucursal' => $this->empleadoTieneSucursal($empleado),
            'no_admin_con_sucursal' => $empleado->rol->claverol !== 'ADMINISTRADOR' && $this->empleadoTieneSucursal($empleado),
            default => true,
        };
    }

    private function empleadoEsAdminOGerenteSucursalUnica(Empleado $empleado): bool
    {
        if ($empleado->rol->claverol === 'ADMINISTRADOR') {
            return true;
        }

        if ($empleado->rol->claverol === 'GERENTE') {
            return $empleado->sucursalesGerenciadas()
                ->where('estado', true)
                ->count() === 1;
        }

        return false;
    }

    private function empleadoPuedeUsuarios(Empleado $empleado): bool
    {
        if ($empleado->rol->claverol === 'ADMINISTRADOR') {
            return true;
        }

        if ($empleado->rol->claverol === 'GERENTE') {
            return $empleado->sucursalesGerenciadas()
                ->where('estado', true)
                ->exists();
        }

        return false;
    }
}
