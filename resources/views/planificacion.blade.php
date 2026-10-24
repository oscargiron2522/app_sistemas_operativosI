@extends('layouts.app')
@section('titulo', 'Planificación de CPU')
@section('subtitulo', 'Simulador de algoritmos de planificación de procesos')
@section('contenido')
<div class="row g-3 mb-3">
    <div class="col-lg-5"><div class="lc-card">
        <h5>Configuración</h5>
        <label class="form-label mb-1">Algoritmo</label>
        <select id="algoritmo" class="form-select mb-3">
            @foreach ($algoritmos as $k => $n)<option value="{{ $k }}">{{ $n }}</option>@endforeach
        </select>
        <label class="form-label mb-1">Quantum (solo RR)</label>
        <input type="number" id="quantum" class="form-control mb-3" value="2" min="1" max="100">
        <div class="d-flex flex-wrap gap-2">
            <button class="btn btn-lc" id="btn-add"><i class="bi bi-plus-lg me-1"></i> Agregar proceso</button>
            <button class="btn btn-gris" id="btn-limpiar">Limpiar</button>
            <button class="btn btn-gris" id="btn-azar"><i class="bi bi-shuffle me-1"></i> Aleatorio</button>
        </div>
        <button class="btn btn-success w-100 mt-3" id="btn-sim"><i class="bi bi-play-fill me-1"></i> Simular y guardar</button>
    </div></div>
    <div class="col-lg-7"><div class="lc-card">
        <h5>Procesos</h5>
        <div class="lc-wrap">
        <table class="lc-tabla">
            <thead><tr><th>PID</th><th>Tiempo Llegada</th><th>Ráfaga CPU</th><th>Prioridad</th><th>Acciones</th></tr></thead>
            <tbody id="filas"></tbody>
        </table>
        </div>
        <div class="text-muted small mt-2">Prioridad: el número menor tiene mayor prioridad. Máximo 30 procesos.</div>
    </div></div>
</div>

<div class="lc-card mb-3">
    <h5>Diagrama de Gantt</h5>
    <div id="gantt-vacio" class="text-muted">Pulse «Simular y guardar» para ver el diagrama.</div>
    <div id="gantt-cont" style="display:none">
        <div class="gantt" id="gantt"></div>
        <div class="gantt-eje" id="gantt-eje"></div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-3"><div class="lc-card lc-stat"><div class="lc-etq">Tiempo de espera promedio</div><div class="lc-valor" id="s-espera">–</div></div></div>
    <div class="col-md-3"><div class="lc-card lc-stat"><div class="lc-etq">Tiempo de retorno promedio</div><div class="lc-valor" id="s-retorno">–</div></div></div>
    <div class="col-md-3"><div class="lc-card lc-stat"><div class="lc-etq">Tiempo de respuesta promedio</div><div class="lc-valor" id="s-resp">–</div></div></div>
    <div class="col-md-3"><div class="lc-card lc-stat"><div class="lc-etq">Utilización de CPU</div><div class="lc-valor" id="s-util">–</div></div></div>
</div>

<div class="row g-3 mb-3">
    <div class="col-lg-6"><div class="lc-card">
        <h5>Tiempos por proceso</h5>
        <div class="lc-wrap"><table class="lc-tabla"><thead><tr><th>PID</th><th>Llegada</th><th>Ráfaga</th><th>Prior.</th><th>Fin</th><th>Retorno</th><th>Espera</th><th>Resp.</th></tr></thead>
        <tbody id="res-filas"><tr><td colspan="8" class="text-center text-muted py-3">Sin simulación</td></tr></tbody></table></div>
    </div></div>
    <div class="col-lg-6"><div class="lc-card">
        <h5>Comparativa entre algoritmos</h5>
        <div class="lc-wrap"><table class="lc-tabla"><thead><tr><th>Algoritmo</th><th>Espera prom.</th><th>Retorno prom.</th><th>Resp. prom.</th><th>CPU %</th></tr></thead>
        <tbody id="comp-filas"><tr><td colspan="5" class="text-center text-muted py-3">Sin simulación</td></tr></tbody></table></div>
        <div class="text-muted small mt-2">Se calcula con los mismos procesos y el mismo quantum. En verde, el menor tiempo de espera promedio.</div>
    </div></div>
</div>

<div class="lc-card">
    <h5>Simulaciones guardadas</h5>
    <div class="lc-wrap"><table class="lc-tabla"><thead><tr><th>#</th><th>Fecha</th><th>Algoritmo</th><th>Procesos</th><th>Espera prom.</th><th>Retorno prom.</th><th>CPU %</th><th></th></tr></thead>
    <tbody id="hist"></tbody></table></div>
</div>
@endsection
@push('scripts')
<script>
const U = '{{ url('/planificacion') }}';
const COLORES = ['#ff9aa2', '#ffd479', '#6fd8b9', '#7ab8ff', '#c8a2ff', '#ffb38a', '#8fe3e8', '#d4e08a', '#f4a6d7', '#b5d99c'];
const tbody = document.getElementById('filas');

function fila(llegada, rafaga, prioridad) {
  const tr = document.createElement('tr');
  tr.innerHTML = `<td class="pid-celda fw-semibold"></td>
    <td><input type="number" class="form-control f-llegada" min="0" max="1000" value="${llegada}"></td>
    <td><input type="number" class="form-control f-rafaga" min="1" max="1000" value="${rafaga}"></td>
    <td><input type="number" class="form-control f-prio" min="0" max="100" value="${prioridad}"></td>
    <td><button class="btn-sq rojo" title="Eliminar"><i class="bi bi-trash3"></i></button></td>`;
  tr.querySelector('button').onclick = () => { tr.remove(); numerar(); };
  tbody.appendChild(tr);
  numerar();
}
function numerar() { [...tbody.rows].forEach((tr, i) => tr.querySelector('.pid-celda').textContent = 'P' + (i + 1)); }
function leer() {
  return [...tbody.rows].map(tr => ({
    llegada: parseInt(tr.querySelector('.f-llegada').value, 10),
    rafaga: parseInt(tr.querySelector('.f-rafaga').value, 10),
    prioridad: parseInt(tr.querySelector('.f-prio').value, 10),
  }));
}
function cargarFilas(lista) { tbody.innerHTML = ''; lista.forEach(p => fila(p.llegada, p.rafaga, p.prioridad)); }
const colorDe = pid => pid === 'IDLE' ? null : COLORES[(parseInt(pid.substring(1), 10) - 1) % COLORES.length];

function mostrar(r) {
  const total = r.duracion;
  document.getElementById('gantt-vacio').style.display = 'none';
  document.getElementById('gantt-cont').style.display = '';
  document.getElementById('gantt').innerHTML = r.gantt.map(s => {
    const c = colorDe(s.pid);
    return `<div class="${c ? '' : 'idle'}" style="width:${100 * (s.fin - s.inicio) / total}%;${c ? 'background:' + c : ''}" title="${s.pid}: ${s.inicio} → ${s.fin}">${c ? s.pid : 'Ocioso'}</div>`;
  }).join('');
  const marcas = [...new Set([0, ...r.gantt.map(s => s.fin)])];
  document.getElementById('gantt-eje').innerHTML = marcas.map(m => `<span style="left:${100 * m / total}%">${m}</span>`).join('');
  document.getElementById('s-espera').textContent = r.espera_promedio.toFixed(2);
  document.getElementById('s-retorno').textContent = r.retorno_promedio.toFixed(2);
  document.getElementById('s-resp').textContent = r.respuesta_promedio.toFixed(2);
  document.getElementById('s-util').textContent = r.utilizacion.toFixed(1) + ' %';
  document.getElementById('res-filas').innerHTML = r.procesos.map(p =>
    `<tr><td><strong>${p.pid}</strong></td><td>${p.llegada}</td><td>${p.rafaga}</td><td>${p.prioridad}</td><td>${p.finalizacion}</td><td>${p.retorno}</td><td>${p.espera}</td><td>${p.respuesta}</td></tr>`).join('');
  const mejor = Math.min(...r.comparativa.map(c => c.espera));
  document.getElementById('comp-filas').innerHTML = r.comparativa.map(c =>
    `<tr class="${c.algoritmo === r.algoritmo ? 'table-primary' : ''}"><td>${LC.esc(c.nombre)}</td><td>${c.espera === mejor ? '<span class="badge-ok">' + c.espera.toFixed(2) + '</span>' : c.espera.toFixed(2)}</td><td>${c.retorno.toFixed(2)}</td><td>${c.respuesta.toFixed(2)}</td><td>${c.utilizacion.toFixed(1)}</td></tr>`).join('');
}

async function historial() {
  try {
    const d = await LC.get(U + '/historial');
    document.getElementById('hist').innerHTML = d.historial.map(h =>
      `<tr><td>${h.id}</td><td>${h.fecha}</td><td>${LC.esc(h.algoritmo)}${h.quantum ? ' (q=' + h.quantum + ')' : ''}</td><td>${h.n}</td><td>${h.espera.toFixed(2)}</td><td>${h.retorno.toFixed(2)}</td><td>${h.utilizacion.toFixed(1)}</td>
       <td><button class="btn btn-sm btn-lc" onclick="verSim(${h.id})">Ver</button></td></tr>`).join('')
      || '<tr><td colspan="8" class="text-center text-muted py-3">Aún no hay simulaciones guardadas.</td></tr>';
  } catch (e) { console.error(e); }
}

async function verSim(id) {
  try {
    const d = await LC.get(U + '/' + id);
    document.getElementById('algoritmo').value = d.algoritmo;
    if (d.quantum) document.getElementById('quantum').value = d.quantum;
    cargarFilas(d.procesos);
    mostrar(d.resultado);
    window.scrollTo({ top: 0, behavior: 'smooth' });
  } catch (e) { LC.toast(e.message, 'danger'); }
}

document.getElementById('btn-add').onclick = () => { if (tbody.rows.length < 30) fila(0, 1, 1); };
document.getElementById('btn-limpiar').onclick = () => { tbody.innerHTML = ''; };
document.getElementById('btn-azar').onclick = () => {
  const n = 3 + Math.floor(Math.random() * 4), r = (a, b) => a + Math.floor(Math.random() * (b - a + 1));
  cargarFilas(Array.from({ length: n }, () => ({ llegada: r(0, 8), rafaga: r(1, 9), prioridad: r(1, 5) })));
};
document.getElementById('btn-sim').onclick = async () => {
  const procesos = leer();
  if (!procesos.length) return LC.toast('Agregue al menos un proceso.', 'warning');
  if (procesos.some(p => [p.llegada, p.rafaga, p.prioridad].some(Number.isNaN))) return LC.toast('Complete todos los campos con números.', 'warning');
  try {
    const r = await LC.post(U + '/simular', { algoritmo: document.getElementById('algoritmo').value, quantum: parseInt(document.getElementById('quantum').value, 10) || 2, procesos });
    mostrar(r);
    LC.toast('Simulación #' + r.id + ' guardada.');
    historial();
  } catch (e) { LC.toast(e.message, 'danger'); }
};

cargarFilas([{ llegada: 0, rafaga: 5, prioridad: 1 }, { llegada: 1, rafaga: 3, prioridad: 1 }, { llegada: 2, rafaga: 8, prioridad: 1 }, { llegada: 3, rafaga: 6, prioridad: 1 }]);
historial();
</script>
@endpush
