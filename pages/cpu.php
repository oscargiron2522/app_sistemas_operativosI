<section class="stats-grid cpu-stats">
    <article class="stat-card"><div><p class="stat-label">Uso de CPU</p><h2 id="cpuUso">-- %</h2></div><i class="fa-solid fa-microchip stat-icon"></i><div class="progress"><span class="green" id="cpuBarra" style="width:0%"></span></div></article>
    <article class="stat-card"><div><p class="stat-label">Carga del sistema</p><h2 id="cpuCarga">--</h2><p class="stat-subtitle">(1 min)</p></div><i class="fa-solid fa-chart-column stat-icon"></i></article>
    <article class="stat-card"><div><p class="stat-label">Memoria RAM</p><h2 id="ramTxt">--</h2></div><i class="fa-solid fa-memory stat-icon"></i><div class="progress"><span id="ramBarra" style="width:0%"></span></div></article>
    <article class="stat-card"><div><p class="stat-label">Swap</p><h2 class="medium-number" id="swapTxt">--</h2></div><i class="fa-solid fa-right-left stat-icon"></i><div class="progress"><span id="swapBarra" style="width:0%"></span></div></article>
</section>
<section class="cpu-panels">
    <article class="panel"><h2>Uso de CPU en tiempo real</h2><div class="chart-wrap tall"><canvas id="cpuRealChart"></canvas></div></article>
    <article class="panel"><h2>Distribución de memoria</h2><div class="memory-chart-layout"><div class="doughnut-wrap"><canvas id="memRealChart"></canvas></div><div class="memory-legend">
        <div class="legend-row"><i class="legend-dot used"></i><span>Usada<br><small id="lgUsada">--</small></span></div>
        <div class="legend-row"><i class="legend-dot cache"></i><span>Caché<br><small id="lgCache">--</small></span></div>
        <div class="legend-row"><i class="legend-dot free"></i><span>Libre<br><small id="lgLibre">--</small></span></div>
    </div></div></article>
</section>
<section class="info-grid">
    <article class="panel"><h2>Información de CPU</h2>
        <div class="info-row"><span>Modelo</span><strong id="infoModelo">--</strong></div>
        <div class="info-row"><span>Núcleos</span><strong id="infoNucleos">--</strong></div>
        <div class="info-row"><span>Tiempo encendido</span><strong id="infoUptime">--</strong></div>
        <div class="info-row"><span>Carga (1 / 5 / 15 min)</span><strong id="infoLoad">--</strong></div></article>
    <article class="panel"><h2>Información de memoria</h2>
        <div class="info-row"><span>Memoria total</span><strong id="memTotal">--</strong></div>
        <div class="info-row"><span>Memoria usada</span><strong id="memUsada">--</strong></div>
        <div class="info-row"><span>Memoria libre</span><strong id="memLibre">--</strong></div>
        <div class="info-row"><span>Caché</span><strong id="memCache">--</strong></div></article>
</section>
<section class="info-grid">
    <article class="panel"><h2>Top 5 por CPU</h2><div id="topCpu"></div></article>
    <article class="panel"><h2>Top 5 por memoria</h2><div id="topMem"></div></article>
</section>
<script src="assets/js/monitor.js" defer></script>
