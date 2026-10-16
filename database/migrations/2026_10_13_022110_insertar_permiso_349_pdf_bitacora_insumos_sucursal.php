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
            'codigo' => '349',
            'nombre' => 'Crear PDF de bitácora de insumos de sucursal',
            'estado' => true,
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('permiso')->where('codigo', '349')->delete();
    }
};
