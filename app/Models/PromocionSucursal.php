<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['idpromocion', 'idsucursal', 'tipodescuento', 'valordescuento', 'fechainicio', 'fechafin', 'activo'])]
class PromocionSucursal extends Model
{
    public $timestamps = false;

    protected $table = 'promocion_sucursal';

    protected function casts(): array
    {
        return [
            'valordescuento' => 'decimal:2',
            'fechainicio' => 'datetime',
            'fechafin' => 'datetime',
            'activo' => 'boolean',
        ];
    }

    public function promocion(): BelongsTo
    {
        return $this->belongsTo(Promocion::class, 'idpromocion');
    }

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class, 'idsucursal');
    }

    public function dias(): HasMany
    {
        return $this->hasMany(PromocionSucursalDia::class, 'idpromocionsucursal');
    }

    public function productosSucursal(): BelongsToMany
    {
        return $this->belongsToMany(SucursalProducto::class, 'promocion_sucursal_producto', 'idpromocionsucursal', 'idsucursalproducto')
            ->withPivot('idsucursal', 'cantidad_producto');
    }

    public function etiquetaDescuento(): string
    {
        $valor = number_format((float) $this->valordescuento, 2);

        return match ($this->tipodescuento) {
            'PORCENTAJE' => '-'.number_format((float) $this->valordescuento, 0).'%',
            'PRECIO_PROMOCIONAL' => 'Bs. '.$valor,
            default => '-Bs. '.$valor,
        };
    }

    public function precioPromocional(float $precioOriginal): float
    {
        $valor = (float) $this->valordescuento;

        return match ($this->tipodescuento) {
            'PORCENTAJE' => $precioOriginal * (1 - $valor / 100),
            'DESCUENTO_MONTO' => max(0, $precioOriginal - $valor),
            'PRECIO_PROMOCIONAL' => $valor,
        };
    }

    public function subtotalCombo(): float
    {
        return round($this->productosSucursal->sum(
            fn (SucursalProducto $producto): float => (float) $producto->precioproducto
                * (int) $producto->pivot->cantidad_producto
        ), 2);
    }

    public function totalCombo(): float
    {
        return round($this->precioPromocional($this->subtotalCombo()), 2);
    }

    public function descuentoCombo(): float
    {
        return max(0, round($this->subtotalCombo() - $this->totalCombo(), 2));
    }

    public function diasTexto(): string
    {
        $nombres = $this->dias->sortBy('diasemana')
            ->map(fn (PromocionSucursalDia $dia) => mb_strtolower(HorarioSucursal::nombreDia($dia->diasemana)))
            ->values();

        if ($nombres->isEmpty()) {
            return 'sin días asignados';
        }

        if ($nombres->count() === 7) {
            return 'todos los días';
        }

        $ultimo = $nombres->pop();

        return 'los '.($nombres->isEmpty() ? $ultimo : $nombres->implode(', ').' y '.$ultimo);
    }
}
