<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['idpromocionsucursal', 'idsucursalproducto', 'idsucursal', 'cantidad_producto'])]
class PromocionSucursalProducto extends Model
{
    public $timestamps = false;

    protected $table = 'promocion_sucursal_producto';

    protected $primaryKey = null;

    public $incrementing = false;

    protected function casts(): array
    {
        return [
            'cantidad_producto' => 'integer',
        ];
    }

    public function promocionSucursal(): BelongsTo
    {
        return $this->belongsTo(PromocionSucursal::class, 'idpromocionsucursal');
    }

    public function sucursalProducto(): BelongsTo
    {
        return $this->belongsTo(SucursalProducto::class, 'idsucursalproducto');
    }

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class, 'idsucursal');
    }
}
