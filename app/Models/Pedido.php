<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['nropedido', 'fecha', 'total', 'estadopedido', 'tipopedido', 'idsucursal', 'idempleado', 'idmesa', 'idaperturacaja', 'subtotalpedido', 'descuentopedido', 'horarecogida', 'observaciones'])]
class Pedido extends Model
{
    public $timestamps = false;

    protected $table = 'pedido';

    protected function casts(): array
    {
        return [
            'fecha' => 'datetime',
            'horarecogida' => 'datetime',
            'total' => 'decimal:2',
            'subtotalpedido' => 'decimal:2',
            'descuentopedido' => 'decimal:2',
        ];
    }

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class, 'idsucursal');
    }

    public function empleado(): BelongsTo
    {
        return $this->belongsTo(Empleado::class, 'idempleado');
    }

    public function mesa(): BelongsTo
    {
        return $this->belongsTo(Mesa::class, 'idmesa');
    }

    public function aperturaCaja(): BelongsTo
    {
        return $this->belongsTo(AperturaCaja::class, 'idaperturacaja');
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(DetallePedido::class, 'idpedido');
    }

    public function venta(): HasOne
    {
        return $this->hasOne(Venta::class, 'idpedido');
    }
}
