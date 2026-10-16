<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['nombre', 'activo', 'gateway'])]
class MetodoPago extends Model
{
    public $timestamps = false;

    protected $table = 'metodopago';

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }

    public function pagos(): HasMany
    {
        return $this->hasMany(Pago::class, 'idmetodopago');
    }
}
