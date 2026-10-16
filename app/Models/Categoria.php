<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['nombre', 'estado'])]
class Categoria extends Model
{
    public $timestamps = false;

    protected $table = 'categoria';

    protected function casts(): array
    {
        return [
            'estado' => 'boolean',
        ];
    }

    public function productos(): HasMany
    {
        return $this->hasMany(Producto::class, 'idcategoria');
    }
}
