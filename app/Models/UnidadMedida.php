<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['nombre', 'abreviatura', 'tipo', 'estado'])]
class UnidadMedida extends Model
{
    public $timestamps = false;

    protected $table = 'unidadmedida';

    protected function casts(): array
    {
        return [
            'estado' => 'boolean',
        ];
    }

    public function insumos(): HasMany
    {
        return $this->hasMany(Insumo::class, 'idunidadmedida');
    }
}
