<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('permiso')->insertOrIgnore([
            ['codigo' => '152', 'nombre' => 'Crear PDF de bitácora de accesos', 'estado' => true],
            ['codigo' => '153', 'nombre' => 'Crear PDF de bitácora de acciones', 'estado' => true],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('permiso')->whereIn('codigo', ['152', '153'])->delete();
    }
};
