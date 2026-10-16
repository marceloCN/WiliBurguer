<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUsuarioRequest;
use App\Models\CuentaUsuario;
use App\Models\Empleado;
use App\Models\Rol;
use App\Models\Sucursal;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

/**
 * Controlador para la gestión de usuarios/empleados del sistema.
 *
 * Administra el dashboard de usuarios, listados, creación, edición,
 * cambio de roles, reinicio de contraseñas y exportación a PDF. Aplica
 * restricciones por rol: los gerentes solo pueden operar dentro de su
 * sucursal y con roles cajero/mesero; los administradores no tienen
 * restricción de sucursal.
 */
class UsuarioController extends Controller
{
    /** Roles que un gerente puede crear o asignar en su sucursal. */
    private const ROLES_GERENTE = ['CAJERO', 'MESERO'];

    /**
     * Muestra el dashboard resumen del módulo de usuarios.
     *
     * Presenta tarjetas con totales de empleados (activos, inactivos,
     * bloqueados) y la información necesaria para abrir el modal de
     * creación. Los gerentes solo ven datos de su propia sucursal.
     */
    public function dashboard(Request $request): View
    {
        $sucursalGerente = $this->contextoSucursalGerente();
        $this->bloquearGerenteSinSucursal($sucursalGerente);

        $queryBase = $this->baseEmpleadosQuery($request, $sucursalGerente);

        $total = (clone $queryBase)->count();
        $activos = (clone $queryBase)->where('estado', true)->count();
        $inactivos = (clone $queryBase)->where('estado', false)->count();
        $bloqueados = (clone $queryBase)
            ->whereHas('cuentaUsuario', fn ($q) => $q->where('bloqueado', true))
            ->count();

        $roles = $this->rolesDisponibles($sucursalGerente);
        $sucursales = $this->sucursalesDisponibles($sucursalGerente);

        return view('admin.usuarios.dashboard', array_merge(
            compact('total', 'activos', 'inactivos', 'bloqueados', 'roles', 'sucursales', 'sucursalGerente'),
            ['permisosUsuario' => $this->codigosPermisoActuales()]
        ));
    }

    /**
     * Muestra el listado de empleados con sus datos personales.
     *
     * Reutiliza la vista de lista genérica filtrando por permisos de rol.
     */
    public function datos(Request $request): View
    {
        return $this->vistaLista($request, 'admin.usuarios.datos');
    }

    /**
     * Muestra el listado de empleados con información de roles.
     *
     * Reutiliza la vista de lista genérica filtrando por permisos de rol.
     */
    public function roles(Request $request): View
    {
        return $this->vistaLista($request, 'admin.usuarios.roles');
    }

    /**
     * Muestra el listado de empleados con información de seguridad.
     *
     * Reutiliza la vista de lista genérica filtrando por permisos de rol.
     */
    public function seguridad(Request $request): View
    {
        return $this->vistaLista($request, 'admin.usuarios.seguridad');
    }

    /**
     * Genera y descarga un PDF con el informe de auditoría de usuarios.
     *
     * Aplica los mismos filtros de búsqueda, rol, estado y sucursal que
     * las vistas de lista. Solo incluye empleados dentro del alcance del
     * usuario logueado.
     */
    public function pdf(Request $request)
    {
        $sucursalGerente = $this->contextoSucursalGerente();
        $this->bloquearGerenteSinSucursal($sucursalGerente);

        $empleados = $this->obtenerEmpleadosFiltrados($request, $sucursalGerente)
            ->orderBy('nombre')
            ->get();

        $filtros = [
            'busqueda' => $request->input('busqueda'),
            'rol' => $request->input('idrol') ? Rol::find($request->input('idrol'))?->nombrerol : null,
            'estado' => match ($request->input('estado')) {
                '1' => 'Activos',
                '0' => 'Inactivos',
                default => null,
            },
            'sucursal' => $request->input('idsucursal') ? Sucursal::find($request->input('idsucursal'))?->nombre : null,
        ];

        $pdf = Pdf::loadView('admin.usuarios.pdf', compact('empleados', 'filtros', 'sucursalGerente'));

        return $pdf->download('informe-auditoria-usuarios.pdf');
    }

    /**
     * Crea un nuevo empleado y su cuenta de usuario.
     *
     * Reglas de negocio:
     * - Gerente: necesita el permiso 206 para asignar el nuevo empleado a
     *   su sucursal. Solo puede crear CAJERO o MESERO; se les asigna
     *   automáticamente la sucursal del gerente y el gerente queda como
     *   jefe directo (idjefe).
     * - Administrador: puede crear cualquier rol. idsucursal e idjefe
     *   quedan vacíos; la asignación de gerentes a sucursales se realiza
     *   en otro módulo.
     */
    public function store(StoreUsuarioRequest $request): RedirectResponse
    {
        $sucursalGerente = $this->contextoSucursalGerente();
        $empleadoCreador = auth()->user()->empleado;
        $esGerente = $empleadoCreador->rol?->claverol === 'GERENTE';

        // Un gerente sin sucursal no puede crear empleados.
        if ($esGerente && $sucursalGerente === null) {
            return back()->with('error', 'Como gerente necesitas tener una sucursal asignada para crear usuarios.');
        }

        // El gerente solo asigna empleados a su sucursal si tiene el permiso 206.
        if ($esGerente && ! $this->codigosPermisoActuales()->contains('206')) {
            return back()->with('error', 'Como gerente necesitas el permiso 206 para asignar empleados a tu sucursal.');
        }

        $validated = $request->validated();

        // Asignación de sucursal y jefe según el rol del creador.
        if ($esGerente) {
            $idsucursal = $sucursalGerente->id;
            $idjefe = $empleadoCreador->id;
        } else {
            $idsucursal = null;
            $idjefe = null;
        }

        $passwordGenerada = 'wili';

        $empleado = Empleado::create([
            'nombre' => $validated['nombre'],
            'apellido' => $validated['apellido'],
            'telefono' => $validated['telefono'],
            'estado' => true,
            'fecharegistro' => now(),
            'idjefe' => $idjefe,
            'idrol' => $validated['idrol'],
            'idsucursal' => $idsucursal,
        ]);

        CuentaUsuario::create([
            'idempleado' => $empleado->id,
            'usuario' => $validated['usuario'],
            'correo' => $validated['correo'],
            'passwordhash' => Hash::make($passwordGenerada),
            'intentosfallidos' => 0,
            'bloqueado' => false,
            'fechacreacion' => now(),
        ]);

        $this->registrarAccion($empleadoCreador->id, 'CREATE', 'empleado', $empleado->id, '100');

        return redirect()->route('dashboard.usuarios.dashboard')
            ->with('exito', "Empleado {$empleado->nombre} {$empleado->apellido} creado. Usuario: {$validated['usuario']} | Contraseña temporal: {$passwordGenerada}");
    }

    /**
     * Activa o desactiva una cuenta de usuario y su empleado asociado.
     *
     * No permite que un usuario desactive su propia cuenta. Valida que el
     * empleado pertenezca al alcance del usuario logueado.
     *
     * Permisos: administrador requiere 105; gerente requiere 207 para
     * operar sobre empleados de su sucursal.
     */
    public function alternarEstado(CuentaUsuario $cuenta): RedirectResponse
    {
        if ($cuenta->id === auth()->id()) {
            return back()->with('error', 'No puedes desactivar tu propia cuenta.');
        }

        $esGerente = auth()->user()->empleado->rol?->claverol === 'GERENTE';
        $permisos = $this->codigosPermisoActuales();

        if ($esGerente && ! $permisos->contains('207')) {
            return back()->with('error', 'Como gerente necesitas el permiso 207 para activar o desactivar empleados de tu sucursal.');
        }

        if (! $esGerente && ! $permisos->contains('105')) {
            return back()->with('error', 'Necesitas el permiso 105 para cambiar el estado de una cuenta.');
        }

        if (! $this->puedeOperarEmpleado($cuenta->empleado)) {
            return back()->with('error', 'Ese empleado no pertenece a tu sucursal.');
        }

        $empleado = $cuenta->empleado;
        $empleado->update(['estado' => ! $empleado->estado]);
        $cuenta->update(['bloqueado' => ! $cuenta->bloqueado]);

        $this->registrarAccion(auth()->user()->empleado->id, 'UPDATE', 'cuentausuario', $cuenta->id, $esGerente ? '207' : '105');

        return back()->with('exito', 'Estado de la cuenta actualizado.');
    }

    /**
     * Actualiza los datos personales y de cuenta de un empleado.
     *
     * Solo permite modificar empleados dentro del alcance del usuario
     * logueado. Registra en bitácora únicamente si hubo cambios reales.
     */
    public function actualizar(CuentaUsuario $cuenta, Request $request): RedirectResponse
    {
        if (! $this->puedeOperarEmpleado($cuenta->empleado)) {
            return back()->with('error', 'Ese empleado no pertenece a tu sucursal.');
        }

        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:100'],
            'apellido' => ['required', 'string', 'max:100'],
            'telefono' => ['nullable', 'string', 'max:15'],
            'usuario' => ['required', 'string', 'max:255', "unique:cuentausuario,usuario,{$cuenta->id}"],
            'correo' => ['required', 'email', 'max:255', "unique:cuentausuario,correo,{$cuenta->id}"],
        ]);

        $empleado = $cuenta->empleado;

        $cambiaEmpleado = $empleado->nombre !== $validated['nombre']
            || $empleado->apellido !== $validated['apellido']
            || ($empleado->telefono ?? '') !== ($validated['telefono'] ?? '');

        $cambiaCuenta = $cuenta->usuario !== $validated['usuario']
            || $cuenta->correo !== $validated['correo'];

        $empleado->update([
            'nombre' => $validated['nombre'],
            'apellido' => $validated['apellido'],
            'telefono' => $validated['telefono'],
        ]);

        $cuenta->update([
            'usuario' => $validated['usuario'],
            'correo' => $validated['correo'],
        ]);

        if ($cambiaEmpleado) {
            $this->registrarAccion(auth()->user()->empleado->id, 'UPDATE', 'empleado', $empleado->id, '101');
        }

        if ($cambiaCuenta) {
            $this->registrarAccion(auth()->user()->empleado->id, 'UPDATE', 'cuentausuario', $cuenta->id, '106');
        }

        return back()->with('exito', 'Datos del empleado actualizados.');
    }

    /**
     * Reinicia la contraseña de una cuenta de usuario.
     *
     * Desbloquea la cuenta y limpia los intentos fallidos. Valida que el
     * empleado pertenezca al alcance del usuario logueado.
     */
    public function reiniciarPassword(CuentaUsuario $cuenta, Request $request): RedirectResponse
    {
        if (! $this->puedeOperarEmpleado($cuenta->empleado)) {
            return back()->with('error', 'Ese empleado no pertenece a tu sucursal.');
        }

        $validated = $request->validate([
            'password' => ['required', 'string', 'min:6'],
        ]);

        $cuenta->update([
            'passwordhash' => Hash::make($validated['password']),
            'intentosfallidos' => 0,
            'bloqueado' => false,
            'bloqueadohasta' => null,
        ]);

        $this->registrarAccion(auth()->user()->empleado->id, 'UPDATE', 'cuentausuario', $cuenta->id, '106');

        return back()->with('exito', 'Contraseña actualizada.');
    }

    /**
     * Cambia el rol de un empleado.
     *
     * No permite que un usuario cambie su propio rol. Valida restricciones
     * de gerente y evita quitar el último rol Administrador. Cuando el
     * cambio involucra el rol GERENTE se limpia idjefe, ya que la
     * asignación de gerentes a sucursales se gestiona en otro módulo.
     */
    public function cambiarRol(CuentaUsuario $cuenta, Request $request): RedirectResponse
    {
        if ($cuenta->id === auth()->id()) {
            return back()->with('error', 'No puedes cambiar tu propio rol.');
        }

        if (! $this->puedeOperarEmpleado($cuenta->empleado)) {
            return back()->with('error', 'Ese empleado no pertenece a tu sucursal.');
        }

        $validated = $request->validate([
            'idrol' => ['required', 'exists:rol,id'],
        ]);

        $sucursalGerente = $this->contextoSucursalGerente();
        $rolesGerente = $this->rolesDisponibles($sucursalGerente)->pluck('id')->all();

        if ($sucursalGerente && ! in_array($validated['idrol'], $rolesGerente)) {
            return back()->with('error', 'Como gerente solo puedes asignar roles cajero o mesero.');
        }

        $rolAnterior = $cuenta->empleado->rol?->claverol;
        $nuevoRol = Rol::find($validated['idrol']);

        // Evita dejar al sistema sin administradores activos.
        if ($rolAnterior === 'ADMINISTRADOR' && $nuevoRol->claverol !== 'ADMINISTRADOR') {
            $administradores = Empleado::where('idrol', $cuenta->empleado->idrol)
                ->where('estado', true)
                ->count();

            if ($administradores <= 1) {
                return back()->with('error', 'No puedes quitar el rol Administrador al último administrador activo.');
            }
        }

        $updates = ['idrol' => $validated['idrol']];

        // Si el cambio involucra el rol GERENTE se limpia el jefe directo.
        // La asignación de gerentes y sus sucursales corresponde a otro módulo.
        if ($rolAnterior === 'GERENTE' || $nuevoRol->claverol === 'GERENTE') {
            $updates['idjefe'] = null;
        }

        $cuenta->empleado->update($updates);

        $this->registrarAccion(auth()->user()->empleado->id, 'UPDATE', 'empleado', $cuenta->empleado->id, '101');

        return back()->with('exito', "Rol de {$cuenta->empleado->nombre} actualizado: {$rolAnterior} → {$nuevoRol->claverol}.");
    }

    /**
     * Renderiza una vista de listado paginada de empleados.
     *
     * Comparte filtros, roles, sucursales y la lista de usuarios existentes
     * para validaciones del front-end. El valor visible del filtro de estado
     * se limita a lo que los permisos 107/108 permiten; la restricción real
     * de filas se aplica en obtenerEmpleadosFiltrados().
     *
     * Los gerentes necesitan además el permiso 208 para acceder a las
     * vistas de datos, roles y seguridad.
     */
    private function vistaLista(Request $request, string $vista): View
    {
        $sucursalGerente = $this->contextoSucursalGerente();
        $this->bloquearGerenteSinSucursal($sucursalGerente);

        $permisosUsuario = $this->codigosPermisoActuales();

        if ($sucursalGerente !== null && ! $permisosUsuario->contains('208')) {
            abort(403, 'Como gerente necesitas el permiso 208 para acceder a esta sección de usuarios.');
        }

        $empleados = $this->obtenerEmpleadosFiltrados($request, $sucursalGerente)
            ->orderBy('nombre')
            ->paginate(10)
            ->withQueryString();

        $roles = $this->rolesDisponibles($sucursalGerente);
        $sucursales = $this->sucursalesDisponibles($sucursalGerente);

        $busqueda = $request->input('busqueda');
        $idrol = $request->input('idrol');
        $idsucursal = $sucursalGerente?->id ?? $request->input('idsucursal');

        $puedeListarActivos = $permisosUsuario->contains('107');
        $puedeListarInactivos = $permisosUsuario->contains('108');

        $estado = ($puedeListarActivos && $puedeListarInactivos)
            ? $request->input('estado')
            : '';

        $usuariosExistentes = CuentaUsuario::query()
            ->pluck('usuario')
            ->map(fn ($usuario) => mb_strtolower(trim($usuario)))
            ->all();

        return view($vista, compact(
            'empleados', 'roles', 'sucursales', 'busqueda', 'idrol', 'estado', 'idsucursal', 'sucursalGerente', 'usuariosExistentes', 'permisosUsuario'
        ));
    }

    /**
     * Construye la consulta base de empleados aplicando filtros y permisos.
     *
     * Para administradores no aplica restricción de sucursal. Para gerentes
     * incluye empleados de su sucursal, sus subordinados directos y su
     * propio registro.
     *
     * El listado por estado se rige por los permisos 107 (activos) y 108
     * (inactivos): con ambos se aplica el filtro solicitado; con solo uno
     * se fuerza ese estado; sin ninguno no se filtra por estado.
     */
    private function obtenerEmpleadosFiltrados(Request $request, ?Sucursal $sucursalGerente)
    {
        $busqueda = $request->input('busqueda');
        $idrol = $request->input('idrol');
        $estado = $request->input('estado');
        $idsucursal = $request->input('idsucursal');

        if ($sucursalGerente) {
            $idsucursal = $sucursalGerente->id;
        }

        $permisos = $this->codigosPermisoActuales();
        $puedeListarActivos = $permisos->contains('107');
        $puedeListarInactivos = $permisos->contains('108');

        $empleadoActual = auth()->user()->empleado;

        return Empleado::with(['rol', 'sucursal', 'cuentaUsuario'])
            ->when($sucursalGerente, function ($query) use ($sucursalGerente, $empleadoActual) {
                // El gerente ve: empleados de su sucursal, sus subordinados
                // directos y él mismo (aunque no tenga idsucursal asignado).
                $query->where(function ($q) use ($sucursalGerente, $empleadoActual) {
                    $q->where('idsucursal', $sucursalGerente->id)
                        ->orWhere('idjefe', $empleadoActual->id)
                        ->orWhere('id', $empleadoActual->id);
                });
            })
            ->when($idsucursal && ! $sucursalGerente, fn ($query) => $query->where('idsucursal', $idsucursal))
            ->when($busqueda, function ($query, string $busqueda) {
                $termino = '%'.mb_strtolower($busqueda).'%';

                $query->where(function ($q) use ($termino) {
                    $q->whereRaw('LOWER(nombre) LIKE ?', [$termino])
                        ->orWhereRaw('LOWER(apellido) LIKE ?', [$termino])
                        ->orWhereRaw('LOWER(telefono) LIKE ?', [$termino])
                        ->orWhereHas('cuentaUsuario', function ($cuenta) use ($termino) {
                            $cuenta->whereRaw('LOWER(usuario) LIKE ?', [$termino])
                                ->orWhereRaw('LOWER(correo) LIKE ?', [$termino]);
                        });
                });
            })
            ->when($idrol, fn ($query) => $query->where('idrol', $idrol))
            ->when($puedeListarActivos && $puedeListarInactivos && $estado === '1', fn ($query) => $query->where('estado', true))
            ->when($puedeListarActivos && $puedeListarInactivos && $estado === '0', fn ($query) => $query->where('estado', false))
            ->when($puedeListarActivos && ! $puedeListarInactivos, fn ($query) => $query->where('estado', true))
            ->when(! $puedeListarActivos && $puedeListarInactivos, fn ($query) => $query->where('estado', false));
    }

    /**
     * Alias interno para compartir la misma consulta filtrada entre
     * el dashboard y las vistas de lista.
     */
    private function baseEmpleadosQuery(Request $request, ?Sucursal $sucursalGerente)
    {
        return $this->obtenerEmpleadosFiltrados($request, $sucursalGerente);
    }

    /**
     * Determina la sucursal asociada a un gerente logueado.
     *
     * Primero busca la sucursal donde el empleado es el gerente oficial
     * (idempleadogerente). Si no existe, usa su idsucursal como fallback.
     * Para otros roles devuelve null.
     */
    private function contextoSucursalGerente(): ?Sucursal
    {
        $empleado = auth()->user()->empleado->load('rol');

        if ($empleado->rol?->claverol !== 'GERENTE') {
            return null;
        }

        $sucursal = Sucursal::where('idempleadogerente', $empleado->id)
            ->where('estado', true)
            ->first();

        if ($sucursal) {
            return $sucursal;
        }

        if ($empleado->idsucursal) {
            return Sucursal::where('id', $empleado->idsucursal)
                ->where('estado', true)
                ->first();
        }

        return null;
    }

    /**
     * Devuelve los roles disponibles según el contexto del usuario.
     *
     * Los gerentes solo pueden ver cajero y mesero. Los administradores
     * ven todos los roles.
     */
    private function rolesDisponibles(?Sucursal $sucursalGerente): Collection
    {
        $roles = Rol::orderBy('nombrerol')->get();

        if ($sucursalGerente) {
            $roles = $roles->whereIn('claverol', self::ROLES_GERENTE)->values();
        }

        return $roles;
    }

    /**
     * Devuelve las sucursales disponibles según el contexto del usuario.
     *
     * Un gerente solo ve su propia sucursal; un administrador ve todas las
     * sucursales activas.
     */
    private function sucursalesDisponibles(?Sucursal $sucursalGerente): Collection
    {
        if ($sucursalGerente instanceof Sucursal) {
            return collect([$sucursalGerente]);
        }

        return Sucursal::where('estado', true)->orderBy('nombre')->get();
    }

    /**
     * Determina si el usuario logueado puede operar sobre un empleado.
     *
     * Los administradores tienen acceso total. Los gerentes solo pueden
     * operar empleados de su sucursal, sus subordinados directos y su
     * propia cuenta.
     */
    private function puedeOperarEmpleado(Empleado $empleado): bool
    {
        $sucursalGerente = $this->contextoSucursalGerente();
        $empleadoActual = auth()->user()->empleado;

        if ($empleadoActual->rol?->claverol === 'GERENTE' && $sucursalGerente === null) {
            return false;
        }

        if ($sucursalGerente === null) {
            return true;
        }

        return $empleado->idsucursal === $sucursalGerente->id
            || $empleado->idjefe === $empleadoActual->id
            || $empleado->id === $empleadoActual->id;
    }

    /**
     * Aborta la petición si un gerente no tiene sucursal asignada.
     *
     * Se usa en los métodos que requieren contexto de sucursal para
     * filtrar u operar empleados.
     */
    private function bloquearGerenteSinSucursal(?Sucursal $sucursalGerente): void
    {
        if (auth()->user()->empleado->rol?->claverol === 'GERENTE' && $sucursalGerente === null) {
            abort(403, 'Como gerente necesitas tener una sucursal asignada para gestionar usuarios.');
        }
    }
}
