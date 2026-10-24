<?php

namespace App\Http\Controllers;

use App\Models\Bitacora;

class BitacoraController extends Controller
{
    public function index()
    {
        $registros = Bitacora::orderByDesc('id')->paginate(15);
        return view('bitacora', compact('registros'));
    }
}
