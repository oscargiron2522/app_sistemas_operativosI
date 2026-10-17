<style>
#m4 section{margin:0 0 16px}
#m4 h2{margin:0 0 14px}
#m4 h3{margin:0 0 8px}
#m4 table{border-collapse:collapse}
#m4 th,#m4 td{border:1px solid rgba(128,128,128,.35);padding:4px 6px;text-align:center}
#m4 input[type=number],#m4 select{padding:4px 5px;border:1px solid rgba(128,128,128,.5);border-radius:6px;background:transparent;color:inherit}
#m4 .mat input[type=number]{width:54px}
#m4 .bar{display:flex;gap:10px;flex-wrap:wrap;align-items:center;margin:8px 0}
#m4 .grid{display:flex;gap:22px;flex-wrap:wrap;align-items:flex-start}
#m4 .steps{font-family:ui-monospace,Menlo,Consolas,monospace;font-size:13px;line-height:1.6;border:1px solid rgba(128,128,128,.35);border-radius:6px;padding:10px 12px;max-height:340px;overflow:auto}
#m4 .ok{color:#2ea043;font-weight:700}
#m4 .bad{color:#d1453b;font-weight:700}
#m4 .muted{opacity:.7;font-size:13px}
#m4 .box{border:1px solid rgba(128,128,128,.35);border-radius:6px;padding:10px 12px;margin-top:10px}
#m4 svg{max-width:100%;border:1px solid rgba(128,128,128,.35);border-radius:6px}
</style>

<div id="m4">
  <h2>M4 — Interbloqueos (simulado)</h2>

  <article class="panel">
    <h3>1. Datos del sistema</h3>
    <div class="bar">
      <label>Procesos (n): <input type="number" id="n" value="5" min="1" max="10" style="width:60px"></label>
      <label>Tipos de recurso (m): <input type="number" id="m" value="3" min="1" max="6" style="width:60px"></label>
      <button class="btn btn-primary" id="btnGen">Generar tablas</button>
      <button class="btn btn-muted" id="btnEx1">Ejemplo seguro (clásico)</button>
      <button class="btn btn-muted" id="btnEx2">Ejemplo con interbloqueo</button>
    </div>
    <div id="tables" class="grid"></div>
    <div class="muted" id="totals"></div>
    <div class="muted">La matriz «Solicitud pendiente» solo se usa para el grafo de asignación y la detección de interbloqueos.</div>
    <div id="err" class="bad"></div>
  </article>

  <article class="panel">
    <h3>2. Algoritmo del banquero</h3>
    <div class="bar"><button class="btn btn-primary" id="btnSafe">Calcular Necesidad y estado seguro</button></div>
    <div class="grid">
      <div id="needBox"></div>
      <div id="safeOut" style="flex:1;min-width:300px"></div>
    </div>
  </article>

  <article class="panel">
    <h3>3. Evaluar una solicitud de recursos</h3>
    <div class="bar">
      <label>Proceso: <select id="reqP"></select></label>
      <span id="reqVec"></span>
      <button class="btn btn-primary" id="btnReq">Evaluar solicitud</button>
      <button class="btn btn-primary" id="btnApply" style="display:none">Aplicar solicitud concedida</button>
    </div>
    <div id="reqOut"></div>
  </article>

  <article class="panel">
    <h3>4. Grafo de asignación de recursos y detección de interbloqueo</h3>
    <div class="bar"><button class="btn btn-primary" id="btnGraph">Dibujar grafo y detectar</button></div>
    <div class="muted">Línea continua R→P: recurso asignado. Línea discontinua P→R: solicitud pendiente. Rojo: ciclo.</div>
    <div id="graphOut"></div>
  </article>
</div>

<script>
(function () {
  const $ = id => document.getElementById(id);
  const sub = (a, b) => a.map((x, i) => x - b[i]);
  const add = (a, b) => a.map((x, i) => x + b[i]);
  const le = (a, b) => a.every((x, i) => x <= b[i]);
  const fmt = v => '[' + v.join(', ') + ']';
  const letters = j => String.fromCharCode(65 + j);       // recursos A, B, C…
  let n = 0, m = 0, lastGrant = null;

  /* ---------- tablas de entrada ---------- */
  function matTable(prefix, title) {
    let h = `<div><b>${title}</b><table class="mat"><tr><th></th>`;
    for (let j = 0; j < m; j++) h += `<th>${letters(j)}</th>`;
    h += '</tr>';
    for (let i = 0; i < n; i++) {
      h += `<tr><th>P${i}</th>`;
      for (let j = 0; j < m; j++) h += `<td><input type="number" min="0" id="${prefix}_${i}_${j}" value="0"></td>`;
      h += '</tr>';
    }
    return h + '</table></div>';
  }
  function generate() {
    n = Math.min(10, Math.max(1, parseInt($('n').value) || 1));
    m = Math.min(6, Math.max(1, parseInt($('m').value) || 1));
    let h = matTable('a', 'Asignación'), mx = matTable('x', 'Máximo'), rq = matTable('r', 'Solicitud pendiente');
    let av = '<div><b>Disponible</b><table class="mat"><tr>';
    for (let j = 0; j < m; j++) av += `<th>${letters(j)}</th>`;
    av += '</tr><tr>';
    for (let j = 0; j < m; j++) av += `<td><input type="number" min="0" id="v_${j}" value="0"></td>`;
    av += '</tr></table></div>';
    $('tables').innerHTML = h + mx + av + rq;
    $('needBox').innerHTML = $('safeOut').innerHTML = $('reqOut').innerHTML = $('graphOut').innerHTML = '';
    $('err').textContent = '';
    $('reqP').innerHTML = [...Array(n).keys()].map(i => `<option value="${i}">P${i}</option>`).join('');
    let rv = '';
    for (let j = 0; j < m; j++) rv += `${letters(j)}: <input type="number" min="0" id="q_${j}" value="0" style="width:54px"> `;
    $('reqVec').innerHTML = rv;
    $('btnApply').style.display = 'none';
  }
  const readM = p => [...Array(n)].map((_, i) => [...Array(m)].map((_, j) => parseInt($(`${p}_${i}_${j}`).value) || 0));
  const readV = () => [...Array(m)].map((_, j) => parseInt($(`v_${j}`).value) || 0);
  function fill(p, M) { M.forEach((r, i) => r.forEach((v, j) => $(`${p}_${i}_${j}`).value = v)); }
  function load(ex) {
    $('n').value = ex.A.length; $('m').value = ex.A[0].length; generate();
    fill('a', ex.A); fill('x', ex.X); fill('r', ex.R || ex.A.map(r => r.map(() => 0)));
    ex.V.forEach((v, j) => $(`v_${j}`).value = v);
    updateTotals();
  }
  function updateTotals() {
    const A = readM('a'), V = readV(), tot = V.slice();
    A.forEach(r => r.forEach((v, j) => tot[j] += v));
    $('totals').textContent = 'Instancias totales por recurso (Asignación + Disponible): ' + tot.map((t, j) => letters(j) + '=' + t).join('  ');
  }

  function state() {
    const A = readM('a'), X = readM('x'), R = readM('r'), V = readV();
    for (let i = 0; i < n; i++) for (let j = 0; j < m; j++)
      if (X[i][j] < A[i][j]) { $('err').textContent = `Error: en P${i} la Asignación de ${letters(j)} (${A[i][j]}) supera su Máximo (${X[i][j]}).`; return null; }
    $('err').textContent = '';
    updateTotals();
    return {A, X, R, V, N: X.map((r, i) => sub(r, A[i]))};
  }

  /* ---------- banquero ---------- */
  function safety(A, N, V) {
    let work = V.slice(); const fin = Array(n).fill(false), seq = [], log = [];
    log.push(`Work inicial = Disponible = ${fmt(work)}`);
    while (seq.length < n) {
      let pick = -1;
      for (let i = 0; i < n; i++) {
        if (fin[i]) continue;
        if (le(N[i], work)) { pick = i; log.push(`P${i}: Necesidad ${fmt(N[i])} ≤ Work ${fmt(work)} ✓`); break; }
        log.push(`P${i}: Necesidad ${fmt(N[i])} ≤ Work ${fmt(work)} ✗ (debe esperar)`);
      }
      if (pick < 0) { log.push('Ningún proceso restante puede terminar.'); break; }
      work = add(work, A[pick]); fin[pick] = true; seq.push(pick);
      log.push(`   → P${pick} termina y libera ${fmt(A[pick])}. Work = ${fmt(work)}`);
    }
    return {safe: seq.length === n, seq, log, blocked: fin.map((f, i) => f ? -1 : i).filter(i => i >= 0)};
  }
  function tableOf(M, title) {
    let h = `<b>${title}</b><table><tr><th></th>` + [...Array(m)].map((_, j) => `<th>${letters(j)}</th>`).join('') + '</tr>';
    M.forEach((r, i) => h += `<tr><th>P${i}</th>` + r.map(v => `<td>${v}</td>`).join('') + '</tr>');
    return h + '</table>';
  }
  function runSafe() {
    const s = state(); if (!s) return;
    $('needBox').innerHTML = tableOf(s.N, 'Necesidad = Máximo − Asignación');
    const r = safety(s.A, s.N, s.V);
    $('safeOut').innerHTML = `<div class="${r.safe ? 'ok' : 'bad'}">${r.safe ? 'Estado SEGURO' : 'Estado INSEGURO'} ${r.safe ? '— secuencia segura: ' + r.seq.map(i => 'P' + i).join(' → ') : '— procesos que no pueden terminar: ' + r.blocked.map(i => 'P' + i).join(', ')}</div>
      <div class="steps">${r.log.join('<br>')}</div>`;
  }

  /* ---------- solicitud ---------- */
  function runReq() {
    const s = state(); if (!s) return;
    const i = parseInt($('reqP').value), req = [...Array(m)].map((_, j) => parseInt($(`q_${j}`).value) || 0);
    let h = `<div class="steps">Solicitud de P${i}: ${fmt(req)}<br>`;
    lastGrant = null; $('btnApply').style.display = 'none';
    if (!le(req, s.N[i])) { $('reqOut').innerHTML = h + `<span class="bad">DENEGADA: excede su Necesidad ${fmt(s.N[i])} (supera el máximo declarado).</span></div>`; return; }
    h += `1) Solicitud ≤ Necesidad ${fmt(s.N[i])} ✓<br>`;
    if (!le(req, s.V)) { $('reqOut').innerHTML = h + `2) Solicitud ≤ Disponible ${fmt(s.V)} ✗<br><span class="bad">P${i} debe ESPERAR: no hay recursos suficientes.</span></div>`; return; }
    h += `2) Solicitud ≤ Disponible ${fmt(s.V)} ✓<br>3) Se simula la asignación:<br>`;
    const A2 = s.A.map(r => r.slice()), N2 = s.N.map(r => r.slice()), V2 = sub(s.V, req);
    A2[i] = add(A2[i], req); N2[i] = sub(N2[i], req);
    h += `   Disponible = ${fmt(V2)}, Asignación P${i} = ${fmt(A2[i])}, Necesidad P${i} = ${fmt(N2[i])}<br>4) Prueba de seguridad:<br>`;
    const r = safety(A2, N2, V2);
    h += r.log.map(l => '   ' + l).join('<br>') + '<br>';
    if (r.safe) {
      h += `<span class="ok">CONCEDIDA: el estado resultante es seguro. Secuencia: ${r.seq.map(k => 'P' + k).join(' → ')}</span>`;
      lastGrant = {i, req}; $('btnApply').style.display = '';
    } else h += `<span class="bad">DENEGADA (P${i} espera): concederla dejaría el sistema en estado inseguro.</span>`;
    $('reqOut').innerHTML = h + '</div>';
  }
  function applyGrant() {
    if (!lastGrant) return;
    const {i, req} = lastGrant;
    req.forEach((v, j) => { $(`a_${i}_${j}`).value = (parseInt($(`a_${i}_${j}`).value) || 0) + v; $(`v_${j}`).value = (parseInt($(`v_${j}`).value) || 0) - v; });
    lastGrant = null; $('btnApply').style.display = 'none'; updateTotals(); runSafe();
  }

  /* ---------- detección y grafo ---------- */
  function detect(A, R, V) {
    let work = V.slice(); const fin = A.map(r => r.every(v => v === 0)), steps = [];
    let ch = true;
    while (ch) {
      ch = false;
      for (let i = 0; i < n; i++) if (!fin[i] && le(R[i], work)) { work = add(work, A[i]); fin[i] = true; ch = true; steps.push(i); }
    }
    return fin.map((f, i) => f ? -1 : i).filter(i => i >= 0);
  }
  function findCycles(A, R) {            // nodos: procesos 0..n-1, recursos n..n+m-1
    const adj = Array.from({length: n + m}, () => []);
    for (let i = 0; i < n; i++) for (let j = 0; j < m; j++) { if (A[i][j] > 0) adj[n + j].push(i); if (R[i][j] > 0) adj[i].push(n + j); }
    const color = Array(n + m).fill(0), stack = [], edges = new Set(); let found = false;
    const dfs = u => {
      color[u] = 1; stack.push(u);
      for (const v of adj[u]) {
        if (color[v] === 0) dfs(v);
        else if (color[v] === 1) {
          found = true; const k = stack.indexOf(v), cyc = stack.slice(k).concat(v);
          for (let x = 0; x < cyc.length - 1; x++) edges.add(cyc[x] + '>' + cyc[x + 1]);
        }
      }
      stack.pop(); color[u] = 2;
    };
    for (let u = 0; u < n + m; u++) if (color[u] === 0) dfs(u);
    return {found, edges};
  }
  function recovery(A, R, V, dead) {
    const holds = i => A[i].reduce((a, b) => a + b, 0);
    const single = dead.filter(i => {
      const A2 = A.map(r => r.slice()); const V2 = add(V, A2[i]); A2[i] = A2[i].map(() => 0);
      return detect(A2, R, V2).length === 0;
    });
    let A2 = A.map(r => r.slice()), V2 = V.slice(), left = dead.slice(), order = [];
    while (left.length) {
      left.sort((a, b) => holds(a) - holds(b) || a - b);
      const v = left.shift(); order.push(v); V2 = add(V2, A2[v]); A2[v] = A2[v].map(() => 0);
      left = detect(A2, R, V2);
    }
    let h = '<div class="box"><b>Estrategias de recuperación sugeridas</b><br>';
    h += `<u>Terminación de procesos</u>: `;
    h += single.length ? `abortar un solo proceso basta: ${single.map(i => 'P' + i).join(' o ')}. ` : 'ningún proceso por sí solo resuelve el interbloqueo. ';
    h += `Orden de terminación de menor costo (menos recursos retenidos): ${order.map(i => 'P' + i).join(' → ')}.<br>`;
    h += `<u>Apropiación de recursos</u>: ${single.length ? 'quitar los recursos retenidos a ' + single.map(i => `P${i} (${fmt(A[i])})`).join(' o ') + ' y reasignarlos a los demás; la víctima vuelve a un punto anterior (rollback) y reintenta' : 'es necesario apropiar recursos a varios procesos: ' + order.map(i => `P${i} ${fmt(A[i])}`).join(', ')}. Conviene rotar la víctima para evitar inanición.</div>`;
    return h;
  }
  function drawGraph() {
    const s = state(); if (!s) return;
    const {A, R, V} = s, cyc = findCycles(A, R), dead = detect(A, R, V);
    const rowH = 62, H = Math.max(n, m) * rowH + 30, W = 620, px = 110, rx = 510, rad = 20;
    const py = i => 30 + i * (H - 40) / Math.max(1, n - 1 || 1) * (n > 1 ? 1 : 0) + (n > 1 ? 0 : (H - 40) / 2);
    const ry = j => 30 + j * (H - 40) / Math.max(1, m - 1 || 1) * (m > 1 ? 1 : 0) + (m > 1 ? 0 : (H - 40) / 2);
    const P = i => ({x: px, y: py(i)}), Rr = j => ({x: rx, y: ry(j)});
    let svg = `<svg viewBox="0 0 ${W} ${H}" width="${W}" height="${H}">
      <defs><marker id="ar" viewBox="0 0 10 10" refX="9" refY="5" markerWidth="7" markerHeight="7" orient="auto"><path d="M0,0 L10,5 L0,10 z" fill="#888"/></marker>
      <marker id="arr" viewBox="0 0 10 10" refX="9" refY="5" markerWidth="7" markerHeight="7" orient="auto"><path d="M0,0 L10,5 L0,10 z" fill="#d1453b"/></marker></defs>`;
    const edge = (a, b, ra, rb, off, dash, red, label, key) => {
      const mx = (a.x + b.x) / 2, my = (a.y + b.y) / 2, dx = b.x - a.x, dy = b.y - a.y, len = Math.hypot(dx, dy) || 1;
      const cx = mx + (-dy / len) * off, cy = my + (dx / len) * off;
      const d1 = Math.hypot(cx - a.x, cy - a.y), d2 = Math.hypot(b.x - cx, b.y - cy);
      const sx = a.x + (cx - a.x) / d1 * ra, sy = a.y + (cy - a.y) / d1 * ra;
      const ex = b.x - (b.x - cx) / d2 * (rb + 2), ey = b.y - (b.y - cy) / d2 * (rb + 2);
      const col = red ? '#d1453b' : '#888';
      return `<path d="M${sx},${sy} Q${cx},${cy} ${ex},${ey}" fill="none" stroke="${col}" stroke-width="${red ? 2.5 : 1.6}" ${dash ? 'stroke-dasharray="6 4"' : ''} marker-end="url(#${red ? 'arr' : 'ar'})"/>
        <text x="${(sx + 2 * cx + ex) / 4 + 0}" y="${(sy + 2 * cy + ey) / 4 - 4}" font-size="12" text-anchor="middle" fill="${col}">${label}</text>`;
    };
    for (let i = 0; i < n; i++) for (let j = 0; j < m; j++) {
      if (A[i][j] > 0) svg += edge(Rr(j), P(i), 18, rad, 14, false, cyc.edges.has((n + j) + '>' + i), A[i][j]);
      if (R[i][j] > 0) svg += edge(P(i), Rr(j), rad, 18, -14, true, cyc.edges.has(i + '>' + (n + j)), R[i][j]);
    }
    for (let i = 0; i < n; i++) {
      const p = P(i), bad = dead.includes(i);
      svg += `<circle cx="${p.x}" cy="${p.y}" r="${rad}" fill="${bad ? 'rgba(209,69,59,.25)' : 'rgba(128,128,128,.15)'}" stroke="${bad ? '#d1453b' : '#888'}" stroke-width="2"/><text x="${p.x}" y="${p.y + 5}" text-anchor="middle" font-size="14" fill="currentColor">P${i}</text>`;
    }
    const tot = V.slice(); A.forEach(r => r.forEach((v, j) => tot[j] += v));
    for (let j = 0; j < m; j++) {
      const p = Rr(j);
      svg += `<rect x="${p.x - 18}" y="${p.y - 18}" width="36" height="36" fill="rgba(128,128,128,.15)" stroke="#888" stroke-width="2"/><text x="${p.x}" y="${p.y + 5}" text-anchor="middle" font-size="14" fill="currentColor">${letters(j)}</text><text x="${p.x + 30}" y="${p.y + 4}" font-size="11" fill="currentColor">${tot[j]} inst.</text>`;
    }
    svg += '</svg>';
    let h = svg + '<div class="box">';
    h += cyc.found ? `<span class="bad">Se detectó un ciclo en el grafo.</span> ` : `<span class="ok">No hay ciclos en el grafo.</span> `;
    if (dead.length) h += `<br><span class="bad">INTERBLOQUEO: procesos bloqueados ${dead.map(i => 'P' + i).join(', ')}.</span>`;
    else h += `<br><span class="ok">No hay interbloqueo</span>${cyc.found ? ' (con varias instancias por recurso, un ciclo es necesario pero no suficiente: los procesos pueden terminar y romperlo).' : '.'}`;
    h += '</div>';
    if (dead.length) h += recovery(A, R, V, dead);
    $('graphOut').innerHTML = h;
  }

  /* ---------- ejemplos y eventos ---------- */
  const EX1 = {A: [[0,1,0],[2,0,0],[3,0,2],[2,1,1],[0,0,2]], X: [[7,5,3],[3,2,2],[9,0,2],[2,2,2],[4,3,3]], V: [3,3,2],
               R: [[0,0,0],[0,0,0],[0,0,0],[0,0,0],[0,0,0]]};
  const EX2 = {A: [[1,0],[0,1]], X: [[1,1],[1,1]], V: [0,0], R: [[0,1],[1,0]]};
  $('btnGen').onclick = generate;
  $('btnEx1').onclick = () => load(EX1);
  $('btnEx2').onclick = () => load(EX2);
  $('btnSafe').onclick = runSafe;
  $('btnReq').onclick = runReq;
  $('btnApply').onclick = applyGrant;
  $('btnGraph').onclick = drawGraph;
  $('tables').addEventListener('input', updateTotals);
  load(EX1);
})();
</script>