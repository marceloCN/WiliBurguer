<?php

use App\Http\Controllers\BitacoraAccesoController;
use App\Http\Controllers\BitacoraAccionController;
use App\Http\Controllers\CajaController;
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
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\ProductoSucursalController;
use App\Http\Controllers\PromocionController;
use App\Http\Controllers\RecetaController;
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

        // PRODUCTOS Y CATEGORÍAS
        // Catálogo centralizado de productos, no depende de sucursal: el precio
        // de venta lo fija cada sucursal en productos-sucursal.
        // 300 crear categoría. 301 editar. 302 listar. 305 dar de baja.
        // 310 crear producto. 311 editar. 312 listar. 315 dar de baja.
        Route::prefix('productos')->name('productos.')->group(function () {
            Route::get('/', [ProductoController::class, 'index'])->middleware('permiso:312,302')->name('index');
            Route::post('/', [ProductoController::class, 'store'])->middleware('permiso:310')->name('store');

            // Van antes de {producto}: en Laravel las rutas se resuelven en el
            // orden en que se registran, así que /categorias debe declararse
            // antes o caería dentro de /{producto} y daría 404.
            Route::post('/categorias', [ProductoController::class, 'storeCategoria'])->middleware('permiso:300')->name('categoria.store');
            Route::post('/categorias/{categoria}', [ProductoController::class, 'updateCategoria'])->middleware('permiso:301')->name('categoria.update');
            Route::post('/categorias/{categoria}/estado', [ProductoController::class, 'alternarEstadoCategoria'])->middleware('permiso:305')->name('categoria.estado');

            Route::post('/{producto}', [ProductoController::class, 'update'])->middleware('permiso:311')->name('update');
            Route::post('/{producto}/estado', [ProductoController::class, 'alternarEstado'])->middleware('permiso:315')->name('estado');
        });

        // RECETAS: vinculan cada producto con los insumos y cantidades que
        // necesitan para prepararlo. La baja quita el insumo de la receta
        // detallereceta no tiene columna de estado
        Route::prefix('recetas')->name('recetas.')->group(function () {
            Route::get('/', [RecetaController::class, 'index'])->middleware('permiso:340,341,345')->name('index');
            Route::post('/', [RecetaController::class, 'store'])->middleware('permiso:340')->name('store');
            Route::post('/{detalleReceta}', [RecetaController::class, 'update'])->middleware('permiso:341')->name('update');
            Route::post('/{detalleReceta}/baja', [RecetaController::class, 'destroy'])->middleware('permiso:345')->name('baja');
        });

        // PRODUCTOS POR SUCURSAL
        // Asigna cada producto del catálogo a una sucursal con el precio con el
        // que se vende allí y su disponibilidad. El historial de precios de cada
        // producto se consulta con un botón por fila, en un modal.
        // 316 registrar. 317 listar. 318 editar precio. 319 alternar disponibilidad.
        // 320 ver historial de precios.
        Route::prefix('productos-sucursal')->name('productos-sucursal.')->group(function () {
            Route::get('/', [ProductoSucursalController::class, 'index'])->middleware('permiso:317')->name('index');
            Route::post('/', [ProductoSucursalController::class, 'store'])->middleware('permiso:316')->name('store');
            Route::post('/{productoSucursal}/precio', [ProductoSucursalController::class, 'actualizarPrecio'])->middleware('permiso:318')->name('precio');
            Route::post('/{productoSucursal}/disponibilidad', [ProductoSucursalController::class, 'alternarDisponibilidad'])->middleware('permiso:319')->name('disponibilidad');
            Route::get('/{productoSucursal}/historial', [ProductoSucursalController::class, 'historial'])->middleware('permiso:320')->name('historial');
        });

        // PROMOCIONES POR SUCURSAL
        // Una promoción (nombre y foto) se publica en una sucursal con su tipo
        // de descuento, su vigencia, los días en que aplica y los productos que
        // la componen. La sucursal no se cambia después de crearla.
        // 350 crear. 351 editar. 352 listar (y generar el reporte PDF de
        // productos promocionados). 355 dar de baja.
        Route::prefix('promociones')->name('promociones.')->group(function () {
            Route::get('/', [PromocionController::class, 'index'])->middleware('permiso:352')->name('index');
            Route::get('/reporte', [PromocionController::class, 'reporte'])->middleware('permiso:352')->name('reporte');
            Route::post('/', [PromocionController::class, 'store'])->middleware('permiso:350')->name('store');
            Route::post('/{promocionSucursal}', [PromocionController::class, 'update'])->middleware('permiso:351')->name('update');
            Route::post('/{promocionSucursal}/estado', [PromocionController::class, 'alternarActivo'])->middleware('permiso:355')->name('estado');
        });

        // 500 crear. 501 editar. 502 listar. 505 anular. 506 editar detalle.
        // El index pide todos los permisos del módulo; el registro solo exige crear.
        // Ejecutar (PENDIENTE → EJECUTADO), cobrar (venta, pago y estado
        // COBRADO), anular (solo desde PENDIENTE) y editar el pedido pendiente
        // son acciones separadas: editar, ejecutar, adicionar y cobrar son 501;
        // anular es 505. Ver detalle es de solo lectura y pide lo mismo que el
        // listado, porque está en las cuatro pestañas. Lo cobrado y lo anulado
        // no tienen acciones que modifiquen el pedido.
        Route::prefix('generar-pedidos')->name('generar-pedidos.')->group(function () {
            Route::get('/', [PedidoController::class, 'index'])->middleware('permiso:500,501,502,505,506')->name('index');
            Route::post('/', [PedidoController::class, 'store'])->middleware('permiso:500')->name('store');
            Route::get('/{pedido}/editar', [PedidoController::class, 'editar'])->middleware('permiso:501')->name('editar');
            Route::put('/{pedido}', [PedidoController::class, 'update'])->middleware('permiso:501')->name('update');
            Route::get('/{pedido}/detalle', [PedidoController::class, 'detalle'])->middleware('permiso:500,501,502,505,506')->name('detalle');
            Route::get('/{pedido}/adicionar', [PedidoController::class, 'adicionar'])->middleware('permiso:501')->name('adicionar');
            Route::post('/{pedido}/adicionar', [PedidoController::class, 'adicionarStore'])->middleware('permiso:501')->name('adicionar.store');
            Route::post('/{pedido}/ejecutar', [PedidoController::class, 'ejecutar'])->middleware('permiso:501')->name('ejecutar');
            Route::post('/{pedido}/cobrar', [PedidoController::class, 'cobrar'])->middleware('permiso:501')->name('cobrar');
            Route::post('/{pedido}/anular', [PedidoController::class, 'anular'])->middleware('permiso:505')->name('anular');

            // WizPay: cobro con tarjeta vía pasarela boliviana.
            // init → auth-setup → DDC → process → (3DS step-up) → status.
            Route::prefix('wizpay')->name('wizpay.')->middleware('permiso:501')->group(function () {
                Route::post('/init', [PedidoController::class, 'wizpayInit'])->name('init');
                Route::post('/auth-setup', [PedidoController::class, 'wizpayAuthSetup'])->name('auth-setup');
                Route::post('/process', [PedidoController::class, 'wizpayProcess'])->name('process');
                Route::get('/status/{reference}', [PedidoController::class, 'wizpayStatus'])->name('status');
            });
        });

        // CAJA: apertura y cierre de jornada, movimientos de caja e historial.
        Route::prefix('caja')->name('caja.')->group(function () {
            Route::get('/', [CajaController::class, 'index'])->middleware('permiso:410,412,420,422,430,436,437')->name('index');
            Route::post('/abrir', [CajaController::class, 'store'])->middleware('permiso:410')->name('abrir');
            Route::post('/{apertura}/cerrar', [CajaController::class, 'cerrar'])->middleware('permiso:430')->name('cerrar');
            // 436 → ver detalle del cierre: el PDF de la jornada cerrada.
            Route::get('/{apertura}/reporte', [CajaController::class, 'reporte'])->middleware('permiso:436')->name('reporte');
            Route::post('/movimientos', [CajaController::class, 'movimientoStore'])->middleware('permiso:420')->name('movimiento.store');
            Route::post('/movimientos/{movimiento}/baja', [CajaController::class, 'movimientoBaja'])->middleware('permiso:425')->name('movimiento.baja');
        });

    });
});
