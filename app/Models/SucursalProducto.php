<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['idsucursal', 'idproducto', 'precioproducto', 'disponibilidad'])]
class SucursalProducto extends Model
{
    public $timestamps = false;

    protected $table = 'sucursal_producto';

    protected function casts(): array
    {
        return [
            'precioproducto' => 'decimal:2',
            'disponibilidad' => 'boolean',
        ];
    }

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class, 'idsucursal');
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'idproducto');
    }

    public function historialesProducto(): HasMany
    {
        return $this->hasMany(HistorialProducto::class, 'idproductosucursal');
    }

    public function detallesPedido(): HasMany
    {
        return $this->hasMany(DetallePedido::class, 'idproductosucursal');
    }

    public function promocionesSucursal(): BelongsToMany
    {
        return $this->belongsToMany(PromocionSucursal::class, 'promocion_sucursal_producto', 'idsucursalproducto', 'idpromocionsucursal')
            ->withPivot('idsucursal', 'cantidad_producto');
    }
}
