<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crea el nuevo esquema de promociones desagregado por sucursal,
     * con dias de la semana y productos vinculados a sucursal_producto.
     */
    public function up(): void
    {
        // Type ENUM para el tipo de beneficio.
        DB::statement("CREATE TYPE tipodescuento AS ENUM ('PORCENTAJE', 'DESCUENTO_MONTO', 'PRECIO_PROMOCIONAL')");

        // Promocion: concepto general (nombre, foto, activo).
        DB::statement('CREATE TABLE promocion ('
            .'id INT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,'
            .'nombre VARCHAR(100) NOT NULL,'
            .'photo VARCHAR(255),'
            .'activo BOOLEAN NOT NULL DEFAULT TRUE'
            .')');

        // Configuracion de la promocion por sucursal.
        DB::statement('CREATE TABLE promocion_sucursal ('
            .'id INT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,'
            .'idpromocion INT NOT NULL,'
            .'idsucursal INT NOT NULL,'
            .'tipodescuento tipodescuento NOT NULL,'
            .'valordescuento DECIMAL(10,2) NOT NULL,'
            .'fechainicio TIMESTAMP NOT NULL,'
            .'fechafin TIMESTAMP NOT NULL,'
            .'activo BOOLEAN NOT NULL DEFAULT TRUE,'
            .'FOREIGN KEY (idpromocion) REFERENCES promocion(id) ON DELETE CASCADE,'
            .'FOREIGN KEY (idsucursal) REFERENCES sucursal(id) ON DELETE CASCADE,'
            .'UNIQUE (idpromocion, idsucursal),'
            .'UNIQUE (id, idsucursal),'
            .'CHECK (valordescuento >= 0),'
            ."CHECK (tipodescuento <> 'PORCENTAJE' OR valordescuento <= 100),"
            .'CHECK (fechafin > fechainicio)'
            .')');

        // Dias de la semana en los que aplica la promocion en una sucursal (1 = lunes, 7 = domingo).
        DB::statement('CREATE TABLE promocion_sucursal_dia ('
            .'idpromocionsucursal INT NOT NULL,'
            .'diasemana SMALLINT NOT NULL,'
            .'PRIMARY KEY (idpromocionsucursal, diasemana),'
            .'FOREIGN KEY (idpromocionsucursal) REFERENCES promocion_sucursal(id) ON DELETE CASCADE,'
            .'CHECK (diasemana BETWEEN 1 AND 7)'
            .')');

        // Se agrega una restriccion UNIQUE compuesta en sucursal_producto para permitir
        // la clave foranea compuesta que garantiza que los productos de una promocion
        // pertenezcan a la misma sucursal.
        DB::statement('ALTER TABLE sucursal_producto ADD CONSTRAINT sucursal_producto_id_idsucursal_unique UNIQUE (id, idsucursal)');

        // Productos incluidos en la promocion, siempre pertenecientes a la misma sucursal.
        DB::statement('CREATE TABLE promocion_sucursal_producto ('
            .'idpromocionsucursal INT NOT NULL,'
            .'idsucursalproducto INT NOT NULL,'
            .'idsucursal INT NOT NULL,'
            .'cantidad_producto INT NOT NULL DEFAULT 1,'
            .'PRIMARY KEY (idpromocionsucursal, idsucursalproducto),'
            .'FOREIGN KEY (idpromocionsucursal, idsucursal) REFERENCES promocion_sucursal (id, idsucursal),'
            .'FOREIGN KEY (idsucursalproducto, idsucursal) REFERENCES sucursal_producto (id, idsucursal),'
            .'CHECK (cantidad_producto > 0)'
            .')');

        // Vincular detallepedido con el nuevo modelo de promocion por sucursal.
        Schema::table('detallepedido', function (Blueprint $table): void {
            $table->foreign('idpromocionsucursal')->references('id')->on('promocion_sucursal');
        });
    }

    /**
     * Elimina el nuevo esquema de promociones.
     */
    public function down(): void
    {
        Schema::table('detallepedido', function (Blueprint $table): void {
            $table->dropForeign(['idpromocionsucursal']);
        });

        DB::statement('DROP TABLE IF EXISTS promocion_sucursal_producto');
        DB::statement('DROP TABLE IF EXISTS promocion_sucursal_dia');
        DB::statement('DROP TABLE IF EXISTS promocion_sucursal');
        DB::statement('DROP TABLE IF EXISTS promocion');
        DB::statement('DROP TYPE IF EXISTS tipodescuento');
        DB::statement('ALTER TABLE sucursal_producto DROP CONSTRAINT IF EXISTS sucursal_producto_id_idsucursal_unique');
    }
};
