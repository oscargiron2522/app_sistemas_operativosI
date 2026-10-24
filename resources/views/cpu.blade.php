@extends('layouts.app')
@section('titulo', 'CPU y Memoria')
@section('subtitulo', 'Monitoreo en tiempo real del uso de CPU, memoria y carga del sistema.')
@section('contenido')
<div class="row g-3 mb-3">
    <div class="col-6 col-xl-3"><div class="lc-card lc-stat">
        <div class="d-flex justify-content-between"><div><div class="lc-etq">Uso de CPU</div><div class="lc-valor" id="c-cpu">–</div></div><i class="bi bi-cpu lc-icono"></i></div>
        <div class="progress mt-2"><div class="progress-bar bg-success" id="c-cpu-bar" style="width:0"></div></div>
    </div></div>
    <div class="col-6 col-xl-3"><div class="lc-card lc-stat">
        <div class="d-flex justify-content-between"><div><div class="lc-etq">Carga del sistema</div><div class="lc-valor" id="c-carga">–</div><div class="lc-etq" id="c-carga2">(1 min)</div></div><i class="bi bi-bar-chart-fill lc-icono"></i></div>
    </div></div>
    <div class="col-6 col-xl-3"><div class="lc-card lc-stat">
        <div class="d-flex justify-content-between"><div><div class="lc-etq">Memoria RAM</div><div class="lc-valor" id="c-ram">–</div></div><i class="bi bi-memory lc-icono"></i></div>
        <div class="progress mt-2"><div class="progress-bar" id="c-ram-bar" style="width:0"></div></div>
    </div></div>
    <div class="col-6 col-xl-3"><div class="lc-card lc-stat">
        <div class="d-flex justify-content-between"><div><div class="lc-etq">Swap</div><div class="lc-valor fs-5" id="c-swap">–</div></div><i class="bi bi-arrow-left-right lc-icono"></i></div>
        <div class="progress mt-2"><div class="progress-bar" id="c-swap-bar" style="width:0"></div></div>
    </div></div>
</div>

<div class="row g-3 mb-3">
    <div class="col-lg-8"><div class="lc-card">
        <h5>Uso de CPU en tiempo real</h5>
        <div style="height:270px"><canvas id="g-cpu"></canvas></div>
    </div></div>
    <div class="col-lg-4"><div class="lc-card">
        <h5>Distribución de memoria</h5>
        <div style="height:270px"><canvas id="g-mem"></canvas></div>
    </div></div>
</div>

<div class="row g-3 mb-3">
    <div class="col-lg-6"><div class="lc-card">
        <h5>Información de CPU</h5>
        <table class="w-100 lh-lg">
            <tr><td>Modelo</td><td class="text-end">{{ $cpu['modelo'] }}</td></tr>
            <tr><td>Núcleos</td><td class="text-end">{{ $cpu['nucleos'] }}</td></tr>
            <tr><td>Velocidad</td><td class="text-end">{{ $cpu['mhz'] ? number_format($cpu['mhz'] / 1000, 2) . ' GHz' : 'No disponible' }}</td></tr>
            <tr><td>Tiempo encendido</td><td class="text-end" id="i-up">–</td></tr>
            <tr><td>Carga promedio (1 / 5 / 15 min)</td><td class="text-end" id="i-carga">–</td></tr>
        </table>
    </div></div>
    <div class="col-lg-6"><div class="lc-card">
        <h5>Información de memoria</h5>
        <table class="w-100 lh-lg">
            <tr><td>Memoria total</td><td class="text-end" id="i-total">–</td></tr>
            <tr><td>Memoria usada</td><td class="text-end" id="i-usada">–</td></tr>
            <tr><td>Memoria libre</td><td class="text-end" id="i-libre">–</td></tr>
            <tr><td>Caché / Buffer</td><td class="text-end" id="i-cache">–</td></tr>
            <tr><td>Swap usada / total</td><td class="text-end" id="i-swap">–</td></tr>
        </table>
    </div></div>
</div>

<div class="row g-3">
    <div class="col-lg-6"><div class="lc-card">
        <h5>Top 5 procesos por CPU</h5>
        <table class="lc-tabla"><thead><tr><th>PID</th><th>Proceso</th><th>Usuario</th><th>CPU %</th></tr></thead><tbody id="top-cpu"></tbody></table>
    </div></div>
    <div class="col-lg-6"><div class="lc-card">
        <h5>Top 5 procesos por memoria</h5>
        <table class="lc-tabla"><thead><tr><th>PID</th><th>Proceso</th><th>Usuario</th><th>Memoria</th></tr></thead><tbody id="top-mem"></tbody></table>
    </div></div>
</div>
@endsection
@push('scripts')
<script>
const PUNTOS = 20;
const datosCpu = Array(PUNTOS).fill(null);
const gCpu = new Chart(document.getElementById('g-cpu'), {
  type: 'line',
  data: { labels: Array.from({length: PUNTOS}, (_, i) => ((PUNTOS - 1 - i) * 3) + 's'),
          datasets: [{ data: datosCpu, borderColor: '#1769ff', backgroundColor: 'rgba(23,105,255,.12)', fill: true, tension: .3, pointRadius: 2, spanGaps: true }] },
  options: { responsive: true, maintainAspectRatio: false, animation: false, plugins: { legend: { display: false } },
             scales: { y: { min: 0, max: 100, ticks: { callback: v => v + '%' } } } }
});
const gMem = new Chart(document.getElementById('g-mem'), {
  type: 'doughnut',
  data: { labels: ['Usada', 'Caché', 'Libre'], datasets: [{ data: [0, 0, 1], backgroundColor: ['#1769ff', '#6fb1ff', '#16b364'] }] },
  options: { responsive: true, maintainAspectRatio: false, animation: false, plugins: { legend: { position: 'bottom' },
             tooltip: { callbacks: { label: c => c.label + ': ' + LC.kb(c.raw) } } } }
});

async function metricas() {
  try {
    const d = await LC.get('{{ url('/cpu-memoria/datos') }}');
    const m = d.memoria;
    const pct = x => ' (' + Math.round(100 * x / m.total) + '%)';
    document.getElementById('c-cpu').textContent = d.cpu + ' %';
    document.getElementById('c-cpu-bar').style.width = d.cpu + '%';
    document.getElementById('c-carga').textContent = d.carga[0].toFixed(2);
    document.getElementById('c-ram').textContent = (m.usada / 1048576).toFixed(1) + ' / ' + (m.total / 1048576).toFixed(1) + ' GB';
    document.getElementById('c-ram-bar').style.width = (100 * m.usada / m.total) + '%';
    document.getElementById('c-swap').textContent = LC.kb(m.swap_usada) + ' / ' + LC.kb(m.swap_total);
    document.getElementById('c-swap-bar').style.width = (m.swap_total ? 100 * m.swap_usada / m.swap_total : 0) + '%';
    document.getElementById('i-up').textContent = LC.segs(d.uptime) + ' (desde ' + d.encendido + ')';
    document.getElementById('i-carga').textContent = d.carga.join(' / ');
    document.getElementById('i-total').textContent = LC.kb(m.total);
    document.getElementById('i-usada').textContent = LC.kb(m.usada) + pct(m.usada);
    document.getElementById('i-libre').textContent = LC.kb(m.libre) + pct(m.libre);
    document.getElementById('i-cache').textContent = LC.kb(m.cache) + pct(m.cache);
    document.getElementById('i-swap').textContent = LC.kb(m.swap_usada) + ' / ' + LC.kb(m.swap_total);
    datosCpu.push(d.cpu); datosCpu.shift(); gCpu.update();
    gMem.data.datasets[0].data = [m.usada, m.cache, m.libre]; gMem.update();
  } catch (e) { console.error(e); }
}

async function CargaTop() {
  try {
    const d = await LC.get('{{ url('/procesos/top') }}');
    const fila = (p, v) => '<tr><td>' + p.pid + '</td><td>' + LC.esc(p.nombre) + '</td><td>' + LC.esc(p.usuario) + '</td><td>' + v + '</td></tr>';
    document.getElementById('top-cpu').innerHTML = d.cpu.map(p => fila(p, p.cpu.toFixed(1) + ' %')).join('');
    document.getElementById('top-mem').innerHTML = d.mem.map(p => fila(p, LC.kb(p.mem_kb) + ' (' + p.mem_pct.toFixed(1) + '%)')).join('');
  } catch (e) { console.error(e); }
}

metricas(); CargaTop();
setInterval(metricas, 3000);
setInterval(CargaTop, 5000);
</script>
@endpush
