<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['nombre', 'apellido', 'telefono', 'estado', 'fecharegistro', 'idjefe', 'idrol', 'idsucursal'])]
class Empleado extends Model
{
    public $timestamps = false;

    protected $table = 'empleado';

    protected function casts(): array
    {
        return [
            'estado' => 'boolean',
            'fecharegistro' => 'datetime',
        ];
    }

    public function jefe(): BelongsTo
    {
        return $this->belongsTo(Empleado::class, 'idjefe');
    }

    public function subordinados(): HasMany
    {
        return $this->hasMany(Empleado::class, 'idjefe');
    }

    public function rol(): BelongsTo
    {
        return $this->belongsTo(Rol::class, 'idrol');
    }

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class, 'idsucursal');
    }

    public function cuentaUsuario(): HasOne
    {
        return $this->hasOne(CuentaUsuario::class, 'idempleado');
    }

    public function sucursalesGerenciadas(): HasMany
    {
        return $this->hasMany(Sucursal::class, 'idempleadogerente');
    }

    public function pedidos(): HasMany
    {
        return $this->hasMany(Pedido::class, 'idempleado');
    }

    public function ventas(): HasMany
    {
        return $this->hasMany(Venta::class, 'idempleado');
    }

    public function aperturasCaja(): HasMany
    {
        return $this->hasMany(AperturaCaja::class, 'idempleado');
    }

    public function movimientosInventario(): HasMany
    {
        return $this->hasMany(MovimientoInventario::class, 'idempleado');
    }

    public function movimientosCaja(): HasMany
    {
        return $this->hasMany(MovimientoCaja::class, 'idempleado');
    }

    public function cierresCaja(): HasMany
    {
        return $this->hasMany(CierreCaja::class, 'idempleado');
    }

    public function bitacorasAcceso(): HasMany
    {
        return $this->hasMany(BitacoraAcceso::class, 'idempleado');
    }

    public function bitacorasAccion(): HasMany
    {
        return $this->hasMany(BitacoraAccion::class, 'idempleado');
    }
}
