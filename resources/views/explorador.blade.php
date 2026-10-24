@extends('layouts.app')
@section('titulo', 'Explorador de archivos')
@section('subtitulo', 'Navegación de solo lectura en directorios permitidos')
@php
    $tam = function ($b) {
        if ($b === null) return '—';
        $u = ['B', 'KB', 'MB', 'GB', 'TB']; $i = 0;
        while ($b >= 1024 && $i < 4) { $b /= 1024; $i++; }
        return ($i ? number_format($b, 1, '.', '') : (int) $b) . ' ' . $u[$i];
    };
@endphp
@section('contenido')
<div class="lc-card">
    <div class="d-flex align-items-center gap-2 mb-3 flex-wrap">
        <a href="{{ route('almacenamiento') }}" class="btn btn-gris btn-sm"><i class="bi bi-arrow-left"></i> Almacenamiento</a>
        <a href="{{ route('explorador') }}" class="btn btn-gris btn-sm"><i class="bi bi-house"></i> Directorios permitidos</a>
        @if ($real)
            @if ($padre !== null)<a class="btn btn-gris btn-sm" href="{{ $padre === '' ? route('explorador') : route('explorador', ['ruta' => $padre]) }}"><i class="bi bi-arrow-up"></i> Subir</a>@endif
            <code class="ms-2 fs-6">{{ $real }}</code>
        @endif
    </div>

    @if ($error)<div class="alert alert-warning">{{ $error }}</div>@endif

    @if (!$real && !$error)
        <p>Directorios que se pueden explorar (configurables con <code>SYSMON_RUTAS</code> en el archivo <code>.env</code>):</p>
        <div class="list-group" style="max-width:460px">
            @forelse ($permitidas as $p)
                <a class="list-group-item list-group-item-action" href="{{ route('explorador', ['ruta' => $p]) }}"><i class="bi bi-folder-fill text-warning me-2"></i>{{ $p }}</a>
            @empty
                <div class="text-muted">No hay directorios permitidos configurados.</div>
            @endforelse
        </div>
    @elseif ($real)
        <div class="lc-wrap"><table class="lc-tabla">
            <thead><tr><th>Nombre</th><th>Permisos</th><th>Propietario</th><th>Grupo</th><th>Tamaño</th><th>Modificado</th></tr></thead>
            <tbody>
            @forelse ($entradas as $e)
                <tr>
                    <td>
                        @if ($e['dir'])<i class="bi bi-folder-fill text-warning me-1"></i><a href="{{ route('explorador', ['ruta' => $e['ruta']]) }}">{{ $e['nombre'] }}</a>
                        @elseif ($e['tipo'] === 'link')<i class="bi bi-link-45deg me-1"></i>{{ $e['nombre'] }}
                        @elseif ($e['tipo'] === 'dir')<i class="bi bi-folder me-1 text-muted"></i>{{ $e['nombre'] }} <small class="text-muted">(fuera de lo permitido)</small>
                        @else<i class="bi bi-file-earmark me-1 text-muted"></i>{{ $e['nombre'] }}@endif
                    </td>
                    <td><code>{{ $e['permisos'] }}</code></td><td>{{ $e['usuario'] }}</td><td>{{ $e['grupo'] }}</td><td>{{ $tam($e['tam']) }}</td><td>{{ $e['fecha'] }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center text-muted">Directorio vacío</td></tr>
            @endforelse
            </tbody></table></div>
        <div class="text-muted small mt-2">{{ count($entradas) }} elementos · solo se listan metadatos; no se muestra ni se modifica el contenido de los archivos.</div>
    @endif
</div>
@endsection
