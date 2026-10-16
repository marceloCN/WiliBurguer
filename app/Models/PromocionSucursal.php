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
}
