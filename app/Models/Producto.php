<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['nombre', 'descripcion', 'photo', 'estado', 'idcategoria'])]
class Producto extends Model
{
    public $timestamps = false;

    protected $table = 'producto';

    protected function casts(): array
    {
        return [
            'estado' => 'boolean',
        ];
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class, 'idcategoria');
    }

    public function detallesReceta(): HasMany
    {
        return $this->hasMany(DetalleReceta::class, 'idproducto');
    }

    public function productosSucursal(): HasMany
    {
        return $this->hasMany(SucursalProducto::class, 'idproducto');
    }
}
