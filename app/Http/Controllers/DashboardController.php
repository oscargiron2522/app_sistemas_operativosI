<?php

namespace App\Http\Controllers;

class DashboardController extends Controller
{
    // Los indicadores se refrescan desde la vista con /cpu-memoria/datos
    public function index()
    {
        return view('dashboard');
    }
}
