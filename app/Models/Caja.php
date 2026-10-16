<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['nombre', 'activo', 'idsucursal'])]
class Caja extends Model
{
    public $timestamps = false;

    protected $table = 'caja';

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class, 'idsucursal');
    }

    public function aperturasCaja(): HasMany
    {
        return $this->hasMany(AperturaCaja::class, 'idcaja');
    }
}
