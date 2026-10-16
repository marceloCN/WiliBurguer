<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordNotification extends Notification
{
    use Queueable;

    public function __construct(public string $token) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $nombre = $notifiable->empleado?->nombre;
        $apellido = $notifiable->empleado?->apellido;

        $url = route('password.reset', [
            'token' => $this->token,
            'correo' => $notifiable->getEmailForPasswordReset(),
        ]);

        $saludo = $nombre
            ? "Hola, {$nombre} {$apellido}"
            : 'Hola';

        $conf = \App\Models\ConfiguracionSistema::pluck('valor', 'clave');

        return (new MailMessage)
            ->from(config('mail.from.address'), config('mail.from.name'))
            ->subject('Recuperación de contraseña · Burguer Willy')
            ->view(['html' => 'emails.reset-password', 'text' => 'emails.reset-password-text'], [
                'sitioNombre' => $conf->get('SITIO_NOMBRE', 'Burger Willy'),
                'sitioEslogan' => $conf->get('SITIO_ESLOGAN', ''),
                'sitioDireccion' => $conf->get('SITIO_DIRECCION', ''),
                'sitioTelefono' => $conf->get('SITIO_TELEFONO', ''),
                'sitioEmail' => $conf->get('SITIO_EMAIL', ''),
            ])
            ->greeting($saludo.',')
            ->line('Recibimos una solicitud para restablecer la contraseña de tu cuenta en Burguer Willy.')
            ->line('Al abrir este mensaje, el botón te llevará directamente al sistema para elegir tu nueva contraseña y confirmarla.')
            ->line('Una vez aceptada, volverás a la página principal y podrás iniciar sesión con tu nueva contraseña.')
            ->action('Cambiar mi contraseña', $url)
            ->line('Este enlace expirará en 60 minutos.')
            ->line('Si no solicitaste este cambio, puedes ignorar este correo.');
    }
}
