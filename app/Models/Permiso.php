<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['codigo', 'nombre', 'estado'])]
class Permiso extends Model
{
    public $timestamps = false;

    protected $table = 'permiso';

    protected function casts(): array
    {
        return [
            'estado' => 'boolean',
        ];
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Rol::class, 'rolpermiso', 'idpermiso', 'idrol');
    }

    public function rolPermisos(): HasMany
    {
        return $this->hasMany(RolPermiso::class, 'idpermiso');
    }

    public function bitacorasAccion(): HasMany
    {
        return $this->hasMany(BitacoraAccion::class, 'idpermiso');
    }
}
