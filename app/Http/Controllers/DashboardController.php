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
     * Modulos agrupados por dominio. Cada modulo define los codigos de permiso
     * que permiten verlo/operarlo.
     */
    private const MODULOS = [
        'inventario' => [
            'titulo' => 'Inventario',
            'descripcion' => 'Controla el stock de insumos por sucursal.',
            'icono' => '📦',
            'color' => 'text-bg-brand',
            'ruta' => 'dashboard.inventario.index',
            'activo' => true,
            'restriccion' => 'con_sucursal',
            'permisos' => ['347'],
        ],
        'insumos' => [
            'titulo' => 'Insumos',
            'descripcion' => 'Catálogo global de insumos y unidades de medida.',
            'icono' => '🧂',
            'color' => 'text-bg-info',
            'ruta' => 'dashboard.insumos.index',
            'activo' => true,
            'restriccion' => 'admin_o_gerente',
            'permisos' => [],
        ],
        'pedidos' => [
            'titulo' => 'Generar pedidos',
            'descripcion' => 'Toma pedidos de mesa y para llevar.',
            'icono' => '🧾',
            'color' => 'text-bg-success',
            'ruta' => 'dashboard.generar-pedidos.index',
            'activo' => true,
            'restriccion' => 'con_sucursal',
            'permisos' => ['500', '501', '502', '505', '506'],
        ],
        'sucursales' => [
            'titulo' => 'Sucursales',
            'descripcion' => 'Administra sucursales, horarios, mesas y cajas.',
            'icono' => '🏪',
            'color' => 'text-bg-brand',
            'ruta' => 'dashboard.sucursales.index',
            'activo' => true,
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
            'restriccion' => 'admin_o_gerente_con_sucursal',
            'permisos' => ['100', '101', '102', '105'],
        ],
        'bitacora-accesos' => [
            'titulo' => 'Bitacora de accesos',
            'descripcion' => 'Audita los ingresos y salidas al sistema.',
            'icono' => '📜',
            'color' => 'text-bg-secondary',
            'ruta' => 'dashboard.bitacora-accesos.index',
            'activo' => true,
            'permisos' => ['150'],
        ],
        'bitacora-acciones' => [
            'titulo' => 'Bitacora de acciones',
            'descripcion' => 'Audita las acciones realizadas en el sistema.',
            'icono' => '📋',
            'color' => 'text-bg-dark',
            'ruta' => 'dashboard.bitacora-acciones.index',
            'activo' => true,
            'permisos' => ['151'],
        ],
        'configuracion' => [
            'titulo' => 'Configuracion del sitio',
            'descripcion' => 'Personaliza nombre, colores, carrusel y secciones.',
            'icono' => '⚙️',
            'color' => 'text-bg-warning',
            'ruta' => 'dashboard.configuracion.index',
            'activo' => true,
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
            'restriccion' => 'solo_admin',
            'permisos' => [],
        ],
    ];

    public function index(Request $request): View|RedirectResponse
    {
        $empleado = auth()->user()->empleado->load('rol');
        $rol = $empleado->rol;

        // El mesero aterriza directamente en la ventana de atencion (Generar
        // pedidos); si no hay apertura de caja, la vista le muestra el mensaje
        // correspondiente. Cajero y gerente entran al panel de administracion
        // (dashboard) como inicio y desde alli abren la caja o generan pedidos.
        if ($rol->claverol === 'MESERO' && ! $request->has('panel')) {
            return redirect()->route('dashboard.generar-pedidos.index', ['idsucursal' => $request->integer('idsucursal')]);
        }

        $sinSucursal = ! $this->empleadoTieneSucursal($empleado);

        $permisos = $sinSucursal
            ? []
            : $this->modulosDisponibles($rol->permisos->where('estado', true)->pluck('codigo'), $empleado);

        $estadisticas = $sinSucursal
            ? []
            : $this->estadisticasPorRol($empleado, $rol);

        return view('dashboard', compact('empleado', 'rol', 'permisos', 'sinSucursal', 'estadisticas'));
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
            $tieneAlguno = empty($modulo['permisos'])
                ? true
                : collect($modulo['permisos'])->intersect($codigos)->isNotEmpty();

            if ($tieneAlguno && isset($modulo['restriccion'])) {
                $tieneAlguno = $this->cumpleRestriccion($modulo['restriccion'], $empleado);
            }

            if ($clave === 'permisos') {
                $tieneAlguno = $empleado->rol->claverol === 'ADMINISTRADOR';
            }

            if ($tieneAlguno) {
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
