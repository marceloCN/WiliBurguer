<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['idproducto', 'idinsumo', 'cantidad'])]
class DetalleReceta extends Model
{
    public $timestamps = false;

    protected $table = 'detallereceta';

    protected function casts(): array
    {
        return [
            'cantidad' => 'decimal:3',
        ];
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'idproducto');
    }

    public function insumo(): BelongsTo
    {
        return $this->belongsTo(Insumo::class, 'idinsumo');
    }
}
