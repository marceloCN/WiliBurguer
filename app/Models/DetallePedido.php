<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['cantidad', 'preciounitario', 'subtotal', 'descuento', 'total', 'idpedido', 'idproductosucursal', 'idpromocionsucursal'])]
class DetallePedido extends Model
{
    public $timestamps = false;

    protected $table = 'detallepedido';

    protected function casts(): array
    {
        return [
            'cantidad' => 'integer',
            'preciounitario' => 'decimal:2',
            'subtotal' => 'decimal:2',
            'descuento' => 'decimal:2',
            'total' => 'decimal:2',
            'idpromocionsucursal' => 'integer',
        ];
    }

    public function pedido(): BelongsTo
    {
        return $this->belongsTo(Pedido::class, 'idpedido');
    }

    public function sucursalProducto(): BelongsTo
    {
        return $this->belongsTo(SucursalProducto::class, 'idproductosucursal');
    }

    public function promocionSucursal(): BelongsTo
    {
        return $this->belongsTo(PromocionSucursal::class, 'idpromocionsucursal');
    }
}
