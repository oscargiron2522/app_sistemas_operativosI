@extends('layouts.app')
@section('titulo', 'Usuarios y Grupos')
@section('subtitulo', 'Usuarios y grupos del sistema (solo lectura) y sesiones activas')
@section('contenido')
<div class="lc-card">
    <ul class="nav nav-tabs mb-3">
        <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#t-usr">Usuarios</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#t-grp">Grupos</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#t-ses">Sesiones activas ({{ count($sesiones) }})</button></li>
    </ul>
    <div class="tab-content">
        <div class="tab-pane fade show active" id="t-usr">
            <div class="d-flex gap-2 align-items-center mb-3 flex-wrap">
                <select id="f-tipo" class="form-select w-auto">
                    <option value="">Todos ({{ count($usuarios) }})</option>
                    <option value="humano">Usuarios humanos ({{ count(array_filter($usuarios, fn($u) => $u['tipo'] === 'humano')) }})</option>
                    <option value="sistema">Usuarios de sistema ({{ count(array_filter($usuarios, fn($u) => $u['tipo'] === 'sistema')) }})</option>
                </select>
                <span class="text-muted small">Humano: UID ≥ 1000. Sistema: servicios y cuentas internas (incluye root).</span>
            </div>
            <div class="lc-wrap"><table class="lc-tabla" id="t-usuarios">
                <thead><tr><th>Usuario</th><th>UID</th><th>Grupo primario</th><th>Directorio</th><th>Shell</th><th>Tipo</th><th>Estado</th></tr></thead>
                <tbody>
                @foreach ($usuarios as $u)
                    <tr data-tipo="{{ $u['tipo'] }}"><td>{{ $u['usuario'] }}</td><td>{{ $u['uid'] }}</td><td>{{ $u['grupo'] }}</td><td>{{ $u['home'] }}</td><td>{{ $u['shell'] }}</td>
                        <td><span class="badge {{ $u['tipo'] === 'humano' ? 'text-bg-primary' : 'text-bg-secondary' }}">{{ $u['tipo'] === 'humano' ? 'Humano' : 'Sistema' }}</span></td>
                        <td><span class="{{ $u['login'] ? 'badge-ok' : 'badge-medio' }}">{{ $u['login'] ? 'Con login' : 'Sin login' }}</span></td></tr>
                @endforeach
                </tbody></table></div>
        </div>
        <div class="tab-pane fade" id="t-grp">
            <div class="lc-wrap"><table class="lc-tabla">
                <thead><tr><th>Grupo</th><th>GID</th><th>Miembros</th></tr></thead>
                <tbody>@foreach ($grupos as $g)<tr><td>{{ $g['nombre'] }}</td><td>{{ $g['gid'] }}</td><td>{{ $g['miembros'] ? implode(', ', $g['miembros']) : '—' }}</td></tr>@endforeach</tbody></table></div>
        </div>
        <div class="tab-pane fade" id="t-ses">
            <div class="lc-wrap"><table class="lc-tabla">
                <thead><tr><th>Usuario</th><th>Terminal</th><th>Inicio de sesión</th><th>Origen</th></tr></thead>
                <tbody>@forelse ($sesiones as $s)<tr><td>{{ $s['usuario'] }}</td><td>{{ $s['terminal'] }}</td><td>{{ $s['inicio'] }}</td><td>{{ $s['origen'] ?: '—' }}</td></tr>
                @empty<tr><td colspan="4" class="text-center text-muted py-3">No hay sesiones activas (comando who).</td></tr>@endforelse</tbody></table></div>
        </div>
    </div>
</div>
@endsection
@push('scripts')
<script>
document.getElementById('f-tipo').addEventListener('change', e => {
  document.querySelectorAll('#t-usuarios tbody tr').forEach(tr => tr.style.display = (!e.target.value || tr.dataset.tipo === e.target.value) ? '' : 'none');
});
</script>
@endpush
