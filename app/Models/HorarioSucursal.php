<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['idsucursal', 'horaapertura', 'horacierre', 'activo', 'diasemana'])]
class HorarioSucursal extends Model
{
    public $timestamps = false;

    protected $table = 'horariosucursal';

    protected $primaryKey = 'idhorariosucursal';

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
            'diasemana' => 'integer',
        ];
    }

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class, 'idsucursal');
    }

    /**
     * Devuelve el nombre del dia de la semana (1 = lunes, 7 = domingo).
     */
    public static function nombreDia(int $diasemana): string
    {
        return [
            1 => 'Lunes',
            2 => 'Martes',
            3 => 'Miercoles',
            4 => 'Jueves',
            5 => 'Viernes',
            6 => 'Sabado',
            7 => 'Domingo',
        ][$diasemana] ?? 'Sin asignar';
    }
}
