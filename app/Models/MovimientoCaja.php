<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['fechamovimiento', 'tipomovimiento', 'concepto', 'monto', 'observacion', 'idaperturacaja', 'idempleado'])]
class MovimientoCaja extends Model
{
    public $timestamps = false;

    protected $table = 'movimientocaja';

    protected $casts = [
        'fechamovimiento' => 'datetime',
        'monto' => 'decimal:2',
    ];

    public function aperturaCaja(): BelongsTo
    {
        return $this->belongsTo(AperturaCaja::class, 'idaperturacaja');
    }

    public function empleado(): BelongsTo
    {
        return $this->belongsTo(Empleado::class, 'idempleado');
    }
}
