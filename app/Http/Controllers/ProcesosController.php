<?php

namespace App\Http\Controllers;

use App\Models\Bitacora;
use App\Models\ProcesoPrueba;
use Illuminate\Http\Request;

// M1 - Procesos: lee /proc/[pid]/stat, /proc/[pid]/status y /proc/[pid]/cmdline.
// Seguridad: nunca se concatena texto del usuario en un comando. Los PID se validan como enteros,
// las señales salen de una lista blanca y solo se actua sobre procesos de prueba lanzados por la app.
class ProcesosController extends Controller
{
    const HZ = 100; // CLK_TCK habitual en Linux

    // Lista blanca de señales permitidas (numeros de Linux)
    const SENALES = ['SIGTERM' => 15, 'SIGKILL' => 9, 'SIGSTOP' => 19, 'SIGCONT' => 18];

    public function index()
    {
        return view('procesos');
    }

    public function datos()
    {
        $lista = self::leerProcesos();
        $pruebas = ProcesoPrueba::pluck('inicio_proc', 'pid')->all();
        $resumen = ['R' => 0, 'S' => 0, 'D' => 0, 'Z' => 0, 'T' => 0, 'otros' => 0];

        foreach ($lista as &$p) {
            $p['prueba'] = isset($pruebas[$p['pid']]) && (string) $pruebas[$p['pid']] === (string) $p['inicio'];
            $e = $p['estado'] === 't' ? 'T' : $p['estado'];
            isset($resumen[$e]) ? $resumen[$e]++ : $resumen['otros']++;
        }
        unset($p);

        return response()->json(['procesos' => $lista, 'resumen' => $resumen, 'total' => count($lista)]);
    }

    // Top 5 por CPU (calculado con dos lecturas de ticks) y por memoria
    public function top()
    {
        $antes = self::ticksTodos();
        $t1 = microtime(true);
        usleep(400000);
        $lista = self::leerProcesos();
        $dt = max(0.05, microtime(true) - $t1);

        foreach ($lista as &$p) {
            $p['cpu'] = isset($antes[$p['pid']])
                ? round(max(0, $p['ticks'] - $antes[$p['pid']]) / (self::HZ * $dt) * 100, 1)
                : 0.0;
        }
        unset($p);

        $porCpu = $lista;
        usort($porCpu, fn($a, $b) => $b['cpu'] <=> $a['cpu']);
        $porMem = $lista;
        usort($porMem, fn($a, $b) => $b['mem_kb'] <=> $a['mem_kb']);

        $corto = fn($p) => ['pid' => $p['pid'], 'nombre' => $p['nombre'], 'usuario' => $p['usuario'],
                            'cpu' => $p['cpu'], 'mem_kb' => $p['mem_kb'], 'mem_pct' => $p['mem_pct']];
        return response()->json([
            'cpu' => array_map($corto, array_slice($porCpu, 0, 5)),
            'mem' => array_map($corto, array_slice($porMem, 0, 5)),
        ]);
    }

    // Detalle de /proc/PID/status (solo lectura, disponible para ambos roles)
    public function detalle(int $pid)
    {
        $txt = @file_get_contents("/proc/$pid/status");
        if ($txt === false) {
            return response()->json(['message' => 'El proceso ya no existe.'], 404);
        }
        $todo = [];
        foreach (explode("\n", $txt) as $l) {
            if (preg_match('/^(\w+):\s*(.*)$/', $l, $m)) {
                $todo[$m[1]] = mb_scrub($m[2]);
            }
        }
        $campos = ['Name', 'State', 'Pid', 'PPid', 'Uid', 'Gid', 'Threads', 'VmSize', 'VmRSS', 'VmSwap',
                   'voluntary_ctxt_switches', 'nonvoluntary_ctxt_switches'];
        $datos = [];
        foreach ($campos as $c) {
            if (isset($todo[$c])) {
                $datos[$c] = $todo[$c];
            }
        }
        $cmd = str_replace("\0", ' ', (string) @file_get_contents("/proc/$pid/cmdline"));
        $datos['Cmdline'] = mb_scrub(trim($cmd)) ?: '(vacío)';

        $p = self::leerProceso($pid);
        $reg = ProcesoPrueba::where('pid', $pid)->first();
        $esPrueba = $p && $reg && (string) $reg->inicio_proc === (string) $p['inicio'];

        return response()->json(['datos' => $datos, 'prueba' => $esPrueba]);
    }

    public function pruebas()
    {
        $salida = [];
        foreach (ProcesoPrueba::orderBy('id')->get() as $r) {
            $p = self::leerProceso((int) $r->pid);
            if (!$p || (string) $p['inicio'] !== (string) $r->inicio_proc) {
                $r->delete(); // ya termino
                continue;
            }
            $salida[] = ['pid' => $p['pid'], 'comando' => $r->comando, 'estado' => $p['estado'], 'nice' => $p['nice'],
                         'cpu' => $p['cpu'], 'creado' => $r->created_at->format('H:i:s')];
        }
        return response()->json(['pruebas' => $salida]);
    }

    public function lanzar(Request $request)
    {
        $this->exigirAdmin();
        $d = $request->validate([
            'tipo'     => ['required', 'in:sleep,carga'],
            'duracion' => ['nullable', 'integer', 'between:10,3600'],
        ]);

        if (count($this->pruebasVivas()) >= config('sysmonitor.max_pruebas', 10)) {
            return response()->json(['message' => 'Hay demasiados procesos de prueba activos. Termine alguno primero.'], 422);
        }

        // Comandos fijos: lo unico variable es un entero ya validado.
        if ($d['tipo'] === 'sleep') {
            $seg = (int) ($d['duracion'] ?? 300);
            $cmd = 'nohup sleep ' . $seg . ' >/dev/null 2>&1 & echo $!';
            $etiqueta = 'sleep ' . $seg;
        } else {
            $cmd = <<<'SH'
nohup sh -c 'i=0; while [ $i -lt 300000000 ]; do i=$((i+1)); done' >/dev/null 2>&1 & echo $!
SH;
            $etiqueta = 'script de carga de CPU';
        }

        $salida = [];
        exec($cmd, $salida);
        $pid = (int) trim($salida[0] ?? '0');

        $info = $pid > 1 ? self::leerStatBasico($pid) : null;
        if (!$info) {
            usleep(80000);
            $info = $pid > 1 ? self::leerStatBasico($pid) : null;
        }
        if (!$info) {
            Bitacora::registrar('Lanzar proceso de prueba', $etiqueta, 'Error al iniciar');
            return response()->json(['message' => 'No se pudo iniciar el proceso de prueba.'], 500);
        }

        ProcesoPrueba::create([
            'pid' => $pid, 'inicio_proc' => (string) $info['inicio'], 'comando' => $etiqueta, 'user_id' => auth()->id(),
        ]);
        Bitacora::registrar('Lanzar proceso de prueba', "PID $pid ($etiqueta)", 'Iniciado');

        return response()->json(['ok' => true, 'mensaje' => "Proceso de prueba iniciado (PID $pid)."]);
    }

    public function senal(Request $request)
    {
        $this->exigirAdmin();
        $d = $request->validate([
            'pid'   => ['required', 'integer', 'min:2'],
            'senal' => ['required', 'in:' . implode(',', array_keys(self::SENALES))],
        ]);
        $pid = (int) $d['pid'];
        $reg = $this->verificarPrueba($pid);
        $objetivo = "PID $pid ({$reg->comando})";

        if (!function_exists('posix_kill')) {
            return response()->json(['message' => 'Falta la extensión PHP posix (php-posix).'], 500);
        }
        $ok = posix_kill($pid, self::SENALES[$d['senal']]);
        if (!$ok) {
            $err = posix_strerror(posix_get_last_error());
            Bitacora::registrar('Señal ' . $d['senal'], $objetivo, 'Error: ' . $err);
            return response()->json(['message' => 'No se pudo enviar la señal: ' . $err], 500);
        }

        Bitacora::registrar('Señal ' . $d['senal'], $objetivo, 'Enviada correctamente');
        return response()->json(['ok' => true, 'mensaje' => $d['senal'] . ' enviada al PID ' . $pid . '.']);
    }

    public function renice(Request $request)
    {
        $this->exigirAdmin();
        $d = $request->validate([
            'pid'  => ['required', 'integer', 'min:2'],
            'nice' => ['required', 'integer', 'between:0,19'],
        ]);
        $pid = (int) $d['pid'];
        $nice = (int) $d['nice'];
        $reg = $this->verificarPrueba($pid);
        $objetivo = "PID $pid ({$reg->comando})";

        $bin = null;
        foreach (['/usr/bin/renice', '/bin/renice'] as $c) {
            if (is_executable($c)) {
                $bin = $c;
                break;
            }
        }
        if (!$bin) {
            return response()->json(['message' => 'No se encontró el comando renice.'], 500);
        }

        // Sin shell: los argumentos viajan como arreglo
        $r = self::ejecutar([$bin, '-n', (string) $nice, '-p', (string) $pid]);
        if ($r['codigo'] !== 0) {
            $msg = trim($r['error'] ?: $r['salida']) ?: 'renice falló';
            Bitacora::registrar("renice a $nice", $objetivo, 'Error: ' . mb_substr($msg, 0, 120));
            return response()->json(['message' => 'renice falló: ' . $msg . ' (sin privilegios solo se puede aumentar el valor nice).'], 422);
        }

        Bitacora::registrar("renice a $nice", $objetivo, 'Aplicado correctamente');
        return response()->json(['ok' => true, 'mensaje' => "Prioridad (nice) del PID $pid cambiada a $nice."]);
    }

    // ------------------------------------------------------------------ utilidades

    private function exigirAdmin(): void
    {
        if (!auth()->user()?->esAdmin()) {
            Bitacora::registrar('Acción denegada', request()->path(), 'El rol Observador no puede administrar');
            abort(403, 'Solo el rol Administrador puede realizar esta acción.');
        }
    }

    // Solo se permite actuar sobre procesos de prueba creados por la app y que sigan siendo los mismos
    private function verificarPrueba(int $pid): ProcesoPrueba
    {
        $reg = ProcesoPrueba::where('pid', $pid)->first();
        if (!$reg) {
            Bitacora::registrar('Acción denegada', "PID $pid", 'No es un proceso de prueba de la aplicación');
            abort(403, 'Solo se pueden controlar procesos de prueba lanzados por la aplicación.');
        }
        $info = self::leerStatBasico($pid);
        if (!$info || (string) $info['inicio'] !== (string) $reg->inicio_proc) {
            $reg->delete();
            abort(410, 'El proceso ya no existe.');
        }
        if (function_exists('posix_geteuid') && @fileowner("/proc/$pid") !== posix_geteuid()) {
            abort(403, 'El proceso no pertenece al usuario de la aplicación.');
        }
        return $reg;
    }

    private function pruebasVivas(): array
    {
        $vivos = [];
        foreach (ProcesoPrueba::all() as $r) {
            $info = self::leerStatBasico((int) $r->pid);
            if ($info && (string) $info['inicio'] === (string) $r->inicio_proc) {
                $vivos[] = $r;
            } else {
                $r->delete();
            }
        }
        return $vivos;
    }

    // Ejecuta un comando SIN shell (proc_open con arreglo)
    private static function ejecutar(array $argv): array
    {
        $proc = @proc_open($argv, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, null,
                           ['LC_ALL' => 'C', 'PATH' => '/usr/sbin:/usr/bin:/sbin:/bin']);
        if (!is_resource($proc)) {
            return ['codigo' => -1, 'salida' => '', 'error' => 'No se pudo ejecutar el comando'];
        }
        $salida = stream_get_contents($pipes[1]);
        $error = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        return ['codigo' => proc_close($proc), 'salida' => (string) $salida, 'error' => (string) $error];
    }

    // ------------------------------------------------------------------ lectura de /proc

    // Parsea /proc/PID/stat. El nombre (comm) va entre parentesis y puede contener espacios.
    public static function leerStatBasico(int $pid): ?array
    {
        $stat = @file_get_contents("/proc/$pid/stat");
        if ($stat === false || $stat === '') {
            return null;
        }
        $ini = strpos($stat, '(');
        $fin = strrpos($stat, ')');
        if ($ini === false || $fin === false) {
            return null;
        }
        $r = preg_split('/\s+/', trim(substr($stat, $fin + 2)));
        if (count($r) < 20) {
            return null;
        }
        // campo N del manual proc(5) = indice N-3 de $r
        return [
            'nombre' => mb_scrub(substr($stat, $ini + 1, $fin - $ini - 1)),
            'estado' => $r[0],
            'ppid'   => (int) $r[1],
            'ticks'  => (int) $r[11] + (int) $r[12],  // utime + stime
            'nice'   => (int) $r[16],
            'hilos'  => (int) $r[17],
            'inicio' => (int) $r[19],                 // starttime en ticks desde el arranque
        ];
    }

    public static function contexto(): array
    {
        $usuarios = [];
        foreach (@file('/etc/passwd', FILE_IGNORE_NEW_LINES) ?: [] as $l) {
            $c = explode(':', $l);
            if (count($c) > 2) {
                $usuarios[(int) $c[2]] = $c[0];
            }
        }
        $memTotal = 1;
        if (preg_match('/^MemTotal:\s+(\d+)/m', (string) @file_get_contents('/proc/meminfo'), $m)) {
            $memTotal = (int) $m[1];
        }
        return [
            'up'       => (float) explode(' ', (string) @file_get_contents('/proc/uptime'))[0],
            'usuarios' => $usuarios,
            'memTotal' => max(1, $memTotal),
        ];
    }

    public static function leerProceso(int $pid, ?array $ctx = null): ?array
    {
        $ctx ??= self::contexto();
        $s = self::leerStatBasico($pid);
        if (!$s) {
            return null;
        }
        $status = (string) @file_get_contents("/proc/$pid/status");
        $uid = 0;
        if (preg_match('/^Uid:\s+(\d+)/m', $status, $m)) {
            $uid = (int) $m[1];
        }
        $rss = 0;
        if (preg_match('/^VmRSS:\s+(\d+)/m', $status, $m)) {
            $rss = (int) $m[1];
        }
        $cmd = trim(str_replace("\0", ' ', (string) @file_get_contents("/proc/$pid/cmdline")));
        $cmd = $cmd !== '' ? mb_scrub(mb_substr($cmd, 0, 200)) : '[' . $s['nombre'] . ']';

        $transcurrido = max(0.01, $ctx['up'] - $s['inicio'] / self::HZ);   // % CPU acumulado, como ps
        $cpu = round(($s['ticks'] / self::HZ) / $transcurrido * 100, 1);

        return [
            'pid'     => $pid,
            'ppid'    => $s['ppid'],
            'usuario' => $ctx['usuarios'][$uid] ?? (string) $uid,
            'estado'  => $s['estado'],
            'nice'    => $s['nice'],
            'cpu'     => $cpu,
            'mem_kb'  => $rss,
            'mem_pct' => round($rss / $ctx['memTotal'] * 100, 1),
            'hilos'   => $s['hilos'],
            'nombre'  => $s['nombre'],
            'comando' => $cmd,
            'inicio'  => $s['inicio'],
            'ticks'   => $s['ticks'],
        ];
    }

    public static function leerProcesos(): array
    {
        $ctx = self::contexto();
        $lista = [];
        foreach (@scandir('/proc') ?: [] as $d) {
            if (ctype_digit($d) && ($p = self::leerProceso((int) $d, $ctx))) {
                $lista[] = $p;
            }
        }
        return $lista;
    }

    public static function ticksTodos(): array
    {
        $r = [];
        foreach (@scandir('/proc') ?: [] as $d) {
            if (ctype_digit($d) && ($s = self::leerStatBasico((int) $d))) {
                $r[(int) $d] = $s['ticks'];
            }
        }
        return $r;
    }
}
