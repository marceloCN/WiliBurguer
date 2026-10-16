<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['idrol', 'idpermiso'])]
class RolPermiso extends Model
{
    public $timestamps = false;

    public $incrementing = false;

    protected $table = 'rolpermiso';

    protected $primaryKey = 'idrol';

    public function rol(): BelongsTo
    {
        return $this->belongsTo(Rol::class, 'idrol');
    }

    public function permiso(): BelongsTo
    {
        return $this->belongsTo(Permiso::class, 'idpermiso');
    }
}
