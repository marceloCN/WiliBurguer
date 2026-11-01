<?php

namespace Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Schema;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // El esquema de la aplicacion vive en base_de_datos.sql (pgsql) y las
        // migraciones existentes asumen Postgres, por lo que las tablas que
        // necesitan las pruebas se crean aqui, sobre la sqlite en memoria,
        // sin tocar migraciones ni la base de produccion.
        $this->crearEsquemaDePrueba();
    }

    /**
     * Crea las tablas minimas que las vistas y controladores consultan en los
     * tests. Cada prueba arranca con una sqlite en memoria nueva, por lo que
     * se dropea y vuelve a crear sin riesgo.
     */
    protected function crearEsquemaDePrueba(): void
    {
        $tablas = [
            'configuracion_sistema' => function (Blueprint $table) {
                $table->id();
                $table->string('clave', 100);
                $table->text('valor');
                $table->string('descripcion')->nullable();
                $table->boolean('estado')->default(true);
            },
            'rol' => function (Blueprint $table) {
                $table->id();
                $table->string('claverol', 50);
                $table->string('nombrerol', 50);
            },
            'permiso' => function (Blueprint $table) {
                $table->id();
                $table->string('codigo', 100);
                $table->string('nombre', 100);
                $table->boolean('estado')->default(true);
            },
            'rolpermiso' => function (Blueprint $table) {
                $table->integer('idrol');
                $table->integer('idpermiso');
            },
            'empleado' => function (Blueprint $table) {
                $table->id();
                $table->string('nombre', 100);
                $table->string('apellido', 100);
                $table->string('telefono', 15)->nullable();
                $table->boolean('estado')->default(true);
                $table->timestamp('fecharegistro')->nullable();
                $table->integer('idjefe')->nullable();
                $table->integer('idrol');
                $table->integer('idsucursal')->nullable();
            },
            'sucursal' => function (Blueprint $table) {
                $table->id();
                $table->string('nombre', 100);
                $table->string('direccion', 200)->nullable();
                $table->string('telefono', 15)->nullable();
                $table->decimal('latitud', 10, 8)->nullable();
                $table->decimal('longitud', 11, 6)->nullable();
                $table->boolean('estado')->default(true);
                $table->integer('idempleadogerente')->nullable();
            },
            'caja' => function (Blueprint $table) {
                $table->id();
                $table->string('nombre', 100);
                $table->boolean('activo')->default(true);
                $table->integer('idsucursal');
            },
            'aperturacaja' => function (Blueprint $table) {
                $table->id();
                $table->timestamp('fechaapertura')->nullable();
                $table->decimal('montoinicial', 10, 2)->default(0);
                $table->string('estado', 20);
                $table->integer('idcaja');
                $table->integer('idempleado');
            },
            'cuentausuario' => function (Blueprint $table) {
                $table->id();
                $table->integer('idempleado');
                $table->string('usuario', 50);
                $table->string('correo', 150);
                $table->string('passwordhash');
                $table->integer('intentosfallidos')->default(0);
                $table->boolean('bloqueado')->default(false);
                $table->timestamp('bloqueadohasta')->nullable();
                $table->timestamp('fechacreacion')->nullable();
                $table->timestamp('ultimoacceso')->nullable();
                $table->string('remember_token', 100)->nullable();
            },
            'categoria' => function (Blueprint $table) {
                $table->id();
                $table->string('nombre');
                $table->boolean('estado')->default(true);
            },
            'producto' => function (Blueprint $table) {
                $table->id();
                $table->string('nombre');
                $table->string('descripcion', 200)->nullable();
                $table->string('photo')->nullable();
                $table->boolean('estado')->default(true);
                $table->integer('idcategoria')->nullable();
            },
            'horariosucursal' => function (Blueprint $table) {
                $table->increments('idhorariosucursal');
                $table->integer('idsucursal');
                $table->time('horaapertura')->nullable();
                $table->time('horacierre')->nullable();
                $table->boolean('activo')->default(true);
                $table->integer('diasemana')->nullable();
            },
            'sucursal_producto' => function (Blueprint $table) {
                $table->integer('idsucursal');
                $table->integer('idproducto');
                $table->decimal('precioproducto', 10, 2)->default(0);
                $table->boolean('disponibilidad')->default(true);
            },
            'pedido' => function (Blueprint $table) {
                $table->id();
                $table->integer('nropedido');
                $table->timestamp('fecha')->nullable();
                $table->decimal('total', 10, 2)->default(0);
                $table->string('estadopedido', 20);
                $table->string('tipopedido', 20)->nullable();
                $table->integer('idsucursal');
                $table->integer('idempleado');
                $table->integer('idmesa')->nullable();
                $table->decimal('subtotalpedido', 10, 2)->nullable();
                $table->decimal('descuentopedido', 10, 2)->nullable();
                $table->timestamp('horarecogida')->nullable();
                $table->text('observaciones')->nullable();
                $table->integer('idaperturacaja')->nullable();
            },
            'venta' => function (Blueprint $table) {
                $table->id();
                $table->timestamp('fechaventa')->nullable();
                $table->decimal('totalventa', 10, 2)->default(0);
                $table->string('estadoventa', 20)->nullable();
                $table->integer('idempleado');
                $table->integer('idpedido')->nullable();
                $table->integer('nrocuenta')->nullable();
            },
            'mesa' => function (Blueprint $table) {
                $table->id();
                $table->integer('numeromesa');
                $table->integer('capacidad')->default(4);
                $table->boolean('estado')->default(true);
                $table->integer('idsucursal');
            },
            'insumo' => function (Blueprint $table) {
                $table->id();
                $table->string('nombre', 100);
                $table->string('descripcion', 255)->nullable();
                $table->integer('idunidadmedida')->nullable();
                $table->boolean('estado')->default(true);
            },
            'unidadmedida' => function (Blueprint $table) {
                $table->id();
                $table->string('nombre', 50);
                $table->string('abreviatura', 10)->nullable();
                $table->string('tipo', 20)->nullable();
                $table->boolean('estado')->default(true);
            },
        ];

        foreach ($tablas as $nombre => $definicion) {
            Schema::dropIfExists($nombre);
            Schema::create($nombre, $definicion);
        }
    }
}
