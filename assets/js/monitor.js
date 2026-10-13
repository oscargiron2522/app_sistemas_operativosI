(() => {
const $ = s => document.querySelector(s);
const esc = t => String(t).replace(/[&<>"]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]));
const gb = mb => mb >= 1024 ? (mb / 1024).toFixed(1) + ' GB' : mb + ' MB';
const pct = (x, t) => t ? Math.round(x / t * 100) : 0;
const toast = m => {
    const b = $('#toast'); if (!b) return;
    b.textContent = m; b.classList.add('show');
    clearTimeout(window.monitorToast); window.monitorToast = setTimeout(() => b.classList.remove('show'), 2800);
};
async function api(accion, body) {
    const r = await fetch('api.php?action=' + accion, body
        ? {method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify(body)} : {});
    return r.json();
}
// Repite cada 3 s, esperando a que termine la petición anterior
function ciclo(fn) { (async function t() { try { await fn(); } catch (e) { console.error(e); } setTimeout(t, 3000); })(); }

// ================= M1: PROCESOS =================
if ($('#procTabla')) {
    let procs = [], orden = {k: 'cpu', d: -1}, verArbol = false;
    const cols = [['pid','PID'],['ppid','PPID'],['user','Usuario'],['cmd','Nombre'],['nice','Nice'],['cpu','CPU %'],['mem','Mem (MB)'],['estado','Estado']];
    const senales = [['SIGTERM','fa-circle-stop','Terminar (SIGTERM)'],['SIGKILL','fa-skull','Matar (SIGKILL)'],['SIGSTOP','fa-pause','Pausar (SIGSTOP)'],['SIGCONT','fa-play','Reanudar (SIGCONT)']];

    const arbolHtml = lista => {
        const hijos = {}, ids = new Set(lista.map(p => p.pid));
        lista.forEach(p => (hijos[p.ppid] ||= []).push(p));
        const nodo = p => `<li>${p.pid} <b>${esc(p.cmd.slice(0, 70))}</b> <small>[${p.estado}]</small>` +
            (hijos[p.pid] ? '<ul style="list-style:none;padding-left:18px">' + hijos[p.pid].map(nodo).join('') + '</ul>' : '') + '</li>';
        return '<ul style="list-style:none;padding:0">' + lista.filter(p => !ids.has(p.ppid)).map(nodo).join('') + '</ul>';
    };

    function pintar() {
        const cuenta = {R: 0, S: 0, D: 0, Z: 0, T: 0};
        procs.forEach(p => { if (p.estado in cuenta) cuenta[p.estado]++; });
        $('#procResumen').innerHTML = Object.entries(cuenta).map(([k, v]) => `<span class="status-pill">${k}: ${v}</span>`).join(' ') + ` &nbsp;<b>Total: ${procs.length}</b>`;
        const q = $('#procBuscar').value.toLowerCase();
        const l = procs.filter(p => !q || Object.values(p).join(' ').toLowerCase().includes(q));
        l.sort((a, b) => (a[orden.k] > b[orden.k] ? 1 : -1) * orden.d);
        $('#procTabla thead').innerHTML = '<tr>' + cols.map(([k, n]) =>
            `<th data-k="${k}" style="cursor:pointer">${n}${orden.k == k ? (orden.d > 0 ? ' ▲' : ' ▼') : ''}</th>`).join('') + '<th>Acciones</th></tr>';
        $('#procTabla tbody').innerHTML = l.map(p => '<tr>' + cols.map(([k]) => k == 'estado'
            ? `<td><span class="status-pill ${p.estado == 'R' ? 'status-running' : ''}">${p.estado}</span></td>`
            : `<td>${esc(k == 'cmd' ? p.cmd.slice(0, 40) : p[k])}</td>`).join('') +
            '<td class="actions">' + (p.lanzado ? senales.map(([s, ic, t]) =>
                `<button class="icon-btn ${s == 'SIGKILL' ? 'danger' : ''}" title="${t}" data-pid="${p.pid}" data-senal="${s}"><i class="fa-solid ${ic}"></i></button>`).join('') +
                `<button class="icon-btn" title="Cambiar prioridad (renice)" data-pid="${p.pid}" data-renice="1"><i class="fa-solid fa-arrow-up-wide-short"></i></button>` : '') + '</td></tr>').join('');
        $('#procConteo').textContent = `Mostrando ${l.length} de ${procs.length} procesos`;
        $('#procArbol').innerHTML = arbolHtml(l);
    }
    const cargar = async () => { procs = await api('procesos'); pintar(); };

    $('#procBuscar').addEventListener('input', pintar);
    $('#procActualizar').addEventListener('click', cargar);
    $('#procVistaBtn').addEventListener('click', () => {
        verArbol = !verArbol;
        $('#procTablaPanel').style.display = verArbol ? 'none' : '';
        $('#procArbolPanel').style.display = verArbol ? '' : 'none';
        $('#procVistaBtn').innerHTML = verArbol ? '<i class="fa-solid fa-table"></i> Ver tabla' : '<i class="fa-solid fa-sitemap"></i> Ver árbol';
    });
    document.querySelectorAll('[data-lanzar]').forEach(b => b.addEventListener('click', async () => {
        const r = await api('lanzar', {tipo: b.dataset.lanzar});
        toast('Proceso de prueba lanzado con PID ' + r.pid); cargar();
    }));
    $('#procTabla').addEventListener('click', async e => {
        const th = e.target.closest('th[data-k]');
        if (th) { orden = {k: th.dataset.k, d: orden.k == th.dataset.k ? -orden.d : -1}; return pintar(); }
        const b = e.target.closest('button'); if (!b) return;
        const pid = Number(b.dataset.pid);
        let r;
        if (b.dataset.senal) r = await api('senal', {pid, senal: b.dataset.senal});
        else {
            const n = prompt('Nuevo valor nice (-20 a 19):', '10'); if (n === null) return;
            r = await api('renice', {pid, nice: Number(n)});
        }
        toast(r.error || 'Acción realizada'); cargar();
    });
    ciclo(cargar);
}

// ================= M2: CPU Y MEMORIA =================
if ($('#cpuRealChart')) {
    const linea = new Chart($('#cpuRealChart'), {type: 'line',
        data: {labels: [], datasets: [{data: [], borderColor: '#0875f7', backgroundColor: 'rgba(8,117,247,.12)', fill: true, tension: .35, pointRadius: 0, borderWidth: 2.5}]},
        options: {responsive: true, maintainAspectRatio: false, animation: false, plugins: {legend: {display: false}},
            scales: {y: {min: 0, max: 100, ticks: {callback: v => v + '%'}}}}});
    const dona = new Chart($('#memRealChart'), {type: 'doughnut',
        data: {labels: ['Usada', 'Caché', 'Libre'], datasets: [{data: [0, 0, 1], backgroundColor: ['#0875f7', '#69b3fa', '#a7bacb'], borderWidth: 0}]},
        options: {responsive: true, maintainAspectRatio: false, cutout: '58%', animation: false, plugins: {legend: {display: false}}}});
    const set = (id, t) => { $(id).textContent = t; };
    const top = (arr, k, u) => arr.map(p => `<div class="info-row"><span>${p.pid} · ${esc(p.cmd.slice(0, 45))}</span><strong>${p[k]} ${u}</strong></div>`).join('');

    ciclo(async () => {
        const d = await api('sistema'), m = d.mem;
        const libre = Math.max(m.total - m.usada - m.cache, 0);
        set('#cpuUso', d.uso + ' %'); $('#cpuBarra').style.width = d.uso + '%';
        set('#cpuCarga', d.load[0]);
        set('#ramTxt', gb(m.usada) + ' / ' + gb(m.total)); $('#ramBarra').style.width = pct(m.usada, m.total) + '%';
        set('#swapTxt', m.swap_usada + ' MB / ' + m.swap_total + ' MB'); $('#swapBarra').style.width = pct(m.swap_usada, m.swap_total) + '%';
        set('#infoModelo', d.modelo); set('#infoNucleos', d.nucleos);
        set('#infoUptime', Math.floor(d.uptime / 3600) + ' h ' + Math.floor(d.uptime % 3600 / 60) + ' min');
        set('#infoLoad', d.load.join(' / '));
        set('#memTotal', gb(m.total)); set('#memUsada', `${gb(m.usada)} (${pct(m.usada, m.total)}%)`);
        set('#memLibre', `${gb(libre)} (${pct(libre, m.total)}%)`); set('#memCache', `${gb(m.cache)} (${pct(m.cache, m.total)}%)`);
        set('#lgUsada', `${gb(m.usada)} (${pct(m.usada, m.total)}%)`); set('#lgCache', `${gb(m.cache)} (${pct(m.cache, m.total)}%)`); set('#lgLibre', `${gb(libre)} (${pct(libre, m.total)}%)`);
        dona.data.datasets[0].data = [m.usada, m.cache, libre]; dona.update();
        linea.data.labels.push(new Date().toLocaleTimeString()); linea.data.datasets[0].data.push(d.uso);
        if (linea.data.labels.length > 20) { linea.data.labels.shift(); linea.data.datasets[0].data.shift(); }
        linea.update();
        $('#topCpu').innerHTML = top(d.top_cpu, 'cpu', '%'); $('#topMem').innerHTML = top(d.top_mem, 'mem', 'MB');
    });
}
})();
