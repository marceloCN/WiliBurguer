import $ from 'jquery';
import * as bootstrap from 'bootstrap/dist/js/bootstrap.bundle.min.js';
import TomSelect from 'tom-select';
import UtilidadesFormulario from './utilidadesFormulario';

/**
 * Cada import de arriba vive dentro de este archivo (Vite los encapsula).
 * Colgamos todo de 'window' para que los <script type="module"> de las vistas
 * Blade puedan usarlos como si fueran globales.
 */
window.bootstrap = bootstrap;
window.$ = window.jQuery = $;
window.TomSelect = TomSelect;
window.UtilidadesFormulario = UtilidadesFormulario;