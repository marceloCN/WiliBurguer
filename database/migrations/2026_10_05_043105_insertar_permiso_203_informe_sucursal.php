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
            'codigo' => '203',
            'nombre' => 'Crear informe de datos de sucursal',
            'estado' => true,
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('permiso')->where('codigo', '203')->delete();
    }
};
