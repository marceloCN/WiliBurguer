<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['nombre', 'direccion', 'telefono', 'latitud', 'longitud', 'estado', 'idempleadogerente'])]
class Sucursal extends Model
{
    public $timestamps = false;

    protected $table = 'sucursal';

    protected function casts(): array
    {
        return [
            'latitud' => 'decimal:8',
            'longitud' => 'decimal:8',
            'estado' => 'boolean',
        ];
    }

    public function gerente(): BelongsTo
    {
        return $this->belongsTo(Empleado::class, 'idempleadogerente');
    }

    public function empleados(): HasMany
    {
        return $this->hasMany(Empleado::class, 'idsucursal');
    }

    public function horarios(): HasMany
    {
        return $this->hasMany(HorarioSucursal::class, 'idsucursal');
    }

    public function productosSucursal(): HasMany
    {
        return $this->hasMany(SucursalProducto::class, 'idsucursal');
    }

    public function inventarios(): HasMany
    {
        return $this->hasMany(InventarioSucursal::class, 'idsucursal');
    }

    public function mesas(): HasMany
    {
        return $this->hasMany(Mesa::class, 'idsucursal');
    }

    public function pedidos(): HasMany
    {
        return $this->hasMany(Pedido::class, 'idsucursal');
    }

    public function cajas(): HasMany
    {
        return $this->hasMany(Caja::class, 'idsucursal');
    }

    public function promocionesSucursal(): HasMany
    {
        return $this->hasMany(PromocionSucursal::class, 'idsucursal');
    }
}
