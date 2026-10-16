<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['nombre', 'descripcion', 'idunidadmedida', 'estado'])]
class Insumo extends Model
{
    public $timestamps = false;

    protected $table = 'insumo';

    protected function casts(): array
    {
        return [
            'estado' => 'boolean',
        ];
    }

    public function unidadMedida(): BelongsTo
    {
        return $this->belongsTo(UnidadMedida::class, 'idunidadmedida');
    }

    public function detallesReceta(): HasMany
    {
        return $this->hasMany(DetalleReceta::class, 'idinsumo');
    }

    public function inventarios(): HasMany
    {
        return $this->hasMany(InventarioSucursal::class, 'idinsumo');
    }
}
