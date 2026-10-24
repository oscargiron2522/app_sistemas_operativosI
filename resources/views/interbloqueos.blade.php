@extends('layouts.app')
@section('titulo', 'Interbloqueos')
@section('subtitulo', 'Simulador del algoritmo del banquero y detección de interbloqueos')
@section('contenido')
<div class="lc-card">
    <ul class="nav nav-tabs mb-3">
        <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-banquero">Algoritmo del Banquero</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-deteccion">Detección de Interbloqueos</button></li>
    </ul>
    <div class="tab-content">

        {{-- ================= BANQUERO ================= --}}
        <div class="tab-pane fade show active" id="tab-banquero">
            <div class="row g-4 mb-3">
                <div class="col-md-7">
                    <h6>Recursos</h6>
                    <label class="form-label mb-1">Número de recursos:</label>
                    <input type="number" id="b-m" class="form-control mb-2" value="3" min="1" max="8">
                    <label class="form-label mb-1">Instancias totales por recurso (A, B, C...):</label>
                    <div id="b-totales" class="d-flex gap-2 flex-wrap"></div>
                </div>
                <div class="col-md-5">
                    <h6>Procesos</h6>
                    <label class="form-label mb-1">Número de procesos:</label>
                    <input type="number" id="b-n" class="form-control mb-3" value="5" min="1" max="10">
                    <button class="btn btn-lc w-100" id="b-gen">Generar tablas</button>
                </div>
            </div>
            <div class="row g-3 mb-3">
                <div class="col-lg-4"><div class="lc-card"><h6>Matriz de Asignación</h6><div id="b-asig" class="lc-wrap"></div></div></div>
                <div class="col-lg-4"><div class="lc-card"><h6>Matriz Máxima</h6><div id="b-max" class="lc-wrap"></div></div></div>
                <div class="col-lg-4"><div class="lc-card"><h6>Recursos Disponibles</h6><div id="b-disp" class="lc-wrap"></div>
                    <button class="btn btn-sm btn-gris mt-2" id="b-calc">Calcular desde totales − asignación</button></div></div>
            </div>
            <div class="d-flex gap-2 flex-wrap mb-3">
                <button class="btn btn-success" id="b-run"><i class="bi bi-play-fill me-1"></i> Ejecutar algoritmo del banquero</button>
                <button class="btn btn-gris" id="b-ej">Cargar ejemplo</button>
            </div>
            <div id="b-res"></div>

            <hr>
            <h6>Evaluar una solicitud de recursos</h6>
            <div class="d-flex flex-wrap gap-2 align-items-end mb-3">
                <div><label class="form-label mb-1">Proceso</label><select id="s-proc" class="form-select"></select></div>
                <div><label class="form-label mb-1">Solicitud</label><div id="s-vec" class="d-flex gap-2"></div></div>
                <button class="btn btn-lc" id="s-run">Evaluar solicitud</button>
            </div>
            <div id="s-res"></div>
        </div>

        {{-- ================= DETECCION ================= --}}
        <div class="tab-pane fade" id="tab-deteccion">
            <div class="row g-3 mb-3 align-items-end">
                <div class="col-sm-3"><label class="form-label mb-1">Número de procesos</label><input type="number" id="d-n" class="form-control" value="3" min="1" max="10"></div>
                <div class="col-sm-3"><label class="form-label mb-1">Número de recursos</label><input type="number" id="d-m" class="form-control" value="3" min="1" max="8"></div>
                <div class="col-sm-3"><button class="btn btn-lc w-100" id="d-gen">Generar tablas</button></div>
                <div class="col-sm-3"><button class="btn btn-gris w-100" id="d-ej">Cargar ejemplo con interbloqueo</button></div>
            </div>
            <div class="row g-3 mb-3">
                <div class="col-lg-4"><div class="lc-card"><h6>Matriz de Asignación (recurso → proceso)</h6><div id="d-asig" class="lc-wrap"></div></div></div>
                <div class="col-lg-4"><div class="lc-card"><h6>Matriz de Solicitud (proceso → recurso)</h6><div id="d-sol" class="lc-wrap"></div></div></div>
                <div class="col-lg-4"><div class="lc-card"><h6>Recursos Disponibles</h6><div id="d-disp" class="lc-wrap"></div></div></div>
            </div>
            <button class="btn btn-success mb-3" id="d-run"><i class="bi bi-search me-1"></i> Detectar interbloqueo</button>
            <div id="d-res"></div>
        </div>
    </div>
</div>
@endsection
@push('scripts')
<script>
const U = '{{ url('/interbloqueos') }}';
const L = j => String.fromCharCode(65 + j);

// ---- utilidades de matrices ----
function tabla(id, filas, cols, datos) {
  let h = '<table class="lc-tabla"><thead><tr><th>Proceso</th>' + Array.from({ length: cols }, (_, j) => `<th>${L(j)}</th>`).join('') + '</tr></thead><tbody>';
  for (let i = 0; i < filas; i++) {
    h += `<tr><td class="fw-semibold">P${i}</td>` + Array.from({ length: cols }, (_, j) =>
      `<td><input type="number" min="0" class="form-control mat-input" data-i="${i}" data-j="${j}" value="${datos?.[i]?.[j] ?? 0}"></td>`).join('') + '</tr>';
  }
  document.getElementById(id).innerHTML = h + '</tbody></table>';
}
function vectorTabla(id, cols, datos) {
  document.getElementById(id).innerHTML = '<table class="lc-tabla"><thead><tr>' + Array.from({ length: cols }, (_, j) => `<th>${L(j)}</th>`).join('') + '</tr></thead><tbody><tr>'
    + Array.from({ length: cols }, (_, j) => `<td><input type="number" min="0" class="form-control mat-input" data-j="${j}" value="${datos?.[j] ?? 0}"></td>`).join('') + '</tr></tbody></table>';
}
const leerM = id => { const f = []; document.querySelectorAll(`#${id} input`).forEach(e => { (f[+e.dataset.i] ??= [])[+e.dataset.j] = parseInt(e.value, 10) || 0; }); return f; };
const leerV = id => [...document.querySelectorAll(`#${id} input`)].map(e => parseInt(e.value, 10) || 0);
const fmt = v => '[' + v.join(', ') + ']';

// ---- BANQUERO ----
function totales(m, datos) {
  document.getElementById('b-totales').innerHTML = Array.from({ length: m }, (_, j) =>
    `<div><div class="small text-muted text-center">${L(j)}</div><input type="number" min="0" class="form-control mat-input b-tot" value="${datos?.[j] ?? 0}"></div>`).join('');
}
function generarB(datos) {
  const n = Math.min(10, Math.max(1, +document.getElementById('b-n').value)), m = Math.min(8, Math.max(1, +document.getElementById('b-m').value));
  tabla('b-asig', n, m, datos?.asig); tabla('b-max', n, m, datos?.max); vectorTabla('b-disp', m, datos?.disp);
  if (!datos) totales(m);
  document.getElementById('s-proc').innerHTML = Array.from({ length: n }, (_, i) => `<option value="${i}">P${i}</option>`).join('');
  document.getElementById('s-vec').innerHTML = Array.from({ length: m }, (_, j) => `<div><div class="small text-muted text-center">${L(j)}</div><input type="number" min="0" class="form-control mat-input s-v" value="0"></div>`).join('');
  document.getElementById('b-res').innerHTML = ''; document.getElementById('s-res').innerHTML = '';
}
document.getElementById('b-gen').onclick = () => generarB();
document.getElementById('b-m').onchange = () => totales(+document.getElementById('b-m').value);

document.getElementById('b-calc').onclick = () => {
  const tot = [...document.querySelectorAll('.b-tot')].map(e => parseInt(e.value, 10) || 0), a = leerM('b-asig');
  const disp = tot.map((t, j) => t - a.reduce((s, f) => s + (f[j] || 0), 0));
  if (disp.some(x => x < 0)) return LC.toast('La asignación supera las instancias totales de algún recurso.', 'warning');
  document.querySelectorAll('#b-disp input').forEach((e, j) => e.value = disp[j]);
};
document.getElementById('b-ej').onclick = () => {
  document.getElementById('b-n').value = 5; document.getElementById('b-m').value = 3; totales(3, [10, 5, 7]);
  generarB({ asig: [[0, 1, 0], [2, 0, 0], [3, 0, 2], [2, 1, 1], [0, 0, 2]], max: [[7, 5, 3], [3, 2, 2], [9, 0, 2], [2, 2, 2], [4, 3, 3]], disp: [3, 3, 2] });
};

const pasosHtml = p => p.map(t => `<pre class="paso">${LC.esc(t)}</pre>`).join('');

document.getElementById('b-run').onclick = async () => {
  try {
    const r = await LC.post(U + '/banquero', { asignacion: leerM('b-asig'), maximo: leerM('b-max'), disponible: leerV('b-disp') });
    const e = r.estado;
    document.getElementById('b-res').innerHTML = `
      <div class="row g-3">
        <div class="col-lg-4">
          <div class="p-3 rounded ${e.seguro ? 'bg-success-subtle text-success-emphasis' : 'bg-danger-subtle text-danger-emphasis'}">
            <div class="fs-5 fw-bold"><i class="bi ${e.seguro ? 'bi-check-circle-fill' : 'bi-x-circle-fill'} me-1"></i> Estado ${e.seguro ? 'seguro' : 'inseguro'}</div>
            <div>${e.seguro ? 'Secuencia segura: <strong>' + e.secuencia.join(' → ') + '</strong>' : 'No existe secuencia segura. Bloqueados: <strong>' + e.bloqueados.join(', ') + '</strong>'}</div>
          </div>
          <h6 class="mt-3">Matriz Necesidad (Máximo − Asignación)</h6>
          <table class="lc-tabla"><thead><tr><th>Proceso</th>${r.necesidad[0].map((_, j) => `<th>${L(j)}</th>`).join('')}</tr></thead><tbody>
          ${r.necesidad.map((f, i) => `<tr><td class="fw-semibold">P${i}</td>${f.map(v => `<td>${v}</td>`).join('')}</tr>`).join('')}</tbody></table>
        </div>
        <div class="col-lg-8"><h6>Paso a paso</h6>${pasosHtml(e.pasos)}</div>
      </div>`;
  } catch (e) { LC.toast(e.message, 'danger'); }
};

document.getElementById('s-run').onclick = async () => {
  try {
    const r = await LC.post(U + '/banquero', {
      asignacion: leerM('b-asig'), maximo: leerM('b-max'), disponible: leerV('b-disp'),
      solicitud: { proceso: +document.getElementById('s-proc').value, recursos: [...document.querySelectorAll('.s-v')].map(e => parseInt(e.value, 10) || 0) }
    });
    const s = r.solicitud;
    document.getElementById('s-res').innerHTML = `
      <div class="p-3 rounded mb-2 ${s.concedida ? 'bg-success-subtle text-success-emphasis' : 'bg-danger-subtle text-danger-emphasis'}">
        <strong>${s.concedida ? 'Solicitud concedida' : 'Solicitud NO concedida'}</strong> — ${LC.esc(s.motivo)}
        ${s.secuencia ? '<div>Secuencia segura resultante: <strong>' + s.secuencia.join(' → ') + '</strong></div>' : ''}
      </div>${pasosHtml(s.pasos)}`;
  } catch (e) { LC.toast(e.message, 'danger'); }
};

// ---- DETECCION + GRAFO ----
function generarD(datos) {
  const n = Math.min(10, Math.max(1, +document.getElementById('d-n').value)), m = Math.min(8, Math.max(1, +document.getElementById('d-m').value));
  tabla('d-asig', n, m, datos?.asig); tabla('d-sol', n, m, datos?.sol); vectorTabla('d-disp', m, datos?.disp);
  document.getElementById('d-res').innerHTML = '';
}
document.getElementById('d-gen').onclick = () => generarD();
document.getElementById('d-ej').onclick = () => {
  document.getElementById('d-n').value = 3; document.getElementById('d-m').value = 3;
  generarD({ asig: [[1, 0, 0], [0, 1, 0], [0, 0, 1]], sol: [[0, 1, 0], [0, 0, 1], [1, 0, 0]], disp: [0, 0, 0] });
};

function dibujarGrafo(r) {
  const W = 720, H = 380, yP = 70, yR = 305;
  const procs = r.nodos.filter(n => n.tipo === 'proceso'), recs = r.nodos.filter(n => n.tipo === 'recurso');
  const pos = {};
  procs.forEach((n, i) => pos[n.id] = { x: (i + 1) * W / (procs.length + 1), y: yP, r: 26 });
  recs.forEach((n, i) => pos[n.id] = { x: (i + 1) * W / (recs.length + 1), y: yR, r: 30 });
  const enCiclo = new Set();
  if (r.ciclo) for (let i = 0; i < r.ciclo.length - 1; i++) enCiclo.add(r.ciclo[i] + '>' + r.ciclo[i + 1]);

  let s = `<svg viewBox="0 0 ${W} ${H}"><defs>
    ${[['az', '#1769ff'], ['na', '#e8890c'], ['ro', '#e03131']].map(([k, c]) => `<marker id="f-${k}" viewBox="0 0 10 10" refX="9" refY="5" markerWidth="7" markerHeight="7" orient="auto-start-reverse"><path d="M0 0L10 5L0 10z" fill="${c}"/></marker>`).join('')}</defs>`;
  r.aristas.forEach(a => {
    const p = pos[a.de], q = pos[a.a];
    const dx = q.x - p.x, dy = q.y - p.y, d = Math.hypot(dx, dy) || 1, ux = dx / d, uy = dy / d;
    const x1 = p.x + ux * (p.r + 2), y1 = p.y + uy * (p.r + 2), x2 = q.x - ux * (q.r + 6), y2 = q.y - uy * (q.r + 6);
    const cx = (x1 + x2) / 2 - uy * 28, cy = (y1 + y2) / 2 + ux * 28;
    const rojo = enCiclo.has(a.de + '>' + a.a);
    const col = rojo ? '#e03131' : (a.tipo === 'asignacion' ? '#1769ff' : '#e8890c'), mk = rojo ? 'ro' : (a.tipo === 'asignacion' ? 'az' : 'na');
    s += `<path d="M${x1} ${y1} Q${cx} ${cy} ${x2} ${y2}" fill="none" stroke="${col}" stroke-width="${rojo ? 3 : 2}" ${a.tipo === 'solicitud' ? 'stroke-dasharray="6 4"' : ''} marker-end="url(#f-${mk})"/>`;
    if (a.n > 1) s += `<text x="${(x1 + 2 * cx + x2) / 4}" y="${(y1 + 2 * cy + y2) / 4 - 4}" font-size="12" fill="${col}" text-anchor="middle" font-weight="700">${a.n}</text>`;
  });
  procs.forEach(n => {
    const p = pos[n.id], bloq = r.bloqueados.includes(n.id);
    s += `<circle cx="${p.x}" cy="${p.y}" r="${p.r}" fill="${bloq ? '#ffd6d6' : '#dbe8ff'}" stroke="${bloq ? '#e03131' : '#1769ff'}" stroke-width="2"/><text x="${p.x}" y="${p.y + 5}" text-anchor="middle" font-weight="700" font-size="15">${n.id}</text>`;
  });
  recs.forEach(n => {
    const p = pos[n.id], k = Math.min(n.instancias, 6), w = 64;
    s += `<rect x="${p.x - w / 2}" y="${p.y - 28}" width="${w}" height="56" rx="6" fill="#fff" stroke="#0b2a4a" stroke-width="2"/><text x="${p.x}" y="${p.y - 10}" text-anchor="middle" font-weight="700" font-size="14">${n.id}</text>`;
    for (let i = 0; i < k; i++) s += `<circle cx="${p.x - (k - 1) * 7 + i * 14}" cy="${p.y + 12}" r="4.5" fill="#0b2a4a"/>`;
    if (n.instancias > 6) s += `<text x="${p.x + w / 2 - 4}" y="${p.y + 24}" font-size="10" text-anchor="end">×${n.instancias}</text>`;
  });
  return s + '</svg>';
}

document.getElementById('d-run').onclick = async () => {
  try {
    const r = await LC.post(U + '/deteccion', { asignacion: leerM('d-asig'), solicitud: leerM('d-sol'), disponible: leerV('d-disp') });
    const lista = a => a.length ? '<ol class="mb-0">' + a.map(t => `<li class="mb-1">${LC.esc(t)}</li>`).join('') + '</ol>' : '';
    document.getElementById('d-res').innerHTML = `
      <div class="p-3 rounded mb-3 ${r.interbloqueo ? 'bg-danger-subtle text-danger-emphasis' : 'bg-success-subtle text-success-emphasis'}">
        <strong>${LC.esc(r.conclusion)}</strong>
        ${r.ciclo ? '<div>Ciclo encontrado: <strong>' + r.ciclo.join(' → ') + '</strong></div>' : ''}
      </div>
      <div class="row g-3">
        <div class="col-lg-7 grafo"><h6>Grafo de asignación de recursos</h6>${dibujarGrafo(r)}
          <div class="small text-muted mt-1">Círculo = proceso · Cuadro = recurso (puntos = instancias) · Flecha azul continua: recurso asignado (R → P) · Flecha naranja discontinua: solicitud (P → R) · En rojo: ciclo.</div></div>
        <div class="col-lg-5"><h6>Paso a paso de la detección</h6>${pasosHtml(r.pasos)}</div>
      </div>
      ${r.interbloqueo ? `<div class="row g-3 mt-1">
        <div class="col-lg-6"><div class="lc-card"><h6>Recuperación por terminación de procesos</h6>${lista(r.recuperacion.terminacion)}</div></div>
        <div class="col-lg-6"><div class="lc-card"><h6>Recuperación por apropiación de recursos</h6>${lista(r.recuperacion.apropiacion)}</div></div></div>` : ''}`;
  } catch (e) { LC.toast(e.message, 'danger'); }
};

totales(3, [10, 5, 7]);
generarB();
generarD();
document.getElementById('b-ej').click();
document.getElementById('d-ej').click();
</script>
@endpush
