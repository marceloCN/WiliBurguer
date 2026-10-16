<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['idventa', 'idmetodopago', 'idaperturacaja', 'montopago', 'estadopago', 'montorecibido', 'cambioentregado', 'referenciaexterna', 'gateway', 'codigoautorizacion', 'idtransaccion', 'ultimos4', 'marcatarjeta', 'fechapago', 'observacionpago'])]
#[Hidden(['ultimos4', 'marcatarjeta', 'codigoautorizacion', 'idtransaccion'])]
class Pago extends Model
{
    public $timestamps = false;

    protected $table = 'pago';

    protected function casts(): array
    {
        return [
            'montopago' => 'decimal:2',
            'montorecibido' => 'decimal:2',
            'cambioentregado' => 'decimal:2',
            'fechapago' => 'datetime',
        ];
    }

    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class, 'idventa');
    }

    public function metodoPago(): BelongsTo
    {
        return $this->belongsTo(MetodoPago::class, 'idmetodopago');
    }

    public function aperturaCaja(): BelongsTo
    {
        return $this->belongsTo(AperturaCaja::class, 'idaperturacaja');
    }
}
