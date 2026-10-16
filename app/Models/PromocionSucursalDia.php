<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['idpromocionsucursal', 'diasemana'])]
class PromocionSucursalDia extends Model
{
    public $timestamps = false;

    protected $table = 'promocion_sucursal_dia';

    protected $primaryKey = null;

    public $incrementing = false;

    protected function casts(): array
    {
        return [
            'diasemana' => 'integer',
        ];
    }

    public function promocionSucursal(): BelongsTo
    {
        return $this->belongsTo(PromocionSucursal::class, 'idpromocionsucursal');
    }
}
