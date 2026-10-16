<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['numeromesa', 'capacidad', 'estado', 'idsucursal'])]
class Mesa extends Model
{
    public $timestamps = false;

    protected $table = 'mesa';

    protected function casts(): array
    {
        return [
            'capacidad' => 'integer',
            'estado' => 'boolean',
        ];
    }

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class, 'idsucursal');
    }

    public function pedidos(): HasMany
    {
        return $this->hasMany(Pedido::class, 'idmesa');
    }
}
