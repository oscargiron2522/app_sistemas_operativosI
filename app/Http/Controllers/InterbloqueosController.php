<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

// M4 - Interbloqueos (simulado): algoritmo del banquero y deteccion con grafo de asignacion de recursos
class InterbloqueosController extends Controller
{
    public function index()
    {
        return view('interbloqueos');
    }

    // ---------------------------------------------------------------- Banquero
    public function banquero(Request $request)
    {
        $asig = self::matriz($request->input('asignacion'), 'Asignación');
        $max = self::matriz($request->input('maximo'), 'Máximo');
        $disp = self::vector($request->input('disponible'), 'Disponible');
        $n = count($asig);
        $m = count($disp);
        self::exigirDimension($asig, $n, $m, 'Asignación');
        self::exigirDimension($max, $n, $m, 'Máximo');

        $nec = [];
        for ($i = 0; $i < $n; $i++) {
            for ($j = 0; $j < $m; $j++) {
                if ($max[$i][$j] < $asig[$i][$j]) {
                    abort(422, 'Datos inválidos: la asignación de P' . $i . ' supera su máximo en el recurso ' . chr(65 + $j) . '.');
                }
                $nec[$i][$j] = $max[$i][$j] - $asig[$i][$j];
            }
        }

        $res = ['necesidad' => $nec, 'estado' => self::seguridad($asig, $nec, $disp)];

        // Evaluacion opcional de una solicitud
        $sol = $request->input('solicitud');
        if (is_array($sol) && isset($sol['proceso'], $sol['recursos'])) {
            $i = (int) $sol['proceso'];
            $req = self::vector($sol['recursos'], 'Solicitud');
            if ($i < 0 || $i >= $n || count($req) !== $m) {
                abort(422, 'Solicitud inválida.');
            }
            $res['solicitud'] = self::evaluarSolicitud($i, $req, $asig, $nec, $disp);
        }
        return response()->json($res);
    }

    // Algoritmo de seguridad con registro del paso a paso
    public static function seguridad(array $asig, array $nec, array $disp): array
    {
        $n = count($asig);
        $trabajo = $disp;
        $fin = array_fill(0, $n, false);
        $sec = [];
        $pasos = [];
        $fmt = fn($v) => '[' . implode(', ', $v) . ']';

        $pasos[] = 'Trabajo (Work) inicial = Disponible = ' . $fmt($disp) . '. Finish = [' . implode(', ', array_fill(0, $n, 'F')) . ']';

        while (count($sec) < $n) {
            $hallado = false;
            for ($i = 0; $i < $n; $i++) {
                if ($fin[$i]) {
                    continue;
                }
                if (self::menorIgual($nec[$i], $trabajo)) {
                    $antes = $trabajo;
                    foreach ($trabajo as $j => $_) {
                        $trabajo[$j] += $asig[$i][$j];
                    }
                    $fin[$i] = true;
                    $sec[] = 'P' . $i;
                    $pasos[] = 'P' . $i . ': Necesidad ' . $fmt($nec[$i]) . ' ≤ Trabajo ' . $fmt($antes)
                        . ' → puede terminar y libera su asignación ' . $fmt($asig[$i])
                        . '. Trabajo = ' . $fmt($antes) . ' + ' . $fmt($asig[$i]) . ' = ' . $fmt($trabajo);
                    $hallado = true;
                    break;
                }
            }
            if (!$hallado) {
                break;
            }
        }

        $bloq = [];
        foreach ($fin as $i => $f) {
            if (!$f) {
                $bloq[] = 'P' . $i;
            }
        }
        if ($bloq) {
            $pasos[] = 'Ningún proceso restante (' . implode(', ', $bloq) . ') tiene Necesidad ≤ Trabajo ' . $fmt($trabajo) . '. El estado es INSEGURO.';
        } else {
            $pasos[] = 'Todos los procesos pueden terminar. El estado es SEGURO.';
        }

        return ['seguro' => !$bloq, 'secuencia' => $sec, 'pasos' => $pasos, 'bloqueados' => $bloq];
    }

    public static function evaluarSolicitud(int $i, array $req, array $asig, array $nec, array $disp): array
    {
        $fmt = fn($v) => '[' . implode(', ', $v) . ']';
        $pasos = [];
        $pasos[] = "Solicitud de P$i = " . $fmt($req);

        if (!self::menorIgual($req, $nec[$i])) {
            $pasos[] = 'Paso 1: Solicitud ≤ Necesidad? NO. El proceso excede su máximo declarado.';
            return ['concedida' => false, 'motivo' => 'La solicitud excede la necesidad máxima declarada del proceso (error).', 'pasos' => $pasos];
        }
        $pasos[] = 'Paso 1: Solicitud ≤ Necesidad ' . $fmt($nec[$i]) . '? SÍ.';

        if (!self::menorIgual($req, $disp)) {
            $pasos[] = 'Paso 2: Solicitud ≤ Disponible ' . $fmt($disp) . '? NO.';
            return ['concedida' => false, 'motivo' => 'No hay recursos disponibles suficientes: el proceso debe esperar.', 'pasos' => $pasos];
        }
        $pasos[] = 'Paso 2: Solicitud ≤ Disponible ' . $fmt($disp) . '? SÍ.';

        $d2 = $disp;
        $a2 = $asig;
        $n2 = $nec;
        foreach ($req as $j => $v) {
            $d2[$j] -= $v;
            $a2[$i][$j] += $v;
            $n2[$i][$j] -= $v;
        }
        $pasos[] = 'Paso 3: Asignación hipotética → Disponible = ' . $fmt($d2) . ', Asignación P' . $i . ' = ' . $fmt($a2[$i]) . ', Necesidad P' . $i . ' = ' . $fmt($n2[$i]) . '.';
        $seg = self::seguridad($a2, $n2, $d2);
        foreach ($seg['pasos'] as $p) {
            $pasos[] = '    ' . $p;
        }

        return $seg['seguro']
            ? ['concedida' => true, 'motivo' => 'El estado resultante es seguro: la solicitud SE CONCEDE.', 'secuencia' => $seg['secuencia'], 'pasos' => $pasos]
            : ['concedida' => false, 'motivo' => 'El estado resultante sería inseguro: la solicitud NO se concede.', 'pasos' => $pasos];
    }

    // ---------------------------------------------------------------- Deteccion
    public function deteccion(Request $request)
    {
        $asig = self::matriz($request->input('asignacion'), 'Asignación');
        $sol = self::matriz($request->input('solicitud'), 'Solicitud');
        $disp = self::vector($request->input('disponible'), 'Disponible');
        $n = count($asig);
        $m = count($disp);
        self::exigirDimension($asig, $n, $m, 'Asignación');
        self::exigirDimension($sol, $n, $m, 'Solicitud');
        return response()->json(self::analizar($asig, $sol, $disp));
    }

    // Devuelve los procesos bloqueados (deadlock) con el algoritmo de deteccion de matrices
    public static function detectar(array $asig, array $sol, array $disp, ?array &$pasos = null): array
    {
        $n = count($asig);
        $fmt = fn($v) => '[' . implode(', ', $v) . ']';
        $trabajo = $disp;
        $fin = [];
        for ($i = 0; $i < $n; $i++) {
            $fin[$i] = array_sum($asig[$i]) === 0;   // sin recursos asignados: no puede estar bloqueando a nadie
        }
        $pasos = ['Trabajo inicial = Disponible = ' . $fmt($disp) . '. Los procesos sin recursos asignados se marcan como terminados.'];
        do {
            $avance = false;
            for ($i = 0; $i < $n; $i++) {
                if (!$fin[$i] && self::menorIgual($sol[$i], $trabajo)) {
                    foreach ($trabajo as $j => $_) {
                        $trabajo[$j] += $asig[$i][$j];
                    }
                    $fin[$i] = true;
                    $avance = true;
                    $pasos[] = "P$i: Solicitud " . $fmt($sol[$i]) . ' ≤ Trabajo → puede terminar. Trabajo = ' . $fmt($trabajo);
                }
            }
        } while ($avance);
        $bloq = [];
        foreach ($fin as $i => $f) {
            if (!$f) {
                $bloq[] = $i;
            }
        }
        $pasos[] = $bloq ? 'Procesos bloqueados: ' . implode(', ', array_map(fn($i) => "P$i", $bloq)) : 'Todos los procesos pueden terminar: no hay interbloqueo.';
        return $bloq;
    }

    public static function analizar(array $asig, array $sol, array $disp): array
    {
        $n = count($asig);
        $m = count($disp);

        // Nodos y aristas del grafo (asignacion R->P, solicitud P->R)
        $nodos = [];
        for ($i = 0; $i < $n; $i++) {
            $nodos[] = ['id' => "P$i", 'tipo' => 'proceso'];
        }
        for ($j = 0; $j < $m; $j++) {
            $total = $disp[$j];
            for ($i = 0; $i < $n; $i++) {
                $total += $asig[$i][$j];
            }
            $nodos[] = ['id' => 'R' . chr(65 + $j), 'tipo' => 'recurso', 'instancias' => $total];
        }
        $aristas = [];
        $ady = [];
        for ($i = 0; $i < $n; $i++) {
            for ($j = 0; $j < $m; $j++) {
                $r = 'R' . chr(65 + $j);
                if ($asig[$i][$j] > 0) {
                    $aristas[] = ['de' => $r, 'a' => "P$i", 'n' => $asig[$i][$j], 'tipo' => 'asignacion'];
                    $ady[$r][] = "P$i";
                }
                if ($sol[$i][$j] > 0) {
                    $aristas[] = ['de' => "P$i", 'a' => $r, 'n' => $sol[$i][$j], 'tipo' => 'solicitud'];
                    $ady["P$i"][] = $r;
                }
            }
        }

        $ciclo = self::buscarCiclo(array_column($nodos, 'id'), $ady);

        $pasos = [];
        $bloq = self::detectar($asig, $sol, $disp, $pasos);

        $res = [
            'nodos' => $nodos, 'aristas' => $aristas, 'ciclo' => $ciclo, 'pasos' => $pasos,
            'interbloqueo' => (bool) $bloq,
            'bloqueados' => array_map(fn($i) => "P$i", $bloq),
            'recuperacion' => ['terminacion' => [], 'apropiacion' => []],
        ];
        if (!$bloq) {
            $res['conclusion'] = $ciclo
                ? 'Hay un ciclo en el grafo, pero como los recursos tienen varias instancias no hay interbloqueo: algún proceso puede terminar y liberar recursos.'
                : 'No hay ciclos ni interbloqueo.';
            return $res;
        }
        $res['conclusion'] = 'INTERBLOQUEO detectado entre: ' . implode(', ', $res['bloqueados']) . '.';
        $res['recuperacion'] = self::recuperacion($asig, $sol, $disp, $bloq);
        return $res;
    }

    // DFS que devuelve un ciclo como lista de nodos (el primero se repite al final) o null
    public static function buscarCiclo(array $ids, array $ady): ?array
    {
        $color = array_fill_keys($ids, 0);
        $pila = [];
        $dfs = function ($u) use (&$dfs, &$color, &$pila, $ady) {
            $color[$u] = 1;
            $pila[] = $u;
            foreach ($ady[$u] ?? [] as $v) {
                if ($color[$v] === 1) {
                    $pos = array_search($v, $pila, true);
                    return array_merge(array_slice($pila, $pos), [$v]);
                }
                if ($color[$v] === 0 && ($c = $dfs($v))) {
                    return $c;
                }
            }
            array_pop($pila);
            $color[$u] = 2;
            return null;
        };
        foreach ($ids as $u) {
            if ($color[$u] === 0 && ($c = $dfs($u))) {
                return $c;
            }
        }
        return null;
    }

    private static function recuperacion(array $asig, array $sol, array $disp, array $bloq): array
    {
        $fmt = fn($v) => '[' . implode(', ', $v) . ']';

        // 1) Terminacion: se elimina de a un proceso (el de menor costo = menos recursos asignados)
        $terminacion = [];
        $a = $asig;
        $s = $sol;
        $d = $disp;
        $actual = $bloq;
        $guard = 0;
        while ($actual && $guard++ < 50) {
            usort($actual, fn($x, $y) => [array_sum($a[$x]), $x] <=> [array_sum($a[$y]), $y]);
            $v = $actual[0];
            $libera = $a[$v];
            foreach ($d as $j => $_) {
                $d[$j] += $a[$v][$j];
                $a[$v][$j] = 0;
                $s[$v][$j] = 0;
            }
            $terminacion[] = "Terminar P$v (es el de menor costo: tiene " . array_sum($libera) . ' unidades asignadas) y liberar ' . $fmt($libera) . '. Disponible = ' . $fmt($d) . '.';
            $actual = self::detectar($a, $s, $d);
        }
        $terminacion[] = $terminacion ? 'Con esto el sistema sale del interbloqueo; los demás procesos pueden continuar.' : '';
        $terminacion = array_values(array_filter($terminacion));

        // 2) Apropiacion: quitar recursos a un proceso bloqueado y dárselos a otro, con rollback de la víctima
        $apropiacion = [];
        $vistos = [];
        foreach ($bloq as $w) {
            foreach ($disp as $j => $dj) {
                $falta = $sol[$w][$j] - $dj;
                if ($falta <= 0) {
                    continue;
                }
                $mejor = null;
                foreach ($bloq as $v) {
                    if ($v !== $w && $asig[$v][$j] > 0 && ($mejor === null || array_sum($asig[$v]) < array_sum($asig[$mejor]))) {
                        $mejor = $v;
                    }
                }
                if ($mejor === null || isset($vistos["$mejor-$j-$w"])) {
                    continue;
                }
                $vistos["$mejor-$j-$w"] = true;
                $q = min($asig[$mejor][$j], $falta);
                $apropiacion[] = "Apropiar $q unidad(es) del recurso " . chr(65 + $j) . " a P$mejor (víctima, menor costo) y asignarlas a P$w. P$mejor debe retroceder (rollback) a un estado seguro anterior y volver a solicitarlas después. Conviene limitar cuántas veces se elige la misma víctima para evitar inanición.";
            }
        }
        if (!$apropiacion) {
            $apropiacion[] = 'No se encontró una apropiación directa; use la estrategia de terminación.';
        }

        return ['terminacion' => $terminacion, 'apropiacion' => $apropiacion];
    }

    // ---------------------------------------------------------------- validacion
    private static function menorIgual(array $a, array $b): bool
    {
        foreach ($a as $j => $v) {
            if ($v > $b[$j]) {
                return false;
            }
        }
        return true;
    }

    private static function vector($v, string $nombre): array
    {
        if (!is_array($v) || !$v || count($v) > 10) {
            abort(422, "El vector $nombre es inválido (1 a 10 recursos).");
        }
        $r = [];
        foreach ($v as $x) {
            if (!is_numeric($x) || (int) $x != $x || $x < 0 || $x > 1000) {
                abort(422, "Todos los valores de $nombre deben ser enteros entre 0 y 1000.");
            }
            $r[] = (int) $x;
        }
        return $r;
    }

    private static function matriz($mx, string $nombre): array
    {
        if (!is_array($mx) || !$mx || count($mx) > 10) {
            abort(422, "La matriz $nombre es inválida (1 a 10 procesos).");
        }
        return array_map(fn($fila) => self::vector($fila, $nombre), array_values($mx));
    }

    private static function exigirDimension(array $mx, int $n, int $m, string $nombre): void
    {
        if (count($mx) !== $n) {
            abort(422, "La matriz $nombre debe tener el mismo número de procesos.");
        }
        foreach ($mx as $fila) {
            if (count($fila) !== $m) {
                abort(422, "La matriz $nombre debe tener $m columnas (recursos).");
            }
        }
    }
}
