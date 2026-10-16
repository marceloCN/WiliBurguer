<div class="modal fade" id="crearUsuarioModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="{{ route('dashboard.usuarios.store') }}">
                @csrf
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold">Nuevo usuario</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nombre *</label>
                            <input type="text" name="nombre" class="form-control crear-nombre" placeholder="Ingresar nombre..." required maxlength="100">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Apellido *</label>
                            <input type="text" name="apellido" class="form-control crear-apellido" placeholder="Ingresar apellido..." required maxlength="100">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Teléfono</label>
                            <input type="text" name="telefono" placeholder="+591" class="form-control" maxlength="15">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Rol *</label>
                            <select name="idrol" class="form-select" required>
                                @foreach ($roles as $rol)
                                    <option value="{{ $rol->id }}">{{ $rol->nombrerol }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Usuario *</label>
                            <input type="text" name="usuario" class="form-control crear-usuario" required maxlength="255" placeholder="nombre.apellido">
                            <div class="form-text">Sugerido automáticamente según nombre y apellido.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Correo *</label>
                            <input type="email" name="correo" class="form-control" required maxlength="255">
                        </div>
                    </div>

                    <div class="alert alert-info mt-3 mb-0">
                        <i class="bi bi-info-circle"></i>
                        La contraseña se generará automáticamente. Se mostrará en la siguiente pantalla junto al usuario.
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-brand">Crear usuario</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    $(function () {
        const $modal = $('#crearUsuarioModal');
        const $nombre = $modal.find('.crear-nombre');
        const $apellido = $modal.find('.crear-apellido');
        const $usuario = $modal.find('.crear-usuario');

        if ($modal.length === 0 || $nombre.length === 0 || $apellido.length === 0 || $usuario.length === 0) {
            return;
        }

        let usuarioEditadoManualmente = false;

        $usuario.on('input', function () {
            usuarioEditadoManualmente = true;
        });

        function sugerirUsuario() {
            if (usuarioEditadoManualmente) {
                return;
            }

            const n = $nombre.val().trim().toLowerCase().replace(/\s+/g, '.');
            const a = $apellido.val().trim().toLowerCase().replace(/\s+/g, '.');

            if (n && a) {
                $usuario.val(n + '.' + a);
            } else if (n) {
                $usuario.val(n);
            } else if (a) {
                $usuario.val(a);
            } else {
                $usuario.val('');
            }
        }

        $nombre.on('input', sugerirUsuario);
        $apellido.on('input', sugerirUsuario);

        $modal.on('hidden.bs.modal', function () {
            usuarioEditadoManualmente = false;
        });
    });
</script>
