<section class="planning-top">
<article class="panel configuration">
<h2>Configuración</h2>
<label>Algoritmo<select id="algorithm">
  <option value="FCFS">FCFS (First Come First Served)</option>
  <option value="SJF">SJF (Shortest Job First)</option>
  <option value="SRTF">SRTF (Shortest Remaining Time First)</option>
  <option value="RR">Round Robin (RR)</option>
  <option value="PRIO">Prioridad (no expropiativo)</option>
  <option value="PRIOP">Prioridad (expropiativo)</option>
</select></label>
<label id="lblQuantum">Quantum (solo RR)<input type="number" id="quantum" value="2" min="1"></label>
<div class="button-row">
  <button class="btn btn-primary" id="addProcess"><i class="fa-solid fa-plus"></i> Agregar proceso</button>
  <button class="btn btn-muted" id="clearProcesses">Limpiar</button>
  <button class="btn btn-muted" id="randomProcesses">Aleatorio</button>
</div>
<div class="button-row" style="margin-top:10px">
  <label style="margin:0">Cantidad aleatoria <input type="number" id="randomCount" value="5" min="1" max="15" style="width:70px"></label>
</div>
<div class="button-row" style="margin-top:10px">
  <button class="btn btn-primary" id="runSim"><i class="fa-solid fa-play"></i> Simular</button>
  <button class="btn btn-muted" id="runCompare">Comparar todos</button>
</div>
<div id="schedMsg" style="color:#d1453b;margin-top:8px"></div>
</article>
<article class="panel"><h2>Procesos</h2><div class="table-scroll"><table id="scheduleTable"><thead><tr><th>PID</th><th>Tiempo Llegada</th><th>Ráfaga CPU</th><th>Prioridad</th><th>Acciones</th></tr></thead><tbody></tbody></table></div>
<small>Prioridad: un número menor significa mayor prioridad.</small></article>
</section>

<article class="panel gantt-panel" id="ganttPanel" style="display:none"><h2>Diagrama de Gantt — <span id="ganttTitle"></span></h2>
<div class="gantt" id="gantt"></div><div class="gantt-ticks" id="ganttTicks"></div></article>

<section class="metrics-grid" id="metricsGrid" style="display:none">
<article class="metric-card"><p>Tiempo de espera promedio</p><strong id="mWait">-</strong></article>
<article class="metric-card"><p>Tiempo de retorno promedio</p><strong id="mTat">-</strong></article>
<article class="metric-card"><p>Tiempo de respuesta promedio</p><strong id="mResp">-</strong></article>
<article class="metric-card"><p>Utilización de CPU</p><strong id="mCpu">-</strong></article>
</section>

<article class="panel" id="resultPanel" style="display:none"><h2>Resultados por proceso</h2><div class="table-scroll">
<table id="resultTable"><thead><tr><th>PID</th><th>Llegada</th><th>Ráfaga</th><th>Prioridad</th><th>Finalización</th><th>Retorno</th><th>Espera</th><th>Respuesta</th></tr></thead><tbody></tbody></table></div>
<div style="display:flex;gap:12px;align-items:center;margin-top:10px"><button class="btn btn-primary" id="saveSim" style="flex:none"><i class="fa-solid fa-floppy-disk"></i> Guardar simulación en SQLite</button><span id="saveMsg"></span></div>
</article>

<article class="panel" id="comparePanel" style="display:none"><h2>Tabla comparativa de algoritmos</h2><div class="table-scroll">
<table id="compareTable"><thead><tr><th>Algoritmo</th><th>Espera prom.</th><th>Retorno prom.</th><th>Respuesta prom.</th></tr></thead><tbody></tbody></table></div>
<small>En verde, el algoritmo con menor tiempo de espera promedio.</small></article>

<article class="panel"><h2>Simulaciones guardadas</h2><div class="table-scroll">
<table id="histTable"><thead><tr><th>#</th><th>Fecha</th><th>Algoritmo</th><th>Quantum</th><th>Procesos</th><th>Espera prom.</th><th>Retorno prom.</th><th>Acciones</th></tr></thead><tbody></tbody></table></div></article>

<style>
#gantt .idle{background:rgba(128,128,128,.25)!important;color:inherit}
#compareTable tr.best td{background:rgba(46,160,67,.25);font-weight:600}
#scheduleTable input[type=number]{width:80px}
#scheduleTable input[type=text]{width:70px}
#resultPanel .button-row .btn{width:auto}
.configuration .button-row{flex-wrap:wrap}
</style>

<script>
(function () {
  const $ = id => document.getElementById(id);
  const esc = s => String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
  const NAMES = {FCFS:'FCFS', SJF:'SJF (no expropiativo)', SRTF:'SRTF', RR:'Round Robin', PRIO:'Prioridad (no expropiativo)', PRIOP:'Prioridad (expropiativo)'};
  // El PHP de SQLite está en la raíz del proyecto. Si tu index.php está en otra carpeta, ajusta esta ruta.
  const API = 'api_planificacion.php';
  let last = null, seq = 0;

  /* ---------- tabla de procesos (editable) ---------- */
  function addRow(p) {
    seq++;
    p = p || {name: 'P' + seq, arrival: 0, burst: 1, priority: 1};
    const tr = document.createElement('tr');
    tr.innerHTML = `<td><input type="text" value="${esc(p.name)}"></td>
      <td><input type="number" min="0" value="${p.arrival}"></td>
      <td><input type="number" min="1" value="${p.burst}"></td>
      <td><input type="number" min="0" value="${p.priority}"></td>
      <td><button class="icon-btn danger remove-process" title="Quitar"><i class="fa-solid fa-trash"></i></button></td>`;
    tr.querySelector('button').onclick = () => tr.remove();
    $('scheduleTable').tBodies[0].appendChild(tr);
  }
  function setProcs(list) { $('scheduleTable').tBodies[0].innerHTML = ''; seq = 0; list.forEach(addRow); }
  function getProcs() {
    return [...$('scheduleTable').tBodies[0].rows].map(tr => {
      const i = tr.querySelectorAll('input');
      return {name: i[0].value.trim() || 'P?', arrival: parseInt(i[1].value), burst: parseInt(i[2].value), priority: parseInt(i[3].value)};
    });
  }
  function validate(P) {
    if (!P.length) return 'Agrega al menos un proceso.';
    for (const p of P) {
      if (isNaN(p.arrival) || p.arrival < 0) return 'La llegada de ' + p.name + ' debe ser un entero ≥ 0.';
      if (isNaN(p.burst) || p.burst < 1) return 'La ráfaga de ' + p.name + ' debe ser un entero ≥ 1.';
      if (isNaN(p.priority)) return 'La prioridad de ' + p.name + ' no es válida.';
    }
    if ($('algorithm').value === 'RR' && !(parseInt($('quantum').value) >= 1)) return 'El quantum debe ser ≥ 1.';
    return '';
  }
  function randomProcs(n) {
    const L = [];
    for (let i = 0; i < n; i++) L.push({name: 'P' + (i + 1), arrival: Math.floor(Math.random() * 8), burst: 1 + Math.floor(Math.random() * 9), priority: 1 + Math.floor(Math.random() * 5)});
    return L;
  }

  /* ---------- motor de simulación ---------- */
  function simulate(P, algo, q) {
    const pre = algo === 'PRIOP', base = algo === 'PRIOP' ? 'PRIO' : algo;
    const n = P.length, rem = P.map(p => p.burst), ct = Array(n).fill(null), first = Array(n).fill(null), g = [];
    let t = 0, done = 0;
    const push = (pid, s, e) => {
      if (e <= s) return;
      const l = g[g.length - 1];
      if (l && l.pid === pid && l.end === s) l.end = e; else g.push({pid, start: s, end: e});
    };
    if (base === 'RR') {
      const order = [...P.keys()].sort((a, b) => P[a].arrival - P[b].arrival || a - b), queue = [];
      let idx = 0;
      const admit = () => { while (idx < n && P[order[idx]].arrival <= t) queue.push(order[idx++]); };
      while (done < n) {
        admit();
        if (!queue.length) { const nx = P[order[idx]].arrival; push(null, t, nx); t = nx; continue; }
        const i = queue.shift();
        if (first[i] === null) first[i] = t;
        const run = Math.min(q, rem[i]);
        push(i, t, t + run); t += run; rem[i] -= run;
        admit();
        if (rem[i] > 0) queue.push(i); else { ct[i] = t; done++; }
      }
    } else {
      const preemptive = base === 'SRTF' || pre;
      const key = i => base === 'FCFS' ? P[i].arrival : base === 'SJF' ? P[i].burst : base === 'SRTF' ? rem[i] : P[i].priority;
      let cur = null;
      while (done < n) {
        const ready = [...P.keys()].filter(i => P[i].arrival <= t && rem[i] > 0);
        if (!ready.length) {
          const nx = Math.min(...P.map((p, i) => rem[i] > 0 ? p.arrival : Infinity));
          push(null, t, nx); t = nx; cur = null; continue;
        }
        if (cur === null || preemptive) {
          ready.sort((a, b) => key(a) - key(b) || (b === cur) - (a === cur) || P[a].arrival - P[b].arrival || a - b);
          cur = ready[0];
        }
        if (first[cur] === null) first[cur] = t;
        push(cur, t, t + 1); t++; rem[cur]--;
        if (rem[cur] === 0) { ct[cur] = t; done++; cur = null; }
      }
    }
    const rows = P.map((p, i) => {
      const tat = ct[i] - p.arrival;
      return {name: p.name, arrival: p.arrival, burst: p.burst, priority: p.priority, ct: ct[i], tat, wt: tat - p.burst, rt: first[i] - p.arrival};
    });
    const avg = k => rows.reduce((s, r) => s + r[k], 0) / n;
    const total = g[g.length - 1].end, busy = P.reduce((s, p) => s + p.burst, 0);
    return {algo, q, procs: P, rows, gantt: g, avgW: avg('wt'), avgT: avg('tat'), avgR: avg('rt'), cpu: busy / total * 100};
  }

  /* ---------- render (usa las clases del diseño del grupo) ---------- */
  function render(s) {
    last = s;
    $('gantt').innerHTML = s.gantt.map(seg => seg.pid === null
      ? `<div class="gantt-segment idle" style="flex:${seg.end - seg.start}">ocio</div>`
      : `<div class="gantt-segment g${seg.pid % 4 + 1}" style="flex:${seg.end - seg.start}" title="${seg.start}–${seg.end}">${esc(s.procs[seg.pid].name)}</div>`).join('');
    $('ganttTicks').innerHTML = [s.gantt[0].start].concat(s.gantt.map(x => x.end)).map(v => `<span>${v}</span>`).join('');
    $('ganttTitle').textContent = NAMES[s.algo] + (s.algo === 'RR' ? ' (q=' + s.q + ')' : '');
    $('mWait').textContent = s.avgW.toFixed(2); $('mTat').textContent = s.avgT.toFixed(2);
    $('mResp').textContent = s.avgR.toFixed(2); $('mCpu').textContent = s.cpu.toFixed(0) + ' %';
    $('resultTable').tBodies[0].innerHTML = s.rows.map(r =>
      `<tr><td>${esc(r.name)}</td><td>${r.arrival}</td><td>${r.burst}</td><td>${r.priority}</td><td>${r.ct}</td><td>${r.tat}</td><td>${r.wt}</td><td>${r.rt}</td></tr>`).join('');
    ['ganttPanel', 'metricsGrid', 'resultPanel'].forEach(id => $(id).style.display = '');
    $('saveMsg').textContent = '';
  }
  function run() {
    const P = getProcs(), e = validate(P);
    $('schedMsg').textContent = e;
    if (e) return;
    render(simulate(P, $('algorithm').value, parseInt($('quantum').value) || 1));
  }
  function compare() {
    const P = getProcs(), e = validate(P);
    $('schedMsg').textContent = e;
    if (e) return;
    const q = parseInt($('quantum').value) || 1;
    const res = Object.keys(NAMES).map(a => ({label: NAMES[a] + (a === 'RR' ? ' (q=' + q + ')' : ''), s: simulate(P, a, q)}));
    const best = Math.min(...res.map(r => r.s.avgW));
    $('compareTable').tBodies[0].innerHTML = res.map(r =>
      `<tr class="${r.s.avgW === best ? 'best' : ''}"><td>${r.label}</td><td>${r.s.avgW.toFixed(2)}</td><td>${r.s.avgT.toFixed(2)}</td><td>${r.s.avgR.toFixed(2)}</td></tr>`).join('');
    $('comparePanel').style.display = '';
  }

  /* ---------- SQLite (vía api_planificacion.php) ---------- */
  async function api(action, o) {
    o = o || {};
    const r = await fetch(API + '?action=' + action + (o.id ? '&id=' + o.id : ''), {
      method: o.body ? 'POST' : 'GET', headers: {'Content-Type': 'application/json'}, body: o.body ? JSON.stringify(o.body) : undefined});
    return r.json();
  }
  async function save() {
    if (!last) return;
    try {
      const d = await api('save', {body: last});
      $('saveMsg').textContent = d.ok ? ' Guardada (#' + d.id + ')' : ' Error: ' + (d.error || '');
    } catch (e) { $('saveMsg').textContent = ' No se pudo conectar con ' + API; }
    loadHist();
  }
  async function loadHist() {
    try {
      const rows = await api('list');
      $('histTable').tBodies[0].innerHTML = Array.isArray(rows) ? rows.map(r =>
        `<tr><td>${r.id}</td><td>${esc(r.fecha)}</td><td>${esc(NAMES[r.algoritmo] || r.algoritmo)}</td><td>${r.algoritmo === 'RR' ? r.quantum : '—'}</td><td>${r.num_procesos}</td><td>${(+r.prom_espera).toFixed(2)}</td><td>${(+r.prom_retorno).toFixed(2)}</td>
         <td><button class="btn btn-muted" data-v="${r.id}">Ver</button> <button class="icon-btn danger" data-d="${r.id}" title="Eliminar"><i class="fa-solid fa-trash"></i></button></td></tr>`).join('') : '';
    } catch (e) { /* sin API todavía */ }
  }
  $('histTable').addEventListener('click', async ev => {
    const b = ev.target.closest('button'); if (!b) return;
    if (b.dataset.v) {
      const d = await api('get', {id: b.dataset.v});
      if (d.error) return alert(d.error);
      setProcs(d.procs); $('algorithm').value = d.algo; $('quantum').value = d.q || 2; toggle();
      render(simulate(d.procs, d.algo, d.q || 1));
    } else if (b.dataset.d && confirm('¿Eliminar la simulación #' + b.dataset.d + '?')) {
      await api('delete', {id: b.dataset.d, body: {}}); loadHist();
    }
  });

  function toggle() { $('lblQuantum').style.display = $('algorithm').value === 'RR' ? '' : 'none'; }
  $('algorithm').onchange = toggle;
  $('addProcess').onclick = () => addRow();
  $('clearProcesses').onclick = () => setProcs([]);
  $('randomProcesses').onclick = () => setProcs(randomProcs(Math.min(15, Math.max(1, parseInt($('randomCount').value) || 5))));
  $('runSim').onclick = run;
  $('runCompare').onclick = compare;
  $('saveSim').onclick = save;

  setProcs([{name:'P1',arrival:0,burst:5,priority:1},{name:'P2',arrival:1,burst:3,priority:1},{name:'P3',arrival:2,burst:8,priority:1},{name:'P4',arrival:3,burst:6,priority:1}]);
  toggle(); loadHist(); run();
})();
</script>
