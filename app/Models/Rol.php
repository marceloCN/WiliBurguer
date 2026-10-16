<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['claverol', 'nombrerol'])]
class Rol extends Model
{
    public $timestamps = false;

    protected $table = 'rol';

    public function empleados(): HasMany
    {
        return $this->hasMany(Empleado::class, 'idrol');
    }

    public function permisos(): BelongsToMany
    {
        return $this->belongsToMany(Permiso::class, 'rolpermiso', 'idrol', 'idpermiso');
    }

    public function rolPermisos(): HasMany
    {
        return $this->hasMany(RolPermiso::class, 'idrol');
    }
}
