<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['idinventariosucursal', 'tipomovimiento', 'cantidad', 'cantidadanterior', 'cantidadnueva', 'fechamovimiento', 'idempleado', 'observacion'])]
class MovimientoInventario extends Model
{
    public $timestamps = false;

    protected $table = 'movimientoinventario';

    protected function casts(): array
    {
        return [
            'cantidad' => 'decimal:3',
            'cantidadanterior' => 'decimal:3',
            'cantidadnueva' => 'decimal:3',
            'fechamovimiento' => 'datetime',
        ];
    }

    public function inventarioSucursal(): BelongsTo
    {
        return $this->belongsTo(InventarioSucursal::class, 'idinventariosucursal');
    }

    public function empleado(): BelongsTo
    {
        return $this->belongsTo(Empleado::class, 'idempleado');
    }
}
