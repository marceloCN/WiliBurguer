<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['nropedido', 'fecha', 'total', 'estadopedido', 'tipopedido', 'idsucursal', 'idempleado', 'idmesa', 'idaperturacaja', 'subtotalpedido', 'descuentopedido', 'horarecogida', 'observaciones'])]
class Pedido extends Model
{
    public $timestamps = false;

    protected $table = 'pedido';

    protected function casts(): array
    {
        return [
            'fecha' => 'datetime',
            'horarecogida' => 'datetime',
            'total' => 'decimal:2',
            'subtotalpedido' => 'decimal:2',
            'descuentopedido' => 'decimal:2',
        ];
    }

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class, 'idsucursal');
    }

    public function empleado(): BelongsTo
    {
        return $this->belongsTo(Empleado::class, 'idempleado');
    }

    public function mesa(): BelongsTo
    {
        return $this->belongsTo(Mesa::class, 'idmesa');
    }

    public function aperturaCaja(): BelongsTo
    {
        return $this->belongsTo(AperturaCaja::class, 'idaperturacaja');
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(DetallePedido::class, 'idpedido');
    }

    public function venta(): HasOne
    {
        return $this->hasOne(Venta::class, 'idpedido');
    }

    /**
     * Todas las ventas del pedido: con cuentas separadas puede haber más de
     * una, una por cuenta cobrada (y una sola, sin cuenta, cuando el cobro
     * cubrió el total pendiente).
     */
    public function ventas(): HasMany
    {
        return $this->hasMany(Venta::class, 'idpedido');
    }

    /**
     * Cuentas del pedido para el cobro separado: la cuenta 1 es el pedido
     * original y cada «Adicionar» abre una cuenta nueva con sus propias líneas,
     * para que cada cliente pueda pagar solo la suya.
     *
     * @return array<int, array{n: int, total: float, pagada: bool, saldo: float}>
     */
    public function cuentas(): array
    {
        $totales = $this->detalles
            ->groupBy('nrocuenta')
            ->map(fn (EloquentCollection $lineas): float => round((float) $lineas->sum('total'), 2))
            ->sortKeys();

        if ($totales->isEmpty()) {
            // Sin líneas no hay cómo agrupar: el pedido entero es la cuenta 1.
            $saldo = $this->saldoPendiente();

            return [[
                'n' => 1,
                'total' => (float) $this->total,
                'pagada' => $saldo <= 0,
                'saldo' => $saldo,
            ]];
        }

        // Un cobro del total cierra todas las cuentas del pedido, incluso las
        // que no tengan una venta propia que las name.
        $cierraTodo = $this->ventas->contains(fn (Venta $venta): bool => $venta->nrocuenta === null);
        $cobradas = $this->ventas->pluck('nrocuenta')
            ->filter()
            ->map(fn ($nrocuenta): int => (int) $nrocuenta)
            ->all();

        return $totales
            ->map(function (float $total, $nrocuenta) use ($cierraTodo, $cobradas): array {
                $pagada = $cierraTodo || in_array((int) $nrocuenta, $cobradas, true);

                return [
                    'n' => (int) $nrocuenta,
                    'total' => $total,
                    'pagada' => $pagada,
                    'saldo' => $pagada ? 0.0 : $total,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * Parte del total del pedido que todavía no se ha cobrado: nunca en
     * negativo, ni aunque los cobros no alcancen el total por un redondeo.
     */
    public function saldoPendiente(): float
    {
        return round(max(0, (float) $this->total - (float) $this->ventas->sum('totalventa')), 2);
    }
}
