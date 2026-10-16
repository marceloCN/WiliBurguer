import $ from 'jquery';

/**
 * Librería general de formularios: mensajes de error y consultas rápidas.
 * El envío por AJAX de cada formulario se escribe en su propia vista.
 */
const UtilidadesFormulario = {

    // ---------- Helpers privados ----------

    /**
     * Devuelve los headers comunes para peticiones AJAX a Laravel.
     */
    headersAjax() {
        const headers = {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        };

        const token = $('meta[name="csrf-token"]').attr('content');
        if (token) {
            headers['X-CSRF-TOKEN'] = token;
        }

        return headers;
    },

    // ---------- Mensajes ----------

    /**
     * Marca un campo como inválido y escribe el mensaje debajo.
     * mostrarError('#usuario', 'Debe ingresar su usuario.');
     */
    mostrarError(campo, mensaje) {
        const $campo = $(campo);
        if ($campo.length === 0) return;

        $campo.addClass('is-invalid');
        $campo.siblings('.invalid-feedback').first().text(mensaje);
    },

    /**
     * Muestra la caja de alerta general del formulario.
     * tipo: 'danger' | 'warning' | 'success' | 'info'
     */
    mostrarAlerta(mensaje, tipo = 'danger') {
        const $alerta = $('[data-alerta]');
        if ($alerta.length === 0) return;

        $alerta
            .removeClass('d-none alert-danger alert-warning alert-success alert-info')
            .addClass('alert-' + tipo)
            .find('[data-alerta-msg]')
            .text(mensaje);
    },

    /**
     * Borra los errores del formulario: la alerta y los campos marcados.
     * limpiarFormulario('#login-form');
     */
    limpiarFormulario(selector) {
        const $form = $(selector);

        $form.find('[data-alerta]')
            .addClass('d-none')
            .find('[data-alerta-msg]')
            .text('');

        $form.find('.is-invalid').removeClass('is-invalid');
        $form.find('.invalid-feedback').text('');
    },

    /**
     * Muestra un modal genérico de permiso denegado con la acción y los permisos requeridos.
     * El modal se crea dinámicamente la primera vez y se reutiliza.
     *
     * UtilidadesFormulario.mostrarModalPermisoDenegado('Crear sucursal', '200');
     * UtilidadesFormulario.mostrarModalPermisoDenegado('Editar', '201, 222, 212, 402');
     */
    mostrarModalPermisoDenegado(accion = 'esta acción', permisos = '') {
        let $modal = $('#modalPermisoDenegado');

        if ($modal.length === 0) {
            $modal = $(
                '<div class="modal fade" id="modalPermisoDenegado" tabindex="-1" aria-hidden="true">' +
                    '<div class="modal-dialog modal-dialog-centered">' +
                        '<div class="modal-content border-0 shadow">' +
                            '<div class="modal-header bg-warning text-dark">' +
                                '<h5 class="modal-title fw-bold">⛔ Acceso restringido</h5>' +
                                '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>' +
                            '</div>' +
                            '<div class="modal-body">' +
                                '<p class="mb-2">No tienes permiso para acceder a: ' +
                                    '<strong class="text-danger" id="permiso-accion-nombre"></strong></p>' +
                                '<p class="mb-1 text-muted small">Permisos requeridos para esta acción:</p>' +
                                '<ul id="permiso-lista-requerida" class="mb-0"></ul>' +
                                '<div class="alert alert-light mt-3 mb-0 small">' +
                                    '💡 Si necesitas este permiso, solicítalo al administrador del sistema.' +
                                '</div>' +
                            '</div>' +
                            '<div class="modal-footer">' +
                                '<button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cerrar</button>' +
                            '</div>' +
                        '</div>' +
                    '</div>' +
                '</div>'
            );
            $('body').append($modal);
        }

        $modal.find('#permiso-accion-nombre').text(accion);

        const $lista = $modal.find('#permiso-lista-requerida');
        $lista.empty();

        String(permisos).split(',').forEach((codigo) => {
            const texto = codigo.trim();
            if (texto) {
                $lista.append('<li><code>' + this._escapeHtml(texto) + '</code></li>');
            }
        });

        const modalBootstrap = new window.bootstrap.Modal($modal[0]);
        modalBootstrap.show();
    },

    /**
     * Escapa HTML para prevenir XSS al inyectar texto dinámico en el DOM.
     */
    _escapeHtml(texto) {
        if (texto == null) return '';
        return String(texto)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    },

    /**
     * Saca un mensaje legible de una respuesta fallida de Laravel.
     * 422 -> errors: { campo: [mensajes] }
     */
    mensajeError(xhr, porDefecto = 'Se produjo un error al procesar su solicitud.') {
        const respuesta = xhr?.responseJSON || {};
        const errores = respuesta.errors;

        if (errores && typeof errores === 'object') {
            const campos = Object.keys(errores);
            if (campos.length > 0) {
                const mensajes = errores[campos[0]];
                const texto = Array.isArray(mensajes) ? mensajes[0] : mensajes;
                if (texto) return texto;
            }
        }

        if (respuesta.message) {
            return respuesta.message;
        }

        return porDefecto;
    },

    // ---------- Consultas rápidas ----------

    /** GET que devuelve JSON. */
    get(url, datos = {}) {
        return $.ajax({
            url: url,
            method: 'GET',
            headers: this.headersAjax(),
            data: datos,
            dataType: 'json'
        });
    },

    /** POST que devuelve JSON. Incluye CSRF automáticamente. */
    post(url, datos = {}) {
        return $.ajax({
            url: url,
            method: 'POST',
            headers: this.headersAjax(),
            data: datos,
            dataType: 'json'
        });
    }
};

export default UtilidadesFormulario;
