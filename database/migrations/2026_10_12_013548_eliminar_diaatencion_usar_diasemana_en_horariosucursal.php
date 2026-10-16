<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Reemplaza la tabla diaatencion por una columna diasemana directa
     * en horariosucursal (1 = lunes, 7 = domingo).
     */
    public function up(): void
    {
        // 1. Agregar la nueva columna como nullable para poder migrar los datos.
        Schema::table('horariosucursal', function (Blueprint $table): void {
            $table->smallInteger('diasemana')->nullable()->after('activo');
        });

        // 2. Migrar los dias desde diaatencion.numerodia.
        DB::statement('UPDATE horariosucursal h SET diasemana = d.numerodia FROM diaatencion d WHERE h.iddiaatencion = d.id');

        // 3. Asegurar que no queden nulos.
        DB::statement('ALTER TABLE horariosucursal ALTER COLUMN diasemana SET NOT NULL');

        // 4. Eliminar constraint UNIQUE y FK que usan iddiaatencion.
        DB::statement('ALTER TABLE horariosucursal DROP CONSTRAINT IF EXISTS horariosucursal_idsucursal_iddiaatencion_key');
        DB::statement('ALTER TABLE horariosucursal DROP CONSTRAINT IF EXISTS horariosucursal_iddiaatencion_fkey');

        // 5. Eliminar la columna iddiaatencion.
        Schema::table('horariosucursal', function (Blueprint $table): void {
            $table->dropColumn('iddiaatencion');
        });

        // 6. Agregar UNIQUE y CHECK sobre diasemana.
        DB::statement('ALTER TABLE horariosucursal ADD CONSTRAINT horariosucursal_idsucursal_diasemana_key UNIQUE (idsucursal, diasemana)');
        DB::statement('ALTER TABLE horariosucursal ADD CONSTRAINT horariosucursal_diasemana_check CHECK (diasemana BETWEEN 1 AND 7)');

        // 7. Eliminar la tabla diaatencion que ya no se usa.
        Schema::dropIfExists('diaatencion');
    }

    /**
     * Revierte el cambio recreando la tabla diaatencion y restaurando iddiaatencion.
     */
    public function down(): void
    {
        // 1. Recrear tabla diaatencion con los 7 dias de la semana.
        DB::statement('CREATE TABLE diaatencion ('
            .'id INT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,'
            .'nombredia VARCHAR(20) NOT NULL,'
            .'numerodia INT NOT NULL'
            .')');

        DB::statement('INSERT INTO diaatencion (nombredia, numerodia) VALUES'
            ."('Lunes', 1),"
            ."('Martes', 2),"
            ."('Miercoles', 3),"
            ."('Jueves', 4),"
            ."('Viernes', 5),"
            ."('Sabado', 6),"
            ."('Domingo', 7)");

        // 2. Agregar columna iddiaatencion nullable para migrar desde diasemana.
        Schema::table('horariosucursal', function (Blueprint $table): void {
            $table->integer('iddiaatencion')->nullable()->after('activo');
        });

        // 3. Migrar los ids desde diasemana.
        DB::statement('UPDATE horariosucursal h SET iddiaatencion = d.id FROM diaatencion d WHERE h.diasemana = d.numerodia');

        // 4. Hacer iddiaatencion NOT NULL.
        DB::statement('ALTER TABLE horariosucursal ALTER COLUMN iddiaatencion SET NOT NULL');

        // 5. Eliminar constraints y columna diasemana.
        DB::statement('ALTER TABLE horariosucursal DROP CONSTRAINT IF EXISTS horariosucursal_idsucursal_diasemana_key');
        DB::statement('ALTER TABLE horariosucursal DROP CONSTRAINT IF EXISTS horariosucursal_diasemana_check');
        Schema::table('horariosucursal', function (Blueprint $table): void {
            $table->dropColumn('diasemana');
        });

        // 6. Restaurar UNIQUE y FK.
        DB::statement('ALTER TABLE horariosucursal ADD CONSTRAINT horariosucursal_idsucursal_iddiaatencion_key UNIQUE (idsucursal, iddiaatencion)');
        DB::statement('ALTER TABLE horariosucursal ADD CONSTRAINT horariosucursal_iddiaatencion_fkey FOREIGN KEY (iddiaatencion) REFERENCES diaatencion(id)');
    }
};
