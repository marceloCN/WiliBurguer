<?php

namespace Tests\Feature;

use App\Models\AperturaCaja;
use App\Models\Caja;
use App\Models\CuentaUsuario;
use App\Models\Empleado;
use App\Models\Permiso;
use App\Models\Rol;
use App\Models\Sucursal;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    /**
     * Permisos que necesita cada rol para ver, en el panel, los modulos cuyas
     * rutas web.php exigen exactamente esos codigos (middleware permiso:...).
     */
    private const PERMISOS_ADMIN = [
        '50', '102', '150', '151', '200', '201', '202', '208', '302', '312', '317',
        '330', '336', '340', '341', '345', '347', '352', '410', '412', '420', '422',
        '430', '436', '437', '500', '501', '502', '505', '506',
    ];

    private const PERMISOS_CAJERO = [
        '317', '352', '410', '412', '420', '422', '430', '436', '437',
        '500', '501', '502', '505', '506',
    ];

    private const PERMISOS_MESERO = ['500', '501', '502', '505', '506'];

    public function test_invitado_es_redirigido_a_login(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_mesero_con_caja_abierta_es_redirigido_a_generar_pedidos(): void
    {
        $sucursal = $this->crearSucursal();
        $empleado = $this->crearEmpleado($this->crearRol('MESERO', self::PERMISOS_MESERO), $sucursal);
        $caja = Caja::create(['nombre' => 'Caja 1', 'activo' => true, 'idsucursal' => $sucursal->id]);
        $this->crearApertura($caja, $empleado, 'ABIERTA');

        $this->actingAs($this->crearCuenta($empleado))
            ->get(route('dashboard'))
            ->assertRedirect(route('dashboard.generar-pedidos.index', ['idsucursal' => $sucursal->id]));
    }

    public function test_mesero_sin_caja_abierta_ve_el_aviso_en_el_panel(): void
    {
        $sucursal = $this->crearSucursal();
        $empleado = $this->crearEmpleado($this->crearRol('MESERO', self::PERMISOS_MESERO), $sucursal);
        $caja = Caja::create(['nombre' => 'Caja 1', 'activo' => true, 'idsucursal' => $sucursal->id]);
        $this->crearApertura($caja, $empleado, 'CERRADA');

        $respuesta = $this->actingAs($this->crearCuenta($empleado))->get(route('dashboard'));

        $respuesta->assertOk();
        $respuesta->assertSee('Tu sucursal no tiene una caja abierta.');
        $respuesta->assertSee('Generar pedidos');
    }

    public function test_admin_ve_todos_los_modulos_incluido_gestion_de_permisos(): void
    {
        $empleado = $this->crearEmpleado($this->crearRol('ADMINISTRADOR', self::PERMISOS_ADMIN));

        $respuesta = $this->actingAs($this->crearCuenta($empleado))->get(route('dashboard'));

        $respuesta->assertOk();

        foreach (['Gestion de permisos', 'Empleados y cuentas', 'Caja del turno', 'Generar pedidos',
            'Sucursales', 'Configuracion del sitio', 'Bitacora de accesos'] as $titulo) {
            $respuesta->assertSee($titulo);
        }
    }

    public function test_cajero_ve_solo_los_modulos_que_sus_permisos_permiten(): void
    {
        $sucursal = $this->crearSucursal();
        $empleado = $this->crearEmpleado($this->crearRol('CAJERO', self::PERMISOS_CAJERO), $sucursal);

        $respuesta = $this->actingAs($this->crearCuenta($empleado))->get(route('dashboard'));

        $respuesta->assertOk();
        $respuesta->assertSee('Caja del turno');
        $respuesta->assertSee('Generar pedidos');
        $respuesta->assertSee('Productos por sucursal');
        $respuesta->assertDontSee('Gestion de permisos');
        $respuesta->assertDontSee('Empleados y cuentas');
        $respuesta->assertDontSee('Bitacora de accesos');
    }

    public function test_usuario_sin_sucursal_ve_el_mensaje_y_no_ve_menus(): void
    {
        $empleado = $this->crearEmpleado($this->crearRol('GERENTE', self::PERMISOS_ADMIN));

        $respuesta = $this->actingAs($this->crearCuenta($empleado))->get(route('dashboard'));

        $respuesta->assertOk();
        $respuesta->assertSee('No estas añadido en el sistema');
        $respuesta->assertDontSee('Generar pedidos');
        $respuesta->assertDontSee('Gestion de permisos');
        $respuesta->assertDontSee('Resumen del dia');
    }

    public function test_rol_sin_permisos_no_visualiza_ningun_menu(): void
    {
        $sucursal = $this->crearSucursal();
        $empleado = $this->crearEmpleado($this->crearRol('CAJERO', []), $sucursal);

        $respuesta = $this->actingAs($this->crearCuenta($empleado))->get(route('dashboard'));

        $respuesta->assertOk();
        $respuesta->assertSee('Sin modulos habilitados.');
        $respuesta->assertDontSee('Generar pedido');
        $respuesta->assertDontSee('Operacion del dia');
        $respuesta->assertDontSee('Caja del turno');
    }

    // ── Datos de prueba ──────────────────────────────────────────────────────

    private function crearRol(string $claverol, array $codigos = []): Rol
    {
        $rol = Rol::create(['claverol' => $claverol, 'nombrerol' => ucfirst(strtolower($claverol))]);

        foreach ($codigos as $codigo) {
            $permiso = Permiso::firstOrCreate(
                ['codigo' => (string) $codigo],
                ['nombre' => "Permiso {$codigo}", 'estado' => true],
            );
            $rol->permisos()->attach($permiso->id);
        }

        return $rol;
    }

    private function crearSucursal(): Sucursal
    {
        return Sucursal::create([
            'nombre' => 'Sucursal Test',
            'direccion' => 'Av. Test 123',
            'telefono' => '70000000',
            'estado' => true,
        ]);
    }

    private function crearEmpleado(Rol $rol, ?Sucursal $sucursal = null): Empleado
    {
        return Empleado::create([
            'nombre' => 'Test',
            'apellido' => 'Empleado',
            'telefono' => '70000000',
            'estado' => true,
            'fecharegistro' => now(),
            'idrol' => $rol->id,
            'idsucursal' => $sucursal?->id,
        ]);
    }

    private function crearCuenta(Empleado $empleado): CuentaUsuario
    {
        return CuentaUsuario::create([
            'idempleado' => $empleado->id,
            'usuario' => 'user'.$empleado->id,
            'correo' => "user{$empleado->id}@test.local",
            'passwordhash' => Hash::make('secret'),
            'intentosfallidos' => 0,
            'bloqueado' => false,
            'fechacreacion' => now(),
        ]);
    }

    private function crearApertura(Caja $caja, Empleado $empleado, string $estado): AperturaCaja
    {
        return AperturaCaja::create([
            'fechaapertura' => now(),
            'montoinicial' => 0,
            'estado' => $estado,
            'idcaja' => $caja->id,
            'idempleado' => $empleado->id,
        ]);
    }
}
