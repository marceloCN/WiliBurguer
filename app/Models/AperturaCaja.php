<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['fechaapertura', 'montoinicial', 'estado', 'idcaja', 'idempleado'])]
class AperturaCaja extends Model
{
    public $timestamps = false;

    protected $table = 'aperturacaja';

    protected function casts(): array
    {
        return [
            'fechaapertura' => 'datetime',
            'montoinicial' => 'decimal:2',
        ];
    }

    public function caja(): BelongsTo
    {
        return $this->belongsTo(Caja::class, 'idcaja');
    }

    public function empleado(): BelongsTo
    {
        return $this->belongsTo(Empleado::class, 'idempleado');
    }

    public function pagos(): HasMany
    {
        return $this->hasMany(Pago::class, 'idaperturacaja');
    }

    public function movimientosCaja(): HasMany
    {
        return $this->hasMany(MovimientoCaja::class, 'idaperturacaja');
    }

    public function cierreCaja(): HasOne
    {
        return $this->hasOne(CierreCaja::class, 'idaperturacaja');
    }
}
