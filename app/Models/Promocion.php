<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['nombre', 'photo', 'activo'])]
class Promocion extends Model
{
    public $timestamps = false;

    protected $table = 'promocion';

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }

    public function promocionesSucursal(): HasMany
    {
        return $this->hasMany(PromocionSucursal::class, 'idpromocion');
    }
}
