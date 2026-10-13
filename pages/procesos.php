<div class="toolbar panel">
    <label class="search-box"><i class="fa-solid fa-magnifying-glass"></i><input id="procBuscar" placeholder="Buscar por PID, usuario, estado o comando..."></label>
    <button class="btn btn-muted" id="procVistaBtn"><i class="fa-solid fa-sitemap"></i> Ver árbol</button>
    <button class="btn btn-muted" data-lanzar="sleep"><i class="fa-solid fa-moon"></i> Lanzar sleep</button>
    <button class="btn btn-muted" data-lanzar="carga"><i class="fa-solid fa-fire"></i> Lanzar carga</button>
    <button class="btn btn-primary" id="procActualizar"><i class="fa-solid fa-arrows-rotate"></i> Actualizar</button>
</div>
<div class="panel" id="procResumen"></div>
<div class="panel table-panel" id="procTablaPanel">
    <div class="table-scroll"><table id="procTabla"><thead></thead><tbody></tbody></table></div>
    <div class="table-footer"><span id="procConteo">Cargando procesos...</span></div>
</div>
<div class="panel" id="procArbolPanel" style="display:none"><div id="procArbol" style="font-family:monospace;font-size:13px"></div></div>
<script src="assets/js/monitor.js" defer></script>
