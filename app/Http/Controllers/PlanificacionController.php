<?php

namespace App\Http\Controllers;

use App\Models\Simulacion;
use Illuminate\Http\Request;

// M3 - Planificacion de CPU (simulado): FCFS, SJF, SRTF, Round Robin y Prioridades
class PlanificacionController extends Controller
{
    const ALGORITMOS = [
        'FCFS'     => 'FCFS (First Come First Served)',
        'SJF'      => 'SJF (no expropiativo)',
        'SRTF'     => 'SRTF (Shortest Remaining Time First)',
        'RR'       => 'Round Robin',
        'PRIO'     => 'Prioridades (no expropiativo)',
        'PRIO_EXP' => 'Prioridades (expropiativo)',
    ];

    public function index()
    {
        return view('planificacion', ['algoritmos' => self::ALGORITMOS]);
    }

    public function simular(Request $request)
    {
        $d = $request->validate([
            'algoritmo'            => ['required', 'in:' . implode(',', array_keys(self::ALGORITMOS))],
            'quantum'              => ['nullable', 'integer', 'between:1,100'],
            'procesos'             => ['required', 'array', 'min:1', 'max:30'],
            'procesos.*.llegada'   => ['required', 'integer', 'between:0,1000'],
            'procesos.*.rafaga'    => ['required', 'integer', 'between:1,1000'],
            'procesos.*.prioridad' => ['required', 'integer', 'between:0,100'],
        ]);
        $quantum = (int) ($d['quantum'] ?? 2);

        // Los nombres P1..Pn los asigna el servidor
        $procs = [];
        foreach (array_values($d['procesos']) as $i => $p) {
            $procs[] = ['pid' => 'P' . ($i + 1), 'llegada' => (int) $p['llegada'],
                        'rafaga' => (int) $p['rafaga'], 'prioridad' => (int) $p['prioridad']];
        }

        $res = self::ejecutar($d['algoritmo'], $procs, $quantum);

        $comparativa = [];
        foreach (self::ALGORITMOS as $clave => $nombre) {
            $r = self::ejecutar($clave, $procs, $quantum);
            $comparativa[] = ['algoritmo' => $clave, 'nombre' => $nombre, 'espera' => $r['espera_promedio'],
                              'retorno' => $r['retorno_promedio'], 'respuesta' => $r['respuesta_promedio'],
                              'utilizacion' => $r['utilizacion']];
        }
        $res['comparativa'] = $comparativa;

        $sim = Simulacion::create([
            'user_id' => auth()->id(), 'algoritmo' => $d['algoritmo'], 'quantum' => $d['algoritmo'] === 'RR' ? $quantum : null,
            'procesos' => $procs, 'resultado' => $res,
            'espera_promedio' => $res['espera_promedio'], 'retorno_promedio' => $res['retorno_promedio'],
            'utilizacion' => $res['utilizacion'],
        ]);
        $res['id'] = $sim->id;

        return response()->json($res);
    }

    public function historial()
    {
        $filas = Simulacion::orderByDesc('id')->limit(20)->get()->map(fn($s) => [
            'id' => $s->id, 'fecha' => $s->created_at->format('Y-m-d H:i'),
            'algoritmo' => self::ALGORITMOS[$s->algoritmo] ?? $s->algoritmo, 'quantum' => $s->quantum,
            'n' => count($s->procesos), 'espera' => (float) $s->espera_promedio, 'retorno' => (float) $s->retorno_promedio,
            'utilizacion' => (float) $s->utilizacion,
        ]);
        return response()->json(['historial' => $filas]);
    }

    public function ver(int $id)
    {
        $s = Simulacion::findOrFail($id);
        $res = $s->resultado;
        $res['id'] = $s->id;
        return response()->json(['algoritmo' => $s->algoritmo, 'quantum' => $s->quantum, 'procesos' => $s->procesos, 'resultado' => $res]);
    }

    // ------------------------------------------------------------------ algoritmos (estaticos)

    public static function ejecutar(string $algoritmo, array $procs, int $quantum = 2): array
    {
        foreach ($procs as $i => &$p) {
            $p['idx'] = $i;
        }
        unset($p);

        switch ($algoritmo) {
            case 'FCFS':
                [$gantt, $fin, $ini] = self::noExpropiativo($procs, fn($a, $b) => [$a['llegada'], $a['idx']] <=> [$b['llegada'], $b['idx']]);
                break;
            case 'SJF':
                [$gantt, $fin, $ini] = self::noExpropiativo($procs, fn($a, $b) => [$a['rafaga'], $a['llegada'], $a['idx']] <=> [$b['rafaga'], $b['llegada'], $b['idx']]);
                break;
            case 'PRIO':
                [$gantt, $fin, $ini] = self::noExpropiativo($procs, fn($a, $b) => [$a['prioridad'], $a['llegada'], $a['idx']] <=> [$b['prioridad'], $b['llegada'], $b['idx']]);
                break;
            case 'SRTF':
                [$gantt, $fin, $ini] = self::expropiativo($procs, 'rafaga');
                break;
            case 'PRIO_EXP':
                [$gantt, $fin, $ini] = self::expropiativo($procs, 'prioridad');
                break;
            case 'RR':
                [$gantt, $fin, $ini] = self::roundRobin($procs, max(1, $quantum));
                break;
            default:
                abort(422, 'Algoritmo no válido');
        }

        $filas = [];
        $sumE = $sumR = $sumResp = $ocupado = 0;
        foreach ($procs as $p) {
            $f = $fin[$p['pid']];
            $retorno = $f - $p['llegada'];
            $espera = $retorno - $p['rafaga'];
            $resp = $ini[$p['pid']] - $p['llegada'];
            $filas[] = ['pid' => $p['pid'], 'llegada' => $p['llegada'], 'rafaga' => $p['rafaga'], 'prioridad' => $p['prioridad'],
                        'finalizacion' => $f, 'retorno' => $retorno, 'espera' => $espera, 'respuesta' => $resp];
            $sumE += $espera;
            $sumR += $retorno;
            $sumResp += $resp;
            $ocupado += $p['rafaga'];
        }
        $n = count($procs);
        $total = max($fin);   // el reloj arranca en 0

        return [
            'algoritmo' => $algoritmo,
            'quantum' => $algoritmo === 'RR' ? $quantum : null,
            'gantt' => $gantt,
            'procesos' => $filas,
            'espera_promedio' => round($sumE / $n, 2),
            'retorno_promedio' => round($sumR / $n, 2),
            'respuesta_promedio' => round($sumResp / $n, 2),
            'utilizacion' => round(100 * $ocupado / max(1, $total), 2),
            'duracion' => $total,
        ];
    }

    private static function segmento(array &$g, string $pid, int $ini, int $fin): void
    {
        if ($fin <= $ini) {
            return;
        }
        $k = count($g) - 1;
        if ($k >= 0 && $g[$k]['pid'] === $pid && $g[$k]['fin'] === $ini) {
            $g[$k]['fin'] = $fin;
        } else {
            $g[] = ['pid' => $pid, 'inicio' => $ini, 'fin' => $fin];
        }
    }

    // FCFS, SJF y Prioridades: se elige un proceso listo y se ejecuta completo
    private static function noExpropiativo(array $procs, callable $criterio): array
    {
        $t = 0;
        $gantt = [];
        $fin = [];
        $ini = [];
        $pend = $procs;
        while ($pend) {
            $listos = array_filter($pend, fn($p) => $p['llegada'] <= $t);
            if (!$listos) {
                $sig = min(array_column($pend, 'llegada'));
                self::segmento($gantt, 'IDLE', $t, $sig);
                $t = $sig;
                continue;
            }
            usort($listos, $criterio);
            $p = $listos[0];
            $ini[$p['pid']] = $t;
            self::segmento($gantt, $p['pid'], $t, $t + $p['rafaga']);
            $t += $p['rafaga'];
            $fin[$p['pid']] = $t;
            $pend = array_filter($pend, fn($x) => $x['pid'] !== $p['pid']);
        }
        return [$gantt, $fin, $ini];
    }

    // SRTF (clave 'rafaga' = tiempo restante) y Prioridades expropiativo (clave 'prioridad'), unidad por unidad
    private static function expropiativo(array $procs, string $clave): array
    {
        $rest = [];
        foreach ($procs as $p) {
            $rest[$p['pid']] = $p['rafaga'];
        }
        $t = 0;
        $gantt = [];
        $fin = [];
        $ini = [];
        $actual = null;
        $hechos = 0;
        while ($hechos < count($procs)) {
            $listos = array_values(array_filter($procs, fn($p) => $p['llegada'] <= $t && $rest[$p['pid']] > 0));
            if (!$listos) {
                $sig = min(array_map(fn($p) => $p['llegada'], array_filter($procs, fn($p) => $rest[$p['pid']] > 0)));
                self::segmento($gantt, 'IDLE', $t, $sig);
                $t = $sig;
                $actual = null;
                continue;
            }
            $valor = fn($p) => $clave === 'rafaga' ? $rest[$p['pid']] : $p['prioridad'];
            usort($listos, fn($a, $b) => [$valor($a), $a['llegada'], $a['idx']] <=> [$valor($b), $b['llegada'], $b['idx']]);
            $mejor = $listos[0];
            // si el actual empata con el mejor, no se le quita la CPU
            foreach ($listos as $p) {
                if ($actual === $p['pid'] && $valor($p) <= $valor($mejor)) {
                    $mejor = $p;
                    break;
                }
            }
            $actual = $mejor['pid'];
            $ini[$actual] ??= $t;
            self::segmento($gantt, $actual, $t, $t + 1);
            $rest[$actual]--;
            $t++;
            if ($rest[$actual] === 0) {
                $fin[$actual] = $t;
                $hechos++;
            }
        }
        return [$gantt, $fin, $ini];
    }

    private static function roundRobin(array $procs, int $q): array
    {
        $rest = [];
        foreach ($procs as $p) {
            $rest[$p['pid']] = $p['rafaga'];
        }
        $porLlegada = $procs;
        usort($porLlegada, fn($a, $b) => [$a['llegada'], $a['idx']] <=> [$b['llegada'], $b['idx']]);
        $t = 0;
        $gantt = [];
        $fin = [];
        $ini = [];
        $cola = [];
        $k = 0;   // siguiente proceso por llegar
        $n = count($procs);
        $encolar = function () use (&$cola, &$k, &$t, $porLlegada, $n) {
            while ($k < $n && $porLlegada[$k]['llegada'] <= $t) {
                $cola[] = $porLlegada[$k]['pid'];
                $k++;
            }
        };
        $hechos = 0;
        while ($hechos < $n) {
            $encolar();
            if (!$cola) {
                $sig = $porLlegada[$k]['llegada'];
                self::segmento($gantt, 'IDLE', $t, $sig);
                $t = $sig;
                continue;
            }
            $pid = array_shift($cola);
            $ini[$pid] ??= $t;
            $run = min($q, $rest[$pid]);
            self::segmento($gantt, $pid, $t, $t + $run);
            $t += $run;
            $rest[$pid] -= $run;
            $encolar();             // los que llegaron durante la ejecucion entran antes que el expulsado
            if ($rest[$pid] > 0) {
                $cola[] = $pid;
            } else {
                $fin[$pid] = $t;
                $hechos++;
            }
        }
        return [$gantt, $fin, $ini];
    }
}
