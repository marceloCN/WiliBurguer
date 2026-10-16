<?php

namespace App\Http\Controllers;

use App\Models\Caja;
use App\Models\Empleado;
use App\Models\HorarioSucursal;
use App\Models\Mesa;
use App\Models\Sucursal;
use Barryvdh\DomPDF\Facade\Pdf;
use DantSu\OpenStreetMapStaticAPI\Circle;
use DantSu\OpenStreetMapStaticAPI\LatLng;
use DantSu\OpenStreetMapStaticAPI\OpenStreetMap;
use DantSu\OpenStreetMapStaticAPI\TileLayer;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Throwable;

class SucursalController extends Controller
{
    public function index(): View
    {
        $esGerente = $this->esGerente();
        $esAdmin = $this->esAdmin();

        $sucursales = Sucursal::with(['gerente', 'horarios', 'empleados.rol'])
            ->when($esGerente, function ($query) {
                $query->where('idempleadogerente', auth()->user()->empleado->id);
            })
            ->orderBy('nombre')
            ->get();

        $sucursalGerenteInactiva = false;

        if ($esGerente) {
            $sucursalGerente = $sucursales->first();
            $sucursalGerenteInactiva = $sucursalGerente !== null && ! $sucursalGerente->estado;
            $sucursales = $sucursalGerenteInactiva ? collect() : $sucursales;
        }

        $totalSucursales = $sucursales->count();
        $totalEmpleados = $sucursales->sum(fn ($s) => $s->empleados->count());

        $permisosUsuario = $this->codigosPermisoActuales();

        return view('admin.sucursales.index', compact(
            'sucursales',
            'totalSucursales',
            'totalEmpleados',
            'permisosUsuario',
            'esAdmin',
            'esGerente',
            'sucursalGerenteInactiva',
        ));
    }

    /**
     * Alterna el estado de una sucursal (baja / reactivación).
     *
     * El administrador puede hacer ambas operaciones. El gerente solo puede
     * desactivar su propia sucursal; al hacerlo pierde el módulo y vuelve
     * al dashboard. La reactivación queda exclusiva del administrador.
     */
    public function alternarEstado(Sucursal $sucursal): RedirectResponse
    {
        $this->autorizarSucursal($sucursal);

        if (! $this->codigosPermisoActuales()->contains('205')) {
            return back()->with('error', 'Necesitas el permiso 205 para cambiar el estado de la sucursal.');
        }

        $esGerente = $this->esGerente();

        if ($esGerente && ! $sucursal->estado) {
            return back()->with('error', 'Solo el administrador puede reactivar una sucursal. Contacta al administrador del sistema.');
        }

        $sucursal->update(['estado' => ! $sucursal->estado]);

        $this->registrarAccion(auth()->user()->empleado->id, 'UPDATE', 'sucursal', $sucursal->id, '205');

        if ($esGerente) {
            return redirect()->route('dashboard')->with(
                'exito',
                'Sucursal dada de baja. Tu acceso al módulo de sucursales queda suspendido. Solo el administrador puede reactivarla.'
            );
        }

        return back()->with(
            'exito',
            $sucursal->estado
                ? 'Sucursal reactivada correctamente.'
                : 'Sucursal dada de baja correctamente.'
        );
    }

    /**
     * Genera y descarga un PDF con la ficha completa de la sucursal:
     * datos, ubicación, horarios, mesas, cajas y empleados.
     */
    public function pdf(Sucursal $sucursal)
    {
        $this->autorizarSucursal($sucursal);

        $sucursal->load(['gerente', 'horarios', 'mesas', 'cajas', 'empleados.rol']);

        $pdf = Pdf::loadView('admin.sucursales.pdf', compact('sucursal'));

        return $pdf->download('ficha-sucursal-'.mb_strtolower(str_replace(' ', '-', $sucursal->nombre)).'.pdf');
    }

    /**
     * Genera una imagen estática del mapa con la ubicación de la sucursal.
     * Devuelve la imagen codificada en base64 o null si no se puede generar.
     */
    private function generarMapaSucursal(Sucursal $sucursal): ?string
    {
        try {
            $this->prepararVariablesServidorParaCurl();

            $centro = new LatLng((float) $sucursal->latitud, (float) $sucursal->longitud);

            $mapa = new OpenStreetMap(
                $centro,
                15,
                600,
                220,
                $this->capaDeTilesOpenStreetMap()
            );
            $mapa->addDraw(
                (new Circle($centro, '#8b5a2b', 2, '#c49a6cAA'))
                    ->setRadius(80)
            );

            $nombreArchivo = 'mapa-sucursal-'.$sucursal->id.'-'.time().'.png';
            $rutaRelativa = 'temp_mapas/'.$nombreArchivo;

            Storage::disk('public')->makeDirectory('temp_mapas');
            Storage::disk('public')->put($rutaRelativa, $mapa->getImage()->getDataPNG());

            return storage_path('app/public/'.$rutaRelativa);
        } catch (Throwable $e) {
            return null;
        }
    }

    /**
     * Asegura que las variables de servidor requeridas por la librería de
     * imágenes estén definidas cuando se ejecuta fuera de un contexto web.
     */
    private function prepararVariablesServidorParaCurl(): void
    {
        if (empty($_SERVER['REQUEST_SCHEME']) || empty($_SERVER['HTTP_HOST']) || empty($_SERVER['REQUEST_URI'])) {
            $appUrl = parse_url(config('app.url', 'http://localhost'));
            $_SERVER['REQUEST_SCHEME'] = $appUrl['scheme'] ?? 'http';
            $_SERVER['HTTP_HOST'] = $appUrl['host'] ?? 'localhost';
            $_SERVER['REQUEST_URI'] = $appUrl['path'] ?? '/';
        }
    }

    /**
     * Crea la capa de tiles de OpenStreetMap con opciones cURL configurables
     * para cumplir con las políticas del proveedor de tiles.
     */
    private function capaDeTilesOpenStreetMap(): TileLayer
    {
        $referer = config('services.map_tile_referer', config('app.url'));
        $verificarSsl = config('services.map_tile_verify_ssl', true);

        return new TileLayer(
            'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
            '© OpenStreetMap contributors',
            'abc',
            [
                CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; LaravelApp/1.0; +'.($referer ?: 'https://example.com/').')',
                CURLOPT_REFERER => $referer ?: 'https://example.com/',
                CURLOPT_TIMEOUT => 10,
                CURLOPT_SSL_VERIFYPEER => (bool) $verificarSsl,
            ],
            false
        );
    }

    public function create(): View
    {
        return view('admin.sucursales.create', [
            'gerentesDisponibles' => $this->gerentesDisponibles(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'direccion' => ['required', 'string', 'max:255'],
            'telefono' => ['required', 'string', 'max:15'],
            'latitud' => ['required', 'numeric', 'between:-90,90'],
            'longitud' => ['required', 'numeric', 'between:-180,180'],
            'idempleadogerente' => ['nullable', 'exists:empleado,id'],
        ]);

        if (! empty($validated['idempleadogerente'])) {
            $this->asegurarGerente($validated['idempleadogerente']);
        }

        $sucursal = Sucursal::create([
            'nombre' => $validated['nombre'],
            'direccion' => $validated['direccion'],
            'telefono' => $validated['telefono'],
            'latitud' => $validated['latitud'],
            'longitud' => $validated['longitud'],
            'estado' => true,
            'idempleadogerente' => $validated['idempleadogerente'] ?? null,
        ]);

        $this->registrarAccion(auth()->user()->empleado->id, 'CREATE', 'sucursal', $sucursal->id, '200');

        if (! empty($validated['idempleadogerente'])) {
            $this->registrarAccion(auth()->user()->empleado->id, 'UPDATE', 'sucursal', $sucursal->id, '204');
        }

        return redirect()
            ->route('dashboard.sucursales.editar', $sucursal)
            ->with('exito', 'Sucursal creada correctamente.');
    }

    public function edit(Sucursal $sucursal): View
    {
        $this->autorizarSucursal($sucursal);

        $sucursal->load(['gerente', 'horarios', 'mesas', 'cajas']);

        $codigos = $this->codigosPermisoActuales();

        return view('admin.sucursales.editar', [
            'sucursal' => $sucursal,
            'gerentesDisponibles' => $this->gerentesDisponibles(),
            'esAdmin' => $this->esAdmin(),
            'puedeGestionarMesas' => $codigos->intersect(['210', '211', '215'])->isNotEmpty(),
            'puedeAperturaCaja' => $codigos->intersect(['400', '401', '402', '405', '410', '411', '412', '415'])->isNotEmpty(),
            'puedeGestionarCajas' => $codigos->intersect(['400', '401', '405'])->isNotEmpty(),
        ]);
    }

    public function asignarGerente(Request $request, Sucursal $sucursal): RedirectResponse
    {
        $this->autorizarSucursal($sucursal);

        $validated = $request->validate([
            'idempleadogerente' => ['nullable', 'exists:empleado,id'],
        ]);

        if (! empty($validated['idempleadogerente'])) {
            $this->asegurarGerente($validated['idempleadogerente']);
        }

        $jefeAnterior = $sucursal->idempleadogerente;
        $jefeNuevo = $validated['idempleadogerente'] ?? null;

        if ($jefeAnterior !== $jefeNuevo) {
            $this->reasignarSubordinados($jefeAnterior, $jefeNuevo, $sucursal->id);
        }

        $sucursal->update([
            'idempleadogerente' => $jefeNuevo,
        ]);

        // El gerente asignado también queda registrado como empleado de la sucursal.
        if ($jefeNuevo !== null) {
            Empleado::where('id', $jefeNuevo)
                ->update(['idsucursal' => $sucursal->id]);
        }

        $this->registrarAccion(auth()->user()->empleado->id, 'UPDATE', 'sucursal', $sucursal->id, '204');

        return back()->with('exito', 'Gerente asignado correctamente.');
    }

    public function update(Request $request, Sucursal $sucursal): RedirectResponse
    {
        $this->autorizarSucursal($sucursal);

        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'direccion' => ['required', 'string', 'max:255'],
            'telefono' => ['required', 'string', 'max:15'],
            'latitud' => ['required', 'numeric', 'between:-90,90'],
            'longitud' => ['required', 'numeric', 'between:-180,180'],
        ]);

        $sucursal->update($validated);

        $this->registrarAccion(auth()->user()->empleado->id, 'UPDATE', 'sucursal', $sucursal->id, '201');

        return back()->with('exito', 'Sucursal actualizada correctamente.');
    }

    public function horarioStore(Request $request, Sucursal $sucursal): RedirectResponse
    {
        $this->autorizarSucursal($sucursal);

        $validated = $request->validate([
            'diasemana' => ['required', 'integer', 'between:1,7'],
            'horaapertura' => ['required', 'date_format:H:i'],
            'horacierre' => ['required', 'date_format:H:i', 'after:horaapertura'],
            'activo' => ['nullable', 'boolean'],
        ]);

        $existe = HorarioSucursal::where('idsucursal', $sucursal->id)
            ->where('diasemana', $validated['diasemana'])
            ->exists();

        if ($existe) {
            return back()->with('error', 'Ya existe un horario para ese día.');
        }

        $horario = HorarioSucursal::create([
            'idsucursal' => $sucursal->id,
            'diasemana' => $validated['diasemana'],
            'horaapertura' => $validated['horaapertura'].':00',
            'horacierre' => $validated['horacierre'].':00',
            'activo' => $request->boolean('activo'),
        ]);

        $this->registrarAccion(auth()->user()->empleado->id, 'CREATE', 'horariosucursal', $horario->idhorariosucursal, '220');

        return back()->with('exito', 'Horario agregado.');
    }

    public function horarioUpdate(Request $request, Sucursal $sucursal, HorarioSucursal $horario): RedirectResponse
    {
        $this->autorizarSucursal($horario->sucursal);

        $validated = $request->validate([
            'diasemana' => ['required', 'integer', 'between:1,7'],
            'horaapertura' => ['required', 'date_format:H:i'],
            'horacierre' => ['required', 'date_format:H:i', 'after:horaapertura'],
            'activo' => ['nullable', 'boolean'],
        ]);

        $duplicado = HorarioSucursal::where('idsucursal', $horario->idsucursal)
            ->where('diasemana', $validated['diasemana'])
            ->where('idhorariosucursal', '!=', $horario->idhorariosucursal)
            ->exists();

        if ($duplicado) {
            return back()->with('error', 'Ya existe un horario para ese día.');
        }

        $horario->update([
            'diasemana' => $validated['diasemana'],
            'horaapertura' => $validated['horaapertura'].':00',
            'horacierre' => $validated['horacierre'].':00',
            'activo' => $request->boolean('activo'),
        ]);

        $this->registrarAccion(auth()->user()->empleado->id, 'UPDATE', 'horariosucursal', $horario->idhorariosucursal, '221');

        return back()->with('exito', 'Horario actualizado.');
    }

    public function horarioBaja(Sucursal $sucursal, HorarioSucursal $horario): RedirectResponse
    {
        $this->autorizarSucursal($horario->sucursal);

        $horario->delete();

        $this->registrarAccion(auth()->user()->empleado->id, 'DELETE', 'horariosucursal', $horario->idhorariosucursal, '225');

        return back()->with('exito', 'Horario eliminado.');
    }

    public function cajaStore(Request $request, Sucursal $sucursal): RedirectResponse
    {
        $this->autorizarSucursal($sucursal);

        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:100'],
            'activo' => ['nullable', 'boolean'],
        ]);

        try {
            $id = DB::selectOne('SELECT registrar_caja(?, ?, ?) AS id', [$sucursal->id, $validated['nombre'], $request->boolean('activo')])->id;
        } catch (QueryException $e) {
            return back()->with('error', $this->mensajeErrorProcedimiento($e));
        }

        $this->registrarAccion(auth()->user()->empleado->id, 'CREATE', 'caja', $id, '400');

        return back()->with('exito', "Caja {$validated['nombre']} registrada.");
    }

    public function cajaUpdate(Request $request, Sucursal $sucursal, Caja $caja): RedirectResponse
    {
        abort_unless($caja->idsucursal === $sucursal->id, 403, 'La caja no pertenece a esta sucursal.');

        $this->autorizarSucursal($sucursal);

        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:100'],
            'activo' => ['nullable', 'boolean'],
        ]);

        try {
            DB::statement('SELECT actualizar_caja(?, ?, ?, ?)', [$caja->id,  $caja->idsucursal, $validated['nombre'], $request->boolean('activo')]);
        } catch (QueryException $e) {
            return back()->with('error', $this->mensajeErrorProcedimiento($e));
        }

        $this->registrarAccion(auth()->user()->empleado->id, 'UPDATE', 'caja', $caja->id, '401');

        return back()->with('exito', "Caja {$validated['nombre']} actualizada.");
    }

    public function cajaBaja(Sucursal $sucursal, Caja $caja): RedirectResponse
    {
        abort_unless($caja->idsucursal === $sucursal->id, 403, 'La caja no pertenece a esta sucursal.');

        $this->autorizarSucursal($sucursal);
        // la misma funcion genera la baja y actualiza la bitacora de accion
        DB::statement('CALL dar_baja_caja(?, ?, ?, ?, ?)', [$caja->id, auth()->user()->empleado->id, '405', request()->userAgent(), request()->ip()]);

        return back()->with('exito', "Caja {$caja->nombre} dada de baja.");
    }

    public function mesaStore(Request $request, Sucursal $sucursal): RedirectResponse
    {
        $this->autorizarSucursal($sucursal);

        $validated = $request->validate([
            'numeromesa' => ['required', 'integer', 'min:1'],
            'capacidad' => ['required', 'integer', 'min:1'],
        ]);

        try {
            DB::statement('CALL registrar_mesa(?, ?, ?, ?, ?, ?, ?, ?)', [$sucursal->id, $validated['numeromesa'], $validated['capacidad'],
                $request->boolean('estado', true), auth()->user()->empleado->id, '210', request()->userAgent(), request()->ip(), ]);
        } catch (QueryException $e) {
            return back()->withInput()->with('error', $this->mensajeErrorProcedimiento($e));
        }

        return back()->with('exito', "Mesa {$validated['numeromesa']} registrada.");
    }

    public function mesaUpdate(Request $request, Sucursal $sucursal, Mesa $mesa): RedirectResponse
    {
        abort_unless($mesa->idsucursal === $sucursal->id, 403, 'La mesa no pertenece a esta sucursal.');

        $this->autorizarSucursal($sucursal);

        $validated = $request->validate([
            'numeromesa' => ['required', 'integer', 'min:1'],
            'capacidad' => ['required', 'integer', 'min:1'],
            'estado' => ['nullable', 'boolean'],
        ]);

        try {
            DB::statement('CALL actualizar_mesa(?, ?, ?, ?, ?, ?, ?, ?, ?)', [$mesa->id, $mesa->idsucursal, $validated['numeromesa'],
                $validated['capacidad'], $request->boolean('estado'), auth()->user()->empleado->id, '211', request()->userAgent(), request()->ip(), ]);
        } catch (QueryException $e) {
            return back()->with('error', $this->mensajeErrorProcedimiento($e));
        }

        return back()->with('exito', "Mesa {$validated['numeromesa']} actualizada.");
    }

    public function mesaBaja(Sucursal $sucursal, Mesa $mesa): RedirectResponse
    {
        abort_unless($mesa->idsucursal === $sucursal->id, 403, 'La mesa no pertenece a esta sucursal.');
        $this->autorizarSucursal($sucursal);
        DB::statement('CALL dar_baja_mesa(?, ?, ?, ?, ?)', [$mesa->id, auth()->user()->empleado->id, '215', request()->userAgent(), request()->ip()]);

        return back()->with('exito', "Mesa {$mesa->numeromesa} dada de baja.");
    }

    /**
     * Reasigna la cadena de mando cuando el gerente de una sucursal cambia:
     * los cajeros/meseros del antiguo gerente pasan a depender del nuevo,
     * y el antiguo gerente pasa a depender del nuevo gerente.
     */
    private function reasignarSubordinados(?int $jefeAnterior, ?int $jefeNuevo, ?int $sucursalId): void
    {
        DB::statement('CALL reasignar_subordinados(?, ?, ?)', [$jefeAnterior, $jefeNuevo, $sucursalId]);
    }

    private function esGerente(): bool
    {
        return auth()->user()->empleado->rol?->claverol === 'GERENTE';
    }

    private function esAdmin(): bool
    {
        return auth()->user()->empleado->rol?->claverol === 'ADMINISTRADOR';
    }

    /**
     * Gerentes activos aún no asignados como gerente de otra sucursal activa.
     */
    private function gerentesDisponibles(): Collection
    {
        $asignados = Sucursal::where('estado', true)
            ->whereNotNull('idempleadogerente')
            ->pluck('idempleadogerente');

        return Empleado::with('rol')
            ->where('estado', true)
            ->whereHas('rol', fn ($q) => $q->where('claverol', 'GERENTE'))
            ->whereNotIn('id', $asignados)
            ->orderBy('nombre')
            ->get();
    }

    private function asegurarGerente(int $idEmpleado): void
    {
        $gerente = Empleado::with('rol')
            ->where('id', $idEmpleado)
            ->where('estado', true)
            ->whereHas('rol', fn ($q) => $q->where('claverol', 'GERENTE'))
            ->first();

        abort_unless($gerente, 422, 'Solo puedes asignar un gerente activo del sistema.');
    }

    private function autorizarSucursal(Sucursal $sucursal): void
    {
        if ($this->esGerente() && $sucursal->idempleadogerente !== auth()->user()->empleado->id) {
            abort(403, 'Solo puedes gestionar la sucursal que te fue asignada.');
        }
    }

    /**
     * Extrae el mensaje legible de un RAISE EXCEPTION lanzado por un procedimiento
     * o función almacenada de PostgreSQL, ignorando el prefijo SQLSTATE de la excepción.
     */
    private function mensajeErrorProcedimiento(QueryException $e): string
    {
        if (preg_match('/ERROR:\s+(.+)/', $e->getMessage(), $m)) {
            return trim($m[1]);
        }

        return 'No se pudo completar la operación solicitada.';
    }
}
