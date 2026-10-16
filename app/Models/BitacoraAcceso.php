<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['idempleado', 'fechahora', 'tipoacceso', 'equipo', 'ip', 'resultado', 'detalle'])]
class BitacoraAcceso extends Model
{
    public $timestamps = false;

    protected $table = 'bitacoraacceso';

    protected function casts(): array
    {
        return [
            'fechahora' => 'datetime',
        ];
    }

    public function empleado(): BelongsTo
    {
        return $this->belongsTo(Empleado::class, 'idempleado');
    }
}
