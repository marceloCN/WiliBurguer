<?php

use App\Models\PromocionSucursal;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('promociones:desactivar-vencidas', function () {
    $vencidas = PromocionSucursal::with('promocion')
        ->where('activo', true)
        ->whereDate('fechafin', '<', now()->toDateString())
        ->get();

    if ($vencidas->isEmpty()) {
        $this->info('No hay promociones vencidas activas.');

        return;
    }

    $actualizadas = 0;

    DB::transaction(function () use ($vencidas, &$actualizadas) {
        foreach ($vencidas as $promocionSucursal) {
            $promocionSucursal->update(['activo' => false]);

            if ($promocionSucursal->promocion) {
                $promocionSucursal->promocion->update(['activo' => false]);
            }

            $actualizadas++;
        }
    });

    $this->info("{$actualizadas} promoción(es) vencida(s) desactivada(s).");
})
    ->purpose('Desactiva las promociones cuya vigencia ya finalizó')
    ->daily();

Artisan::command('promociones:estado-actual', function () {
    $activas = PromocionSucursal::where('activo', true)->count();
    $vencidas = PromocionSucursal::where('activo', true)
        ->whereDate('fechafin', '<', now()->toDateString())
        ->count();

    $this->info("Promociones activas: {$activas}");
    $this->info("Promociones activas pero vencidas: {$vencidas}");
})->purpose('Muestra cuántas promociones activas están vencidas');
