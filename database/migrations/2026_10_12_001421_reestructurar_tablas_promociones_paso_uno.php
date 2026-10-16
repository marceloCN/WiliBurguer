<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Elimina el esquema antiguo de promociones, los registros de detallepedido
     * que lo referenciaban, y prepara la tabla detallepedido para el nuevo modelo.
     *
     * Nota: esta migracion es destructiva para los datos historicos de promociones.
     * Los rollback no pueden recuperar los datos eliminados.
     */
    public function up(): void
    {
        // 1. Eliminar los detalles de pedido que usaban promociones del modelo anterior.
        DB::statement('DELETE FROM detallepedido WHERE idpromocion IS NOT NULL');

        // 2. Quitar la FK y el CHECK que dependian de la columna idpromocion.
        DB::statement('ALTER TABLE detallepedido DROP CONSTRAINT IF EXISTS fk_detallepedido_promocion');
        DB::statement('ALTER TABLE detallepedido DROP CONSTRAINT IF EXISTS chk_detallepedido_item');

        // 3. Eliminar la columna idpromocion.
        Schema::table('detallepedido', function (Blueprint $table): void {
            $table->dropColumn('idpromocion');
        });

        // 4. Eliminar las tablas antiguas de promociones.
        DB::statement('DROP TABLE IF EXISTS promocion_sucursal_producto');
        DB::statement('DROP TABLE IF EXISTS promocion');

        // 5. Eliminar el type ENUM antiguo.
        DB::statement('DROP TYPE IF EXISTS tipodescuento');

        // 6. Agregar la nueva columna que apuntara a promocion_sucursal.
        //    La FK se creara en la siguiente migracion, una vez exista la tabla.
        Schema::table('detallepedido', function (Blueprint $table): void {
            $table->integer('idpromocionsucursal')->nullable()->after('idproductosucursal');
        });

        // 7. Restaurar el CHECK de integridad con la nueva columna.
        DB::statement('ALTER TABLE detallepedido ADD CONSTRAINT chk_detallepedido_item CHECK (idproductosucursal IS NOT NULL OR idpromocionsucursal IS NOT NULL)');
    }

    /**
     * Revierte los cambios recreando el esquema antiguo de promociones.
     *
     * Nota: los datos eliminados no se recuperan.
     */
    public function down(): void
    {
        // 1. Quitar el CHECK que usa idpromocionsucursal.
        DB::statement('ALTER TABLE detallepedido DROP CONSTRAINT IF EXISTS chk_detallepedido_item');

        // 2. Eliminar la columna idpromocionsucursal.
        Schema::table('detallepedido', function (Blueprint $table): void {
            $table->dropColumn('idpromocionsucursal');
        });

        // 3. Recrear el type ENUM antiguo.
        DB::statement("CREATE TYPE tipodescuento AS ENUM ('PORCENTAJE', 'MONTO_FIJO')");

        // 4. Recrear la tabla promocion antigua.
        DB::statement('CREATE TABLE promocion ('
            .'id INT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,'
            .'nombre VARCHAR(100) NOT NULL,'
            ."tipodescuento tipodescuento DEFAULT 'MONTO_FIJO' NOT NULL,"
            .'valordescuento DECIMAL(10,2) NOT NULL,'
            .'fechainicio TIMESTAMP NOT NULL,'
            .'fechafin TIMESTAMP NOT NULL,'
            .'activo BOOLEAN DEFAULT TRUE NOT NULL,'
            .'photo VARCHAR(255),'
            .'CONSTRAINT promocion_check CHECK (fechafin > fechainicio),'
            ."CONSTRAINT promocion_check1 CHECK ((tipodescuento = 'MONTO_FIJO'::tipodescuento) OR (valordescuento <= 100)),"
            .'CONSTRAINT promocion_valordescuento_check CHECK (valordescuento >= 0)'
            .')');

        // 5. Recrear la tabla intermedia antigua.
        DB::statement('CREATE TABLE promocion_sucursal_producto ('
            .'idpromocion INT NOT NULL,'
            .'idsucursalproducto INT NOT NULL,'
            .'cantidad_producto INT DEFAULT 1 NOT NULL,'
            .'CONSTRAINT promocion_sucursal_producto_pkey PRIMARY KEY (idpromocion, idsucursalproducto),'
            .'CONSTRAINT promocion_sucursal_producto_cantidad_check CHECK (cantidad_producto >= 1),'
            .'CONSTRAINT promocion_sucursal_producto_idpromocion_fkey FOREIGN KEY (idpromocion) REFERENCES promocion(id),'
            .'CONSTRAINT promocion_sucursal_producto_idsucursalproducto_fkey FOREIGN KEY (idsucursalproducto) REFERENCES sucursal_producto(id)'
            .')');

        // 6. Restaurar la columna idpromocion en detallepedido.
        Schema::table('detallepedido', function (Blueprint $table): void {
            $table->integer('idpromocion')->nullable()->after('idproductosucursal');
            $table->foreign('idpromocion')->references('id')->on('promocion');
        });

        // 7. Restaurar el CHECK original.
        DB::statement('ALTER TABLE detallepedido ADD CONSTRAINT chk_detallepedido_item CHECK (idproductosucursal IS NOT NULL OR idpromocion IS NOT NULL)');
    }
};
