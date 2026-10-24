@extends('layouts.app')
@section('titulo', 'Almacenamiento')
@section('subtitulo', 'Información de discos, particiones y sistemas de archivos')
@php
    $h = function ($b) {
        if ($b === null) return '—';
        $u = ['B', 'KB', 'MB', 'GB', 'TB']; $i = 0;
        while ($b >= 1024 && $i < 4) { $b /= 1024; $i++; }
        return ($i ? number_format($b, $b < 10 ? 1 : 0, '.', '') : (int) $b) . ' ' . $u[$i];
    };
    $clase = fn($p) => $p === null ? '' : ($p >= 85 ? 'badge-alto' : ($p >= 60 ? 'badge-medio' : 'badge-ok'));
@endphp
@section('contenido')
@foreach ($errores as $e)<div class="alert alert-warning">{{ $e }}</div>@endforeach

<div class="row g-3 mb-3">
    <div class="col-6 col-xl-3"><div class="lc-card lc-stat d-flex align-items-center gap-3"><i class="bi bi-hdd-fill lc-icono" style="font-size:44px"></i><div><div class="lc-etq">Discos totales</div><div class="lc-valor">{{ $h($resumen['discos']) }}</div><div class="lc-etq">(Uso físico)</div></div></div></div>
    <div class="col-6 col-xl-3"><div class="lc-card lc-stat"><div class="lc-etq">Uso total</div><div class="lc-valor">{{ $h($resumen['usado']) }} ({{ $resumen['pct'] }}%)</div><div class="progress mt-2"><div class="progress-bar" style="width:{{ $resumen['pct'] }}%"></div></div></div></div>
    <div class="col-6 col-xl-3"><div class="lc-card lc-stat"><div class="lc-etq">Espacio libre</div><div class="lc-valor">{{ $h($resumen['libre']) }} ({{ $resumen['pctLibre'] }}%)</div><div class="progress mt-2"><div class="progress-bar bg-success" style="width:{{ $resumen['pctLibre'] }}%"></div></div></div></div>
    <div class="col-6 col-xl-3"><div class="lc-card lc-stat d-flex align-items-center gap-3"><i class="bi bi-pie-chart-fill lc-icono" style="font-size:44px"></i><div><div class="lc-etq">Particiones / volúmenes</div><div class="lc-valor">{{ $resumen['particiones'] }}</div></div></div></div>
</div>

<div class="row g-3 mb-3">
    <div class="col-xl-8">
        <div class="lc-card mb-3">
            <h5>Discos físicos y tabla de particiones</h5>
            <div class="lc-wrap"><table class="lc-tabla">
                <thead><tr><th>Disco</th><th>Modelo</th><th>Tamaño</th><th>Tabla de particiones</th><th>Particiones</th><th>Solo lectura</th></tr></thead>
                <tbody>
                @forelse ($discos as $d)
                    <tr><td>{{ $d['nombre'] }}</td><td>{{ $d['modelo'] }}</td><td>{{ $h($d['tam']) }}</td>
                        <td><span class="badge text-bg-primary">{{ $d['tabla'] }}</span></td><td>{{ $d['particiones'] }}</td><td>{{ $d['ro'] ? 'Sí' : 'No' }}</td></tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted">Sin datos de lsblk</td></tr>
                @endforelse
                </tbody></table></div>
            <div class="small text-muted mt-2">MBR corresponde a la tabla «dos» y GPT a la tabla «gpt» que informa lsblk. «—» indica un disco sin tabla de particiones (por ejemplo, un disco virtual formateado completo).</div>
        </div>

        <div class="lc-card mb-3">
            <h5>Particiones (lsblk)</h5>
            <div class="lc-wrap"><table class="lc-tabla">
                <thead><tr><th>Dispositivo</th><th>Punto de montaje</th><th>Sistema de archivos</th><th>Tamaño</th><th>Usado</th><th>Libre</th><th>Uso %</th></tr></thead>
                <tbody>
                @forelse ($particiones as $p)
                    <tr><td>{{ $p['dispositivo'] }}</td><td>{{ $p['montaje'] }}</td><td>{{ $p['fs'] }}</td><td>{{ $h($p['tam']) }}</td><td>{{ $h($p['usado']) }}</td><td>{{ $h($p['libre']) }}</td>
                        <td>@if ($p['pct'] !== null)<span class="{{ $clase($p['pct']) }}">{{ $p['pct'] }}%</span>@else — @endif</td></tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted">Sin datos</td></tr>
                @endforelse
                </tbody></table></div>
        </div>

        <div class="lc-card">
            <h5>Sistemas de archivos montados (df)</h5>
            <div class="lc-wrap"><table class="lc-tabla">
                <thead><tr><th>Dispositivo</th><th>Tipo</th><th>Total</th><th>Usado</th><th>Disponible</th><th style="min-width:150px">Ocupación</th><th>Montado en</th></tr></thead>
                <tbody>
                @forelse ($df as $f)
                    <tr><td>{{ $f['fuente'] }}</td><td>{{ $f['tipo'] }}</td><td>{{ $h($f['total']) }}</td><td>{{ $h($f['usado']) }}</td><td>{{ $h($f['libre']) }}</td>
                        <td><div class="progress" style="height:14px"><div class="progress-bar {{ $f['pct'] >= 85 ? 'bg-danger' : ($f['pct'] >= 60 ? 'bg-warning' : 'bg-success') }}" style="width:{{ $f['pct'] }}%">{{ $f['pct'] }}%</div></div></td>
                        <td>{{ $f['montaje'] }}</td></tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted">Sin datos de df</td></tr>
                @endforelse
                </tbody></table></div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="lc-card mb-3">
            <h5>Uso de disco</h5>
            <div style="height:260px"><canvas id="g-disco"></canvas></div>
            <div class="mt-2 d-flex justify-content-between"><span><span class="badge" style="background:#1769ff">&nbsp;</span> Usado</span><span>{{ $h($resumen['usado']) }} ({{ $resumen['pct'] }}%)</span></div>
            <div class="d-flex justify-content-between"><span><span class="badge" style="background:#b4c2d0">&nbsp;</span> Libre</span><span>{{ $h($resumen['libre']) }} ({{ $resumen['pctLibre'] }}%)</span></div>
        </div>
        <div class="lc-card">
            <h5>Explorador de archivos</h5>
            <p class="text-muted">Navegue en modo de solo lectura por los directorios permitidos, con permisos, propietario y tamaño.</p>
            <a href="{{ route('explorador') }}" class="btn btn-lc w-100"><i class="bi bi-folder2-open me-1"></i> Abrir explorador</a>
        </div>
    </div>
</div>

<div class="lc-card">
    <ul class="nav nav-tabs mb-3">
        <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#t-mnt">Puntos de montaje (findmnt)</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#t-fstab">/etc/fstab</button></li>
    </ul>
    <div class="tab-content">
        <div class="tab-pane fade show active" id="t-mnt"><div class="lc-wrap"><table class="lc-tabla">
            <thead><tr><th>Destino</th><th>Origen</th><th>Tipo</th><th>Opciones</th></tr></thead>
            <tbody>@forelse ($montajes as $m)<tr><td>{{ $m['destino'] }}</td><td>{{ $m['origen'] }}</td><td>{{ $m['fs'] }}</td><td class="small">{{ $m['opciones'] }}</td></tr>
            @empty<tr><td colspan="4" class="text-center text-muted">Sin datos</td></tr>@endforelse</tbody></table></div></div>
        <div class="tab-pane fade" id="t-fstab"><div class="lc-wrap"><table class="lc-tabla">
            <thead><tr><th>Sistema de archivos</th><th>Punto de montaje</th><th>Tipo</th><th>Opciones</th><th>dump</th><th>pass</th></tr></thead>
            <tbody>@forelse ($fstab as $f)<tr>@foreach ($f as $c)<td>{{ $c }}</td>@endforeach</tr>
            @empty<tr><td colspan="6" class="text-center text-muted">/etc/fstab no tiene entradas o no se puede leer</td></tr>@endforelse</tbody></table></div></div>
    </div>
</div>
@endsection
@push('scripts')
<script>
new Chart(document.getElementById('g-disco'), {
  type: 'doughnut',
  data: { labels: ['Usado', 'Libre'], datasets: [{ data: [{{ $resumen['usado'] }}, {{ $resumen['libre'] }}], backgroundColor: ['#1769ff', '#b4c2d0'] }] },
  options: { responsive: true, maintainAspectRatio: false, cutout: '68%', plugins: { legend: { display: false },
    tooltip: { callbacks: { label: c => c.label + ': ' + (c.raw / 1073741824).toFixed(1) + ' GB' } } } }
});
</script>
@endpush
