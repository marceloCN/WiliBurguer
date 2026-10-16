<div class="modal fade" id="ayudaModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold">Ayuda: insumos y unidades de medida</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <div class="accordion" id="acordeonAyuda">
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#ayudaUnidades">
                                ¿Qué es una unidad de medida?
                            </button>
                        </h2>
                        <div id="ayudaUnidades" class="accordion-collapse collapse show" data-bs-parent="#acordeonAyuda">
                            <div class="accordion-body">
                                <p>
                                    Es la forma en que se mide un insumo en el inventario. Por ejemplo:
                                    kilogramos para carne, litros para aceite o unidades para huevos.
                                </p>
                                <p class="mb-0">
                                    <strong>Tipos recomendados:</strong>
                                </p>
                                <ul class="mb-0">
                                    <li><strong>Peso:</strong> g, kg, lb, oz (carnes, verduras, granos).</li>
                                    <li><strong>Volumen:</strong> ml, l, gal (líquidos, salsas).</li>
                                    <li><strong>Unidad / pieza:</strong> und, pza, docena (huevos, empaques).</li>
                                    <li><strong>Longitud:</strong> cm, m (útiles de menor uso).</li>
                                    <li><strong>Otro:</strong> cualquier abreviatura personalizada.</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#ayudaRelacion">
                                ¿Cómo relaciono una unidad con un insumo?
                            </button>
                        </h2>
                        <div id="ayudaRelacion" class="accordion-collapse collapse" data-bs-parent="#acordeonAyuda">
                            <div class="accordion-body">
                                <ol class="mb-0">
                                    <li>Crea la unidad de medida con el botón <strong>+ Unidad</strong>.</li>
                                    <li>Crea el insumo con el botón <strong>+ Insumo</strong>.</li>
                                    <li>En el formulario de insumo, selecciona la unidad que le corresponde.</li>
                                    <li>El insumo activo aparecerá disponible en el inventario de cualquier sucursal.</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#ayudaEstado">
                                ¿Puedo desactivar una unidad o insumo?
                            </button>
                        </h2>
                        <div id="ayudaEstado" class="accordion-collapse collapse" data-bs-parent="#acordeonAyuda">
                            <div class="accordion-body mb-0">
                                <p class="mb-0">
                                    Sí. Puedes activar o desactivar insumos y unidades. Sin embargo,
                                    <strong>no podrás desactivar una unidad que tenga insumos activos asignados</strong>,
                                    para evitar inconsistencias en el inventario.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <a href="{{ route('dashboard.insumos.ayuda.pdf') }}" class="btn btn-outline-brand">Descargar guía PDF</a>
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
