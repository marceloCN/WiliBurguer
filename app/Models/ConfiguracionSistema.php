<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

#[Fillable(['clave', 'valor', 'descripcion', 'estado'])]
class ConfiguracionSistema extends Model
{
    public const POR_DEFECTO = [
        'SITIO_NOMBRE' => 'Burger Willy and Grill',
        'SITIO_ESLOGAN' => 'Sabores que se quedan',
        'SITIO_TELEFONO' => '987 654 321',
        'SITIO_EMAIL' => 'contacto@burguerwilly.com',
        'SITIO_DIRECCION' => 'Av. Principal 123, Lima',
        'SITIO_HORARIO' => 'Lun - Dom: 12:00 p.m. - 11:00 p.m.',
        'IMAGENES_CARRUSEL' => '[]',
        'SITIO_HABILITAR_CARRUSEL' => '1',
        'SITIO_HABILITAR_MENU' => '1',
        'SITIO_HABILITAR_PROMOCIONES' => '1',
        'SITIO_HABILITAR_SUCURSALES' => '1',
        'TEMA_COLOR_PRIMARIO' => '#4a6741',
        'TEMA_COLOR_PRIMARIO_OSCURO' => '#3a5233',
        'TEMA_COLOR_FONDO' => '#faf6ef',
        'TEMA_COLOR_TEXTO' => '#2f2b26',
        'TEMA_COLOR_DETALLE' => '#d4a373',
        'SITIO_MANTENIMIENTO_ACTIVO' => '0',
        'SITIO_MANTENIMIENTO_MENSAJE' => 'Estamos realizando mejoras en la página. Volveremos muy pronto. Gracias por tu paciencia.',
    ];

    protected static $mapaCache = null;

    public $timestamps = false;

    protected $table = 'configuracion_sistema';

    protected function casts(): array
    {
        return [
            'estado' => 'boolean',
        ];
    }

    public static function mapa(): Collection
    {
        if (static::$mapaCache === null) {
            $actuales = static::query()
                ->where('estado', true)
                ->pluck('valor', 'clave');

            static::$mapaCache = collect(static::POR_DEFECTO)->merge($actuales);
        }

        return static::$mapaCache;
    }

    public static function guardar(string $clave, string $valor, ?string $descripcion = null): void
    {
        $datos = ['valor' => $valor, 'estado' => true];

        if ($descripcion !== null) {
            $datos['descripcion'] = $descripcion;
        }

        static::updateOrCreate(['clave' => $clave], $datos);
    }
}
