<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['fecha_registro', 'precioanterior', 'precionuevo', 'idproductosucursal'])]
class HistorialProducto extends Model
{
    public $timestamps = false;

    protected $table = 'historialproducto';

    protected function casts(): array
    {
        return [
            'fecha_registro' => 'datetime',
            'precioanterior' => 'decimal:2',
            'precionuevo' => 'decimal:2',
        ];
    }

    public function sucursalProducto(): BelongsTo
    {
        return $this->belongsTo(SucursalProducto::class, 'idproductosucursal');
    }
}
