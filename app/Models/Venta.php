<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['fechaventa', 'totalventa', 'estadoventa', 'idempleado', 'idpedido'])]
class Venta extends Model
{
    public $timestamps = false;

    protected $table = 'venta';

    protected function casts(): array
    {
        return [
            'fechaventa' => 'datetime',
            'totalventa' => 'decimal:2',
        ];
    }

    public function empleado(): BelongsTo
    {
        return $this->belongsTo(Empleado::class, 'idempleado');
    }

    public function pedido(): BelongsTo
    {
        return $this->belongsTo(Pedido::class, 'idpedido');
    }

    public function pagos(): HasMany
    {
        return $this->hasMany(Pago::class, 'idventa');
    }
}
