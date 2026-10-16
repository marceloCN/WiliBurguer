<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['idsucursal', 'idinsumo', 'cantidad', 'cantidadminima', 'fechaactualizacion'])]
class InventarioSucursal extends Model
{
    public $timestamps = false;

    protected $table = 'inventariosucursal';

    protected function casts(): array
    {
        return [
            'cantidad' => 'decimal:3',
            'cantidadminima' => 'decimal:3',
            'fechaactualizacion' => 'datetime',
        ];
    }

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class, 'idsucursal');
    }

    public function insumo(): BelongsTo
    {
        return $this->belongsTo(Insumo::class, 'idinsumo');
    }

    public function movimientos(): HasMany
    {
        return $this->hasMany(MovimientoInventario::class, 'idinventariosucursal');
    }
}
