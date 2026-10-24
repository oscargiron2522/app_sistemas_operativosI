@extends('layouts.app')
@section('titulo', 'Dashboard')
@section('subtitulo', 'Resumen general del sistema en tiempo real')
@section('contenido')
<div class="row g-3 mb-3">
    <div class="col-6 col-xl-3"><div class="lc-card lc-stat">
        <div class="d-flex justify-content-between"><div><div class="lc-etq">Uso de CPU</div><div class="lc-valor" id="d-cpu">–</div></div><i class="bi bi-cpu lc-icono"></i></div>
        <div class="progress mt-2"><div class="progress-bar bg-success" id="d-cpu-bar" style="width:0"></div></div>
    </div></div>
    <div class="col-6 col-xl-3"><div class="lc-card lc-stat">
        <div class="d-flex justify-content-between"><div><div class="lc-etq">Memoria RAM</div><div class="lc-valor" id="d-ram">–</div></div><i class="bi bi-memory lc-icono"></i></div>
        <div class="progress mt-2"><div class="progress-bar" id="d-ram-bar" style="width:0"></div></div>
    </div></div>
    <div class="col-6 col-xl-3"><div class="lc-card lc-stat">
        <div class="d-flex justify-content-between"><div><div class="lc-etq">Procesos</div><div class="lc-valor" id="d-procs">–</div><div class="lc-etq" id="d-run"></div></div><i class="bi bi-gear-fill lc-icono"></i></div>
    </div></div>
    <div class="col-6 col-xl-3"><div class="lc-card lc-stat">
        <div class="d-flex justify-content-between"><div><div class="lc-etq">Tiempo activo</div><div class="lc-valor" id="d-up">–</div><div class="lc-etq" id="d-desde"></div></div><i class="bi bi-clock-fill lc-icono"></i></div>
    </div></div>
</div>
<div class="row g-3">
    <div class="col-lg-7"><div class="lc-card">
        <h5>Uso de CPU (últimos 60 segundos)</h5>
        <div style="height:260px"><canvas id="g-cpu"></canvas></div>
    </div></div>
    <div class="col-lg-5"><div class="lc-card">
        <h5>Distribución de memoria</h5>
        <div class="progress my-3" style="height:34px;border-radius:2px">
            <div class="progress-bar" id="b-usada" style="background:#0a55d6"></div>
            <div class="progress-bar" id="b-cache" style="background:#3d9bff"></div>
            <div class="progress-bar" id="b-libre" style="background:#b4c2d0"></div>
        </div>
        <table class="w-100">
            <tr><td><span class="badge" style="background:#0a55d6">&nbsp;</span> Usada</td><td class="text-end" id="m-usada"></td></tr>
            <tr><td><span class="badge" style="background:#3d9bff">&nbsp;</span> Caché / Buffer</td><td class="text-end" id="m-cache"></td></tr>
            <tr><td><span class="badge" style="background:#b4c2d0">&nbsp;</span> Libre</td><td class="text-end" id="m-libre"></td></tr>
        </table>
        <hr>
        <div class="d-flex justify-content-between"><span>Swap:</span><span id="m-swap"></span></div>
        <div class="d-flex justify-content-between"><span>Carga promedio (1/5/15 min):</span><span id="m-carga"></span></div>
    </div></div>
</div>
@endsection
@push('scripts')
<script>
const PUNTOS = 20;               // 20 lecturas x 3 s = 60 s
const datosCpu = Array(PUNTOS).fill(null);
const etiquetas = Array.from({length: PUNTOS}, (_, i) => ((PUNTOS - 1 - i) * 3) + 's');
const grafico = new Chart(document.getElementById('g-cpu'), {
  type: 'line',
  data: { labels: etiquetas, datasets: [{ data: datosCpu, borderColor: '#1769ff', backgroundColor: 'rgba(23,105,255,.12)', fill: true, tension: .3, pointRadius: 2, spanGaps: true }] },
  options: { responsive: true, maintainAspectRatio: false, animation: false, plugins: { legend: { display: false } },
             scales: { y: { min: 0, max: 100, ticks: { callback: v => v + '%' } } } }
});

async function actualizar() {
  try {
    const d = await LC.get('{{ url('/cpu-memoria/datos') }}');
    const m = d.memoria;
    document.getElementById('d-cpu').textContent = d.cpu + ' %';
    document.getElementById('d-cpu-bar').style.width = d.cpu + '%';
    document.getElementById('d-ram').textContent = (m.usada / 1048576).toFixed(1) + ' / ' + (m.total / 1048576).toFixed(1) + ' GB';
    document.getElementById('d-ram-bar').style.width = (100 * m.usada / m.total) + '%';
    document.getElementById('d-procs').textContent = d.procesos;
    document.getElementById('d-run').textContent = d.ejecutando + ' en ejecución';
    document.getElementById('d-up').textContent = LC.segs(d.uptime);
    document.getElementById('d-desde').textContent = 'Desde ' + d.encendido.substring(0, 10);
    const p = x => (100 * x / m.total) + '%';
    document.getElementById('b-usada').style.width = p(m.usada);
    document.getElementById('b-cache').style.width = p(m.cache);
    document.getElementById('b-libre').style.width = p(m.libre);
    document.getElementById('m-usada').textContent = LC.kb(m.usada);
    document.getElementById('m-cache').textContent = LC.kb(m.cache);
    document.getElementById('m-libre').textContent = LC.kb(m.libre);
    document.getElementById('m-swap').textContent = LC.kb(m.swap_usada) + ' / ' + LC.kb(m.swap_total);
    document.getElementById('m-carga').textContent = d.carga.join(' / ');
    datosCpu.push(d.cpu); datosCpu.shift();
    grafico.update();
  } catch (e) { console.error(e); }
}
actualizar();
setInterval(actualizar, 3000);
</script>
@endpush
