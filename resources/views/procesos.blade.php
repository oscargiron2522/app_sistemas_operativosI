@extends('layouts.app')
@section('titulo', 'Procesos')
@section('subtitulo', 'Listado y administración de procesos del sistema')
@section('contenido')
<div class="lc-card mb-3">
    <div class="d-flex flex-wrap gap-2 align-items-center">
        <strong class="me-2">Estados:</strong>
        <span class="badge text-bg-success" title="Running">R en ejecución: <span id="r-R">0</span></span>
        <span class="badge text-bg-primary" title="Sleeping">S durmiendo: <span id="r-S">0</span></span>
        <span class="badge text-bg-warning" title="Uninterruptible sleep">D espera E/S: <span id="r-D">0</span></span>
        <span class="badge text-bg-danger" title="Zombie">Z zombie: <span id="r-Z">0</span></span>
        <span class="badge text-bg-secondary" title="Stopped">T detenido: <span id="r-T">0</span></span>
        <span class="badge text-bg-light border">Otros: <span id="r-otros">0</span></span>
        <span class="ms-auto text-muted">Total: <strong id="r-total">0</strong> procesos</span>
    </div>
</div>

<div class="lc-card">
    <ul class="nav nav-tabs mb-3">
        <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-tabla">Tabla</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-arbol">Árbol de procesos</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-pruebas">Procesos de prueba</button></li>
    </ul>
    <div class="tab-content">
        <div class="tab-pane fade show active" id="tab-tabla">
            <div class="d-flex flex-wrap gap-2 mb-3 align-items-center">
                <div class="input-group" style="max-width:520px;flex:1">
                    <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                    <input type="text" id="buscar" class="form-control" placeholder="Buscar por PID, usuario, nombre, estado o comando...">
                </div>
                <select id="por-pagina" class="form-select w-auto"><option>10</option><option>25</option><option>50</option><option>100</option></select>
                <div class="form-check form-switch ms-2"><input class="form-check-input" type="checkbox" id="auto" checked><label class="form-check-label" for="auto">Auto (5 s)</label></div>
                <button class="btn btn-lc ms-auto" id="btn-act"><i class="bi bi-arrow-repeat me-1"></i> Actualizar</button>
            </div>
            <div class="lc-wrap">
            <table class="lc-tabla" id="tabla">
                <thead><tr>
                    <th class="orden" data-c="pid">PID</th><th class="orden" data-c="ppid">PPID</th>
                    <th class="orden" data-c="usuario">Usuario</th><th class="orden" data-c="nombre">Nombre</th>
                    <th class="orden" data-c="cpu">CPU %</th><th class="orden" data-c="mem_pct">Memoria %</th>
                    <th class="orden" data-c="nice">Nice</th><th class="orden" data-c="estado">Estado</th>
                    <th class="orden" data-c="comando">Comando</th><th>Acciones</th>
                </tr></thead>
                <tbody id="filas"></tbody>
            </table>
            </div>
            <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap gap-2">
                <span id="info" class="text-muted"></span>
                <ul class="pagination mb-0" id="paginas"></ul>
            </div>
        </div>

        <div class="tab-pane fade" id="tab-arbol">
            <p class="text-muted">Relación padre-hijo según el PPID de cada proceso. Haga clic en un nodo para expandir o contraer.</p>
            <div id="arbol-cont" class="lc-wrap" style="max-height:560px;overflow:auto"></div>
        </div>

        <div class="tab-pane fade" id="tab-pruebas">
            @if (auth()->user()->esAdmin())
            <div class="d-flex flex-wrap gap-2 align-items-end mb-3">
                <div><label class="form-label mb-1">Tipo</label>
                    <select id="p-tipo" class="form-select"><option value="sleep">sleep (proceso dormido)</option><option value="carga">Script de carga de CPU</option></select></div>
                <div id="p-dur-box"><label class="form-label mb-1">Duración (s)</label>
                    <input type="number" id="p-dur" class="form-control" value="300" min="10" max="3600" style="width:110px"></div>
                <button class="btn btn-lc" id="p-lanzar"><i class="bi bi-play-fill me-1"></i> Lanzar proceso de prueba</button>
            </div>
            @else
            <div class="alert alert-info">Rol Observador: solo lectura. Un Administrador puede lanzar procesos de prueba y enviarles señales.</div>
            @endif
            <div class="lc-wrap">
            <table class="lc-tabla">
                <thead><tr><th>PID</th><th>Comando</th><th>Estado</th><th>Nice</th><th>CPU %</th><th>Creado</th>@if (auth()->user()->esAdmin())<th>Enviar señal</th><th>Cambiar prioridad (renice)</th>@endif</tr></thead>
                <tbody id="filas-pruebas"><tr><td colspan="8" class="text-center text-muted py-3">Cargando...</td></tr></tbody>
            </table>
            </div>
            <p class="text-muted mt-2 mb-0 small">Solo estos procesos (lanzados por la aplicación) aceptan señales y renice. Sin privilegios de root únicamente se puede aumentar el valor nice (0 a 19).</p>
        </div>
    </div>
</div>

<div class="modal fade" id="modal-det" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title">Detalle del proceso</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body"><table class="lc-tabla"><tbody id="det-cuerpo"></tbody></table></div>
</div></div></div>
@endsection
@push('scripts')
<script>
const ES_ADMIN = {{ auth()->user()->esAdmin() ? 'true' : 'false' }};
const U = '{{ url('/procesos') }}';
let todos = [], orden = { c: 'pid', d: 1 }, pagina = 1, porPagina = 10;

const cmp = (a, b) => (typeof a === 'number' && typeof b === 'number') ? a - b : String(a).localeCompare(String(b));

function filtrados() {
  const q = document.getElementById('buscar').value.trim().toLowerCase();
  let l = q ? todos.filter(p => [p.pid, p.ppid, p.usuario, p.nombre, p.estado, p.comando].join(' ').toLowerCase().includes(q)) : todos.slice();
  l.sort((a, b) => cmp(a[orden.c], b[orden.c]) * orden.d);
  return l;
}

function pintarTabla() {
  const l = filtrados();
  const paginas = Math.max(1, Math.ceil(l.length / porPagina));
  if (pagina > paginas) pagina = paginas;
  const ini = (pagina - 1) * porPagina;
  document.getElementById('filas').innerHTML = l.slice(ini, ini + porPagina).map(p => `
    <tr>
      <td>${p.pid}</td><td>${p.ppid}</td><td>${LC.esc(p.usuario)}</td><td>${LC.esc(p.nombre)}</td>
      <td>${p.cpu.toFixed(1)}</td><td>${p.mem_pct.toFixed(1)}</td><td>${p.nice}</td>
      <td><span class="estado-pill">${LC.esc(p.estado)}</span></td>
      <td style="max-width:320px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="${LC.esc(p.comando)}">${LC.esc(p.comando)}</td>
      <td class="text-nowrap">
        <button class="btn-sq azul" title="Detalle" onclick="detalle(${p.pid})"><i class="bi bi-search"></i></button>
        <button class="btn-sq rojo" title="${p.prueba ? 'Terminar (SIGTERM)' : 'Solo se pueden controlar procesos de prueba'}" ${(p.prueba && ES_ADMIN) ? '' : 'disabled'} onclick="senal(${p.pid},'SIGTERM')"><i class="bi bi-trash3"></i></button>
      </td>
    </tr>`).join('') || '<tr><td colspan="10" class="text-center text-muted py-3">Sin resultados</td></tr>';
  document.getElementById('info').textContent = l.length
    ? `Mostrando ${ini + 1} a ${Math.min(ini + porPagina, l.length)} de ${l.length} procesos` : 'Sin procesos';

  let h = `<li class="page-item ${pagina === 1 ? 'disabled' : ''}"><a class="page-link" href="#" data-p="${pagina - 1}">&lsaquo;</a></li>`;
  const desde = Math.max(1, Math.min(pagina - 2, paginas - 4)), hasta = Math.min(paginas, desde + 4);
  for (let i = desde; i <= hasta; i++) h += `<li class="page-item ${i === pagina ? 'active' : ''}"><a class="page-link" href="#" data-p="${i}">${i}</a></li>`;
  h += `<li class="page-item ${pagina === paginas ? 'disabled' : ''}"><a class="page-link" href="#" data-p="${pagina + 1}">&rsaquo;</a></li>`;
  document.getElementById('paginas').innerHTML = h;
}

function pintarArbol() {
  const mapa = new Map(todos.map(p => [p.pid, { ...p, hijos: [] }]));
  const raices = [];
  mapa.forEach(n => { const padre = mapa.get(n.ppid); (padre && padre !== n ? padre.hijos : raices).push(n); });
  const nodo = (n, prof) => {
    const txt = `<span class="pid">${n.pid}</span> ${LC.esc(n.nombre)} <small class="text-muted">(${LC.esc(n.usuario)}, ${LC.esc(n.estado)})</small>` + (n.prueba ? ' <span class="prueba">prueba</span>' : '');
    if (!n.hijos.length) return `<li>${txt}</li>`;
    return `<li><details ${prof < 2 ? 'open' : ''}><summary>${txt} <small class="text-muted">[${n.hijos.length}]</small></summary><ul>${n.hijos.map(h => nodo(h, prof + 1)).join('')}</ul></details></li>`;
  };
  document.getElementById('arbol-cont').innerHTML = '<ul class="arbol">' + raices.map(r => nodo(r, 0)).join('') + '</ul>';
}

async function cargar() {
  try {
    const d = await LC.get(U + '/datos');
    todos = d.procesos;
    for (const k in d.resumen) document.getElementById('r-' + k).textContent = d.resumen[k];
    document.getElementById('r-total').textContent = d.total;
    pintarTabla();
    if (document.getElementById('tab-arbol').classList.contains('active')) pintarArbol();
  } catch (e) { LC.toast(e.message, 'danger'); }
}

async function cargarPruebas() {
  try {
    const d = await LC.get(U + '/pruebas');
    const cols = ES_ADMIN ? 8 : 6;
    document.getElementById('filas-pruebas').innerHTML = d.pruebas.map(p => `
      <tr><td>${p.pid}</td><td>${LC.esc(p.comando)}</td><td><span class="estado-pill">${LC.esc(p.estado)}</span></td><td>${p.nice}</td><td>${p.cpu.toFixed(1)}</td><td>${LC.esc(p.creado)}</td>
      ${ES_ADMIN ? `<td class="text-nowrap">
        <select id="sig-${p.pid}" class="form-select form-select-sm d-inline-block w-auto"><option>SIGTERM</option><option>SIGKILL</option><option>SIGSTOP</option><option>SIGCONT</option></select>
        <button class="btn btn-sm btn-lc" onclick="senal(${p.pid}, document.getElementById('sig-${p.pid}').value)">Enviar</button></td>
        <td class="text-nowrap"><input type="number" id="nice-${p.pid}" min="0" max="19" value="${Math.max(0, p.nice)}" class="form-control form-control-sm d-inline-block" style="width:70px">
        <button class="btn btn-sm btn-gris" onclick="renice(${p.pid})">Aplicar</button></td>` : ''}</tr>`).join('')
      || `<tr><td colspan="${cols}" class="text-center text-muted py-3">No hay procesos de prueba activos.</td></tr>`;
  } catch (e) { console.error(e); }
}

async function detalle(pid) {
  try {
    const d = await LC.get(U + '/' + pid);
    document.getElementById('det-cuerpo').innerHTML = Object.entries(d.datos).map(([k, v]) => `<tr><th style="width:230px">${LC.esc(k)}</th><td>${LC.esc(v)}</td></tr>`).join('')
      + `<tr><th>Proceso de prueba</th><td>${d.prueba ? 'Sí (se puede controlar)' : 'No (solo lectura)'}</td></tr>`;
    new bootstrap.Modal('#modal-det').show();
  } catch (e) { LC.toast(e.message, 'danger'); }
}

async function senal(pid, s) {
  if ((s === 'SIGKILL' || s === 'SIGTERM') && !confirm('¿Enviar ' + s + ' al PID ' + pid + '?')) return;
  try { const r = await LC.post(U + '/senal', { pid, senal: s }); LC.toast(r.mensaje); }
  catch (e) { LC.toast(e.message, 'danger'); }
  setTimeout(() => { cargar(); cargarPruebas(); }, 300);
}

async function renice(pid) {
  try { const r = await LC.post(U + '/renice', { pid, nice: parseInt(document.getElementById('nice-' + pid).value, 10) }); LC.toast(r.mensaje); }
  catch (e) { LC.toast(e.message, 'danger'); }
  cargar(); cargarPruebas();
}

document.getElementById('buscar').addEventListener('input', () => { pagina = 1; pintarTabla(); });
document.getElementById('por-pagina').addEventListener('change', e => { porPagina = parseInt(e.target.value, 10); pagina = 1; pintarTabla(); });
document.getElementById('btn-act').addEventListener('click', () => { cargar(); cargarPruebas(); });
document.getElementById('paginas').addEventListener('click', e => { e.preventDefault(); const p = e.target.closest('a')?.dataset.p; if (p) { pagina = parseInt(p, 10); pintarTabla(); } });
document.querySelectorAll('#tabla th.orden').forEach(th => th.addEventListener('click', () => {
  orden = { c: th.dataset.c, d: orden.c === th.dataset.c ? -orden.d : 1 }; pintarTabla();
}));
document.querySelector('[data-bs-target="#tab-arbol"]').addEventListener('shown.bs.tab', pintarArbol);
document.querySelector('[data-bs-target="#tab-pruebas"]').addEventListener('shown.bs.tab', cargarPruebas);

if (ES_ADMIN) {
  document.getElementById('p-tipo').addEventListener('change', e => { document.getElementById('p-dur-box').style.display = e.target.value === 'sleep' ? '' : 'none'; });
  document.getElementById('p-lanzar').addEventListener('click', async () => {
    try {
      const r = await LC.post(U + '/lanzar', { tipo: document.getElementById('p-tipo').value, duracion: parseInt(document.getElementById('p-dur').value, 10) || 300 });
      LC.toast(r.mensaje);
    } catch (e) { LC.toast(e.message, 'danger'); }
    cargar(); cargarPruebas();
  });
}

cargar(); cargarPruebas();
setInterval(() => { if (document.getElementById('auto').checked) { cargar(); cargarPruebas(); } }, 5000);
</script>
@endpush
