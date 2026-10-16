<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['idempleado', 'idpermiso', 'fechahora', 'accion', 'tablaafectada', 'idregistroafectado', 'equipo', 'ip', 'resultado'])]
class BitacoraAccion extends Model
{
    public $timestamps = false;

    protected $table = 'bitacoraaccion';

    protected function casts(): array
    {
        return [
            'fechahora' => 'datetime',
            'idregistroafectado' => 'integer',
        ];
    }

    public function empleado(): BelongsTo
    {
        return $this->belongsTo(Empleado::class, 'idempleado');
    }

    public function permiso(): BelongsTo
    {
        return $this->belongsTo(Permiso::class, 'idpermiso');
    }
}
