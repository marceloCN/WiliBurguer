<?php

use App\Http\Controllers\BitacoraAccesoController;
use App\Http\Controllers\BitacoraAccionController;
use App\Http\Controllers\ConfiguracionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\InsumoController;
use App\Http\Controllers\InventarioController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\PedidoController;
use App\Http\Controllers\PerfilController;
use App\Http\Controllers\PermisoController;
use App\Http\Controllers\SucursalController;
use App\Http\Controllers\UsuarioController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('inicio');
Route::get('/login', [LoginController::class, 'show'])->name('login');
Route::post('/login', [LoginController::class, 'store'])->middleware('guest')->name('login.store');
Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

Route::middleware('guest')->group(function () {
    Route::get('/forgot-password', [PasswordResetController::class, 'showForgotForm'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLink'])->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'showResetForm'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'reset'])->name('password.store');
});

Route::middleware('auth')->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::prefix('dashboard')->name('dashboard.')->group(function () {

        // configuracion de perfil del empleado
        Route::prefix('mi-perfil')->name('perfil.')->group(function () {
            Route::get('/', [PerfilController::class, 'index'])->name('index');
            Route::post('/', [PerfilController::class, 'update'])->name('update');
        });

        // datos de configuracion del sistema
        Route::prefix('configuracion')->name('configuracion.')->middleware('permiso:50')->group(function () {
            Route::get('/', [ConfiguracionController::class, 'index'])->name('index');
            Route::post('/', [ConfiguracionController::class, 'update'])->name('update');
        });

        // GESTION DE USUARIOS
        // ADMINISTRADOR → todas. GERENTE → solo su sucursal, si este gerente cuenta con una sucursal asignada..
        Route::prefix('usuarios')->name('usuarios.')->group(function () {
            Route::get('/', [UsuarioController::class, 'dashboard'])->middleware('permiso:102')->name('dashboard');
            Route::get('/datos', [UsuarioController::class, 'datos'])->middleware('permiso:102')->name('datos');
            Route::get('/roles', [UsuarioController::class, 'roles'])->middleware('permiso:102')->name('roles');
            Route::get('/seguridad', [UsuarioController::class, 'seguridad'])->middleware('permiso:102')->name('seguridad');
            Route::get('/pdf', [UsuarioController::class, 'pdf'])->middleware('permiso:103')->name('pdf');
            Route::post('/', [UsuarioController::class, 'store'])->middleware('permiso:100')->name('store');
            Route::post('/{cuenta}/estado', [UsuarioController::class, 'alternarEstado'])->middleware('permiso:105,207')->name('estado');
            Route::post('/{cuenta}/actualizar', [UsuarioController::class, 'actualizar'])->middleware('permiso:101')->name('actualizar');
            Route::post('/{cuenta}/rol', [UsuarioController::class, 'cambiarRol'])->middleware('permiso:101')->name('rol');
            Route::post('/{cuenta}/password', [UsuarioController::class, 'reiniciarPassword'])->middleware('permiso:106')->name('password');
        });

        // GESTIÓN DE BITACORA DE ACCESOS (solo administradores)
        Route::prefix('bitacora-accesos')->name('bitacora-accesos.')->middleware('permiso:150')->group(function () {
            Route::get('/', [BitacoraAccesoController::class, 'index'])->name('index');
            Route::get('/pdf', [BitacoraAccesoController::class, 'pdf'])->middleware('permiso:152')->name('pdf');
        });

        // GESTIÓN DE BITACORA DE ACCIONES (solo administradores)
        Route::prefix('bitacora-acciones')->name('bitacora-acciones.')->middleware('permiso:151')->group(function () {
            Route::get('/', [BitacoraAccionController::class, 'index'])->name('index');
            Route::get('/pdf', [BitacoraAccionController::class, 'pdf'])->middleware('permiso:153')->name('pdf');
        });

        // GESTIÓN DE PERMISOS POR ROL (solo administradores)
        Route::prefix('permisos')->name('permisos.')->middleware('admin')->group(function () {
            Route::get('/', [PermisoController::class, 'index'])->name('index');
            Route::post('/{rol}', [PermisoController::class, 'actualizar'])->name('actualizar');
        });

        // SUCURSALES, HORARIOS, MESAS Y CAJAS
        // ADMINISTRADOR → todas. GERENTE → solo su sucursal (y sus horarios).
        Route::prefix('sucursales')->name('sucursales.')->group(function () {
            Route::get('/', [SucursalController::class, 'index'])->middleware('permiso:200,201,202,208')->name('index');
            Route::get('/crear', [SucursalController::class, 'create'])->middleware('permiso:200')->name('crear');
            Route::post('/', [SucursalController::class, 'store'])->middleware('permiso:200')->name('store');
            Route::get('/{sucursal}/pdf', [SucursalController::class, 'pdf'])->middleware('permiso:203')->name('pdf');
            Route::get('/{sucursal}/editar', [SucursalController::class, 'edit'])->middleware('permiso:201,222,212,402')->name('editar');
            Route::post('/{sucursal}', [SucursalController::class, 'update'])->middleware('permiso:201,222,212,402')->name('update');
            Route::post('/{sucursal}/gerente', [SucursalController::class, 'asignarGerente'])->middleware('permiso:204')->name('gerente');
            Route::post('/{sucursal}/estado', [SucursalController::class, 'alternarEstado'])->middleware('permiso:205')->name('estado');

            Route::post('/{sucursal}/horarios', [SucursalController::class, 'horarioStore'])->middleware('permiso:220')->name('horario.store');
            Route::post('/{sucursal}/horarios/{horario}', [SucursalController::class, 'horarioUpdate'])->middleware('permiso:221')->name('horario.update');
            Route::post('/{sucursal}/horarios/{horario}/baja', [SucursalController::class, 'horarioBaja'])->middleware('permiso:225')->name('horario.baja');

            Route::post('/{sucursal}/cajas', [SucursalController::class, 'cajaStore'])->middleware('permiso:400')->name('caja.store');
            Route::post('/{sucursal}/cajas/{caja}', [SucursalController::class, 'cajaUpdate'])->middleware('permiso:401')->name('caja.update');
            Route::post('/{sucursal}/cajas/{caja}/baja', [SucursalController::class, 'cajaBaja'])->middleware('permiso:405')->name('caja.baja');

            Route::post('/{sucursal}/mesas', [SucursalController::class, 'mesaStore'])->middleware('permiso:210')->name('mesa.store');
            Route::post('/{sucursal}/mesas/{mesa}', [SucursalController::class, 'mesaUpdate'])->middleware('permiso:211')->name('mesa.update');
            Route::post('/{sucursal}/mesas/{mesa}/baja', [SucursalController::class, 'mesaBaja'])->middleware('permiso:215')->name('mesa.baja');
        });

        // INVENTARIO Y MOVIMIENTOS DE INVENTARIO
        // 346 → registrar movimientos. 347 → ver inventario. 348 → editar cantidad mínima. 349 → crear PDF de bitácora de insumos.
        // ADMINISTRADOR → cualquier sucursal. GERENTE → sucursales que gerencia.
        Route::prefix('inventario')->name('inventario.')->group(function () {
            Route::get('/', [InventarioController::class, 'index'])->middleware('permiso:347')->name('index');
            Route::get('/insumos/buscar', [InventarioController::class, 'buscarInsumos'])->middleware('permiso:346')->name('insumos.buscar');
            Route::get('/reporte', [InventarioController::class, 'reporte'])->middleware('permiso:349')->name('reporte');
            Route::post('/movimientos', [InventarioController::class, 'store'])->middleware('permiso:346')->name('movimiento.store');
            Route::post('/{inventario}/minimo', [InventarioController::class, 'actualizarMinimo'])->middleware('permiso:348')->name('minimo');
        });

        // INSUMOS Y UNIDADES DE MEDIDA
        // Gestión centralizada, no depende de sucursal. Sin permisos por ahora.
        Route::prefix('insumos')->name('insumos.')->group(function () {
            Route::get('/', [InsumoController::class, 'index'])->middleware('permiso:330,336')->name('index');
            Route::post('/', [InsumoController::class, 'store'])->name('store');
            Route::post('/{insumo}', [InsumoController::class, 'update'])->name('update');
            Route::post('/{insumo}/estado', [InsumoController::class, 'alternarEstado'])->name('estado');

            Route::post('/unidades', [InsumoController::class, 'storeUnidad'])->name('unidad.store');
            Route::post('/unidades/{unidad}', [InsumoController::class, 'updateUnidad'])->name('unidad.update');
            Route::post('/unidades/{unidad}/estado', [InsumoController::class, 'alternarEstadoUnidad'])->name('unidad.estado');

            Route::get('/ayuda/pdf', [InsumoController::class, 'ayudaPdf'])->name('ayuda.pdf');
        });

        Route::prefix('generar-pedidos')->name('generar-pedidos.')->group(function () {
            Route::get('/', [PedidoController::class, 'index'])->middleware('permiso:500,501,502,505,506')->name('index');
        });

    });
});
