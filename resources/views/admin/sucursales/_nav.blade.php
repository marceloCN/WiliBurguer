<ul class="nav nav-pills seccion-tabs mb-4 flex-wrap gap-2" id="sucursalTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="tab-datos-tab" data-bs-toggle="pill" data-bs-target="#tab-datos"
            type="button" role="tab" aria-controls="tab-datos" aria-selected="true">🏪 Datos y ubicación</button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="tab-horarios-tab" data-bs-toggle="pill" data-bs-target="#tab-horarios"
            type="button" role="tab" aria-controls="tab-horarios" aria-selected="false">🕒 Horarios</button>
    </li>
    @if ($puedeGestionarMesas)
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="tab-mesas-tab" data-bs-toggle="pill" data-bs-target="#tab-mesas"
                type="button" role="tab" aria-controls="tab-mesas" aria-selected="false">🪑 Mesas</button>
        </li>
    @endif
    @if ($puedeGestionarCajas)
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="tab-cajas-tab" data-bs-toggle="pill" data-bs-target="#tab-cajas"
                type="button" role="tab" aria-controls="tab-cajas" aria-selected="false">🛢️ Cajas</button>
        </li>
    @endif
</ul>
