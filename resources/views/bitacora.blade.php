@extends('layouts.app')
@section('titulo', 'Bitácora')
@section('subtitulo', 'Registro de acciones administrativas y accesos')
@section('contenido')
<div class="lc-card">
    <div class="lc-wrap">
    <table class="lc-tabla">
        <thead><tr><th>#</th><th>Fecha y hora</th><th>Usuario</th><th>Acción</th><th>Proceso / objetivo</th><th>Resultado</th><th>IP</th></tr></thead>
        <tbody>
        @forelse ($registros as $r)
            <tr>
                <td>{{ $r->id }}</td>
                <td>{{ $r->created_at->format('Y-m-d H:i:s') }}</td>
                <td>{{ $r->usuario }}</td>
                <td>{{ $r->accion }}</td>
                <td>{{ $r->objetivo ?? '—' }}</td>
                <td>{{ $r->resultado ?? '—' }}</td>
                <td>{{ $r->ip }}</td>
            </tr>
        @empty
            <tr><td colspan="7" class="text-center text-muted py-4">Aún no hay registros.</td></tr>
        @endforelse
        </tbody>
    </table>
    </div>
    <div class="mt-3 d-flex justify-content-between align-items-center">
        <span class="text-muted">Total: {{ $registros->total() }} registros</span>
        {{ $registros->links() }}
    </div>
</div>
@endsection
