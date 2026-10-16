<?php

namespace App\Models;

use App\Notifications\ResetPasswordNotification;
use Illuminate\Auth\Authenticatable as AuthenticatableTrait;
use Illuminate\Auth\Passwords\CanResetPassword as CanResetPasswordTrait;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Notifications\Notifiable;

#[Fillable(['idempleado', 'usuario', 'correo', 'passwordhash', 'intentosfallidos', 'bloqueado', 'bloqueadohasta', 'fechacreacion', 'ultimoacceso'])]
#[Hidden(['passwordhash'])]
class CuentaUsuario extends Model implements AuthenticatableContract, CanResetPasswordContract
{
    use AuthenticatableTrait;
    use CanResetPasswordTrait;
    use Notifiable;

    public $timestamps = false;

    protected $table = 'cuentausuario';

    protected function casts(): array
    {
        return [
            'intentosfallidos' => 'integer',
            'bloqueado' => 'boolean',
            'bloqueadohasta' => 'datetime',
            'fechacreacion' => 'datetime',
            'ultimoacceso' => 'datetime',
        ];
    }

    public function empleado(): BelongsTo
    {
        return $this->belongsTo(Empleado::class, 'idempleado');
    }

    public function getAuthPassword(): string
    {
        return $this->passwordhash ?? '';
    }

    public function getEmailForPasswordReset(): string
    {
        return $this->correo;
    }

    public function routeNotificationForMail($notification = null): string
    {
        return $this->correo;
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }
}
