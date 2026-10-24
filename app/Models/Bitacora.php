<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Bitacora extends Model
{
    protected $table = 'bitacora';

    protected $guarded = [];

    // Registra una accion en la bitacora (usuario, fecha/hora, accion, objetivo, resultado)
    public static function registrar(string $accion, ?string $objetivo = null, ?string $resultado = null): void
    {
        $u = Auth::user();
        static::create([
            'user_id'   => $u?->id,
            'usuario'   => $u?->usuario ?? (string) request('usuario', '-'),
            'accion'    => $accion,
            'objetivo'  => $objetivo,
            'resultado' => $resultado,
            'ip'        => request()->ip(),
        ]);
    }
}
