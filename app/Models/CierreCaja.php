<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['fechacierre', 'fondoinicial', 'totalventasefectivo', 'totalventasqr', 'totalventastarjeta', 'totalventas', 'totalingresos', 'totalegresos', 'efectivoesperado', 'efectivocontado', 'diferencia', 'observacion', 'idaperturacaja', 'idempleado'])]
class CierreCaja extends Model
{
    public $timestamps = false;

    protected $table = 'cierrecaja';

    protected $casts = [
        'fechacierre' => 'datetime',
        'fondoinicial' => 'decimal:2',
        'totalventasefectivo' => 'decimal:2',
        'totalventasqr' => 'decimal:2',
        'totalventastarjeta' => 'decimal:2',
        'totalventas' => 'decimal:2',
        'totalingresos' => 'decimal:2',
        'totalegresos' => 'decimal:2',
        'efectivoesperado' => 'decimal:2',
        'efectivocontado' => 'decimal:2',
        'diferencia' => 'decimal:2',
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
