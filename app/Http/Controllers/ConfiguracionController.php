<?php

namespace App\Http\Controllers;

use App\Models\ConfiguracionSistema;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class ConfiguracionController extends Controller
{
    private const CLAVES_IDENTIDAD = [
        'SITIO_NOMBRE' => 'Nombre comercial del sistema/negocio',
        'SITIO_ESLOGAN' => 'Eslogan que aparece en el inicio',
        'SITIO_TELEFONO' => 'Teléfono de contacto principal',
        'SITIO_EMAIL' => 'Correo de contacto',
        'SITIO_DIRECCION' => 'Dirección principal',
        'SITIO_HORARIO' => 'Horario de atención general',
    ];

    private const CLAVES_SECCIONES = [
        'SITIO_HABILITAR_CARRUSEL' => 'Muestra el carrusel de imágenes en el inicio',
        'SITIO_HABILITAR_MENU' => 'Muestra la sección de menú/productos',
        'SITIO_HABILITAR_PROMOCIONES' => 'Muestra la sección de promociones',
        'SITIO_HABILITAR_SUCURSALES' => 'Muestra la sección de sucursales',
    ];

    private const CLAVES_TEMA = [
        'TEMA_COLOR_PRIMARIO' => 'Color principal (botones, barra de navegación)',
        'TEMA_COLOR_PRIMARIO_OSCURO' => 'Color principal oscuro (hover de botones)',
        'TEMA_COLOR_FONDO' => 'Color de fondo de la página',
        'TEMA_COLOR_TEXTO' => 'Color de texto',
        'TEMA_COLOR_DETALLE' => 'Color de detalle (acentos, degradados)',
    ];

    private const CLAVES_MANTENIMIENTO = [
        'SITIO_MANTENIMIENTO_MENSAJE' => 'Mensaje que verán los visitantes mientras el sitio está en mantenimiento',
    ];

    public function index(): View
    {
        $configuracion = ConfiguracionSistema::mapa();

        $sliders = collect(json_decode($configuracion->get('IMAGENES_CARRUSEL', '[]'), true))
            ->filter()
            ->sortBy('orden')
            ->values();

        return view('admin.configuracion.index', compact('configuracion', 'sliders'));
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate($this->reglas($request));

        foreach (self::CLAVES_IDENTIDAD as $clave => $descripcion) {
            ConfiguracionSistema::guardar($clave, (string) $request->input($clave, ''), $descripcion);
        }

        foreach (self::CLAVES_SECCIONES as $clave => $descripcion) {
            ConfiguracionSistema::guardar($clave, $request->boolean($clave) ? '1' : '0', $descripcion);
        }

        foreach (self::CLAVES_TEMA as $clave => $descripcion) {
            ConfiguracionSistema::guardar($clave, (string) $request->input($clave), $descripcion);
        }

        ConfiguracionSistema::guardar('SITIO_MANTENIMIENTO_ACTIVO',$request->boolean('SITIO_MANTENIMIENTO_ACTIVO') ? '1' : '0','Activa o desactiva el modo mantenimiento de la página');

        foreach (self::CLAVES_MANTENIMIENTO as $clave => $descripcion) {
            ConfiguracionSistema::guardar($clave, (string) $request->input($clave, ''), $descripcion);
        }

        ConfiguracionSistema::guardar('IMAGENES_CARRUSEL', json_encode($this->slidersDesdeRequest($request), JSON_UNESCAPED_UNICODE),
            'Imágenes del carrusel del inicio (JSON)');

        $this->registrarAccion(auth()->user()->empleado->id,'UPDATE', 'configuracion_sistema', null,'50');

        return back()->with('exito', 'Configuración del sitio actualizada correctamente.');
    }

    private function reglas(Request $request): array
    {
        $reglas = [];

        foreach (array_keys(self::CLAVES_IDENTIDAD) as $clave) {
            $reglas[$clave] = ['nullable', 'string', 'max:255'];
        }

        foreach (array_keys(self::CLAVES_TEMA) as $clave) {
            $reglas[$clave] = ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'];
        }

        $reglas['SITIO_MANTENIMIENTO_MENSAJE'] = ['nullable', 'string', 'max:500'];

        $reglas['slider.titulo.*'] = ['nullable', 'string', 'max:255'];
        $reglas['slider.subtitulo.*'] = ['nullable', 'string', 'max:255'];
        $reglas['slider.imagen.*'] = ['nullable', 'string', 'max:500'];
        $reglas['slider.boton_texto.*'] = ['nullable', 'string', 'max:100'];
        $reglas['slider.boton_url.*'] = ['nullable', 'string', 'max:255'];

        return $reglas;
    }

    private function slidersDesdeRequest(Request $request): Collection
    {
        $sliders = [];

        $titulos = $request->input('slider.titulo', []);
        $subtitulos = $request->input('slider.subtitulo', []);
        $imagenes = $request->input('slider.imagen', []);
        $botones = $request->input('slider.boton_texto', []);
        $urls = $request->input('slider.boton_url', []);

        foreach ($imagenes as $indice => $imagen) {
            $imagen = trim((string) $imagen);
            $titulo = trim((string) ($titulos[$indice] ?? ''));
            $subtitulo = trim((string) ($subtitulos[$indice] ?? ''));
            $botonTexto = trim((string) ($botones[$indice] ?? ''));
            $botonUrl = trim((string) ($urls[$indice] ?? ''));

            if ($imagen === '' && $titulo === '' && $subtitulo === '') {
                continue;
            }

            $sliders[] = [
                'titulo' => $titulo,
                'subtitulo' => $subtitulo,
                'imagen' => $imagen,
                'boton_texto' => $botonTexto,
                'boton_url' => $botonUrl,
                'orden' => $indice + 1,
            ];
        }

        return collect($sliders);
    }
}
