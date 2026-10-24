<?php

use App\Http\Controllers\AlmacenamientoController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BitacoraController;
use App\Http\Controllers\CpuMemoriaController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InterbloqueosController;
use App\Http\Controllers\PlanificacionController;
use App\Http\Controllers\ProcesosController;
use App\Http\Controllers\UsuariosController;
use Illuminate\Support\Facades\Route;

// Sin registro de usuarios: solo login/logout
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'mostrarLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
});
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

Route::middleware('auth')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // M1 Procesos
    Route::get('/procesos', [ProcesosController::class, 'index'])->name('procesos');
    Route::get('/procesos/datos', [ProcesosController::class, 'datos']);
    Route::get('/procesos/top', [ProcesosController::class, 'top']);
    Route::get('/procesos/pruebas', [ProcesosController::class, 'pruebas']);
    Route::post('/procesos/lanzar', [ProcesosController::class, 'lanzar']);
    Route::post('/procesos/senal', [ProcesosController::class, 'senal']);
    Route::post('/procesos/renice', [ProcesosController::class, 'renice']);
    Route::get('/procesos/{pid}', [ProcesosController::class, 'detalle'])->whereNumber('pid');

    // M2 CPU y Memoria
    Route::get('/cpu-memoria', [CpuMemoriaController::class, 'index'])->name('cpu');
    Route::get('/cpu-memoria/datos', [CpuMemoriaController::class, 'datos']);

    // M3 Planificacion
    Route::get('/planificacion', [PlanificacionController::class, 'index'])->name('planificacion');
    Route::post('/planificacion/simular', [PlanificacionController::class, 'simular']);
    Route::get('/planificacion/historial', [PlanificacionController::class, 'historial']);
    Route::get('/planificacion/{id}', [PlanificacionController::class, 'ver'])->whereNumber('id');

    // M4 Interbloqueos
    Route::get('/interbloqueos', [InterbloqueosController::class, 'index'])->name('interbloqueos');
    Route::post('/interbloqueos/banquero', [InterbloqueosController::class, 'banquero']);
    Route::post('/interbloqueos/deteccion', [InterbloqueosController::class, 'deteccion']);

    // M5 Almacenamiento y usuarios
    Route::get('/almacenamiento', [AlmacenamientoController::class, 'index'])->name('almacenamiento');
    Route::get('/explorador', [AlmacenamientoController::class, 'explorador'])->name('explorador');
    Route::get('/usuarios', [UsuariosController::class, 'index'])->name('usuarios');

    // M6 Bitacora
    Route::get('/bitacora', [BitacoraController::class, 'index'])->name('bitacora');
});
