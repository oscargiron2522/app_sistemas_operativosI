<?php
// Lectura de datos reales desde /proc (solo funciona en Linux)

function procLeer(string $ruta): string { return (string) @file_get_contents($ruta); }

function procConst(): array {
    static $c = null;
    return $c ??= [
        'clk'  => (int) trim((string) @shell_exec('getconf CLK_TCK')) ?: 100,   // ticks de CPU por segundo
        'page' => (int) trim((string) @shell_exec('getconf PAGESIZE')) ?: 4096, // bytes por página de memoria
    ];
}

function procUsuario(int $uid): string {
    static $cache = [];
    return $cache[$uid] ??= ((function_exists('posix_getpwuid') ? (posix_getpwuid($uid) ?: []) : [])['name'] ?? (string) $uid);
}

/** M1: lee /proc/[pid]/stat, cmdline y el dueño de cada proceso. */
function procSnapshot(): array {
    $k = procConst();
    $salida = [];
    foreach (scandir('/proc') as $dir) {
        if (!ctype_digit($dir)) continue;
        $stat = @file_get_contents("/proc/$dir/stat");
        if ($stat === false) continue;               // el proceso murió mientras lo leíamos
        $ini = strpos($stat, '(');
        $fin = strrpos($stat, ')');                  // el nombre puede tener espacios o paréntesis
        $c = explode(' ', substr($stat, $fin + 2));  // c[0] = campo 3 del manual (estado)
        $cmd = trim(str_replace("\0", ' ', procLeer("/proc/$dir/cmdline")));
        $salida[(int) $dir] = [
            'pid'    => (int) $dir,
            'ppid'   => (int) $c[1],
            'user'   => procUsuario((int) @fileowner("/proc/$dir")),
            'estado' => $c[0],
            'nice'   => (int) $c[16],
            'ticks'  => (int) $c[11] + (int) $c[12], // utime + stime
            'mem'    => round((int) $c[21] * $k['page'] / 1048576, 1), // RSS en MB
            'cmd'    => $cmd !== '' ? $cmd : '[' . substr($stat, $ini + 1, $fin - $ini - 1) . ']',
        ];
    }
    return $salida;
}

/** M2: contadores acumulados de /proc/stat (total, inactivo). */
function procTiemposCpu(): array {
    $linea = explode("\n", procLeer('/proc/stat'))[0];
    $v = array_map('intval', array_slice(preg_split('/\s+/', trim($linea)), 1));
    return [array_sum(array_slice($v, 0, 8)), ($v[3] ?? 0) + ($v[4] ?? 0)];
}

/** Toma dos lecturas separadas por 0.3 s y calcula %CPU por proceso y %CPU total. */
function procMuestrear(): array {
    $k = procConst();
    $t0 = microtime(true);
    $a = procSnapshot(); $c1 = procTiemposCpu();
    usleep(300000);
    $b = procSnapshot(); $c2 = procTiemposCpu();
    $dt = max(microtime(true) - $t0, 0.001);
    $lanz = procLanzados();
    $lista = [];
    foreach ($b as $pid => $p) {
        // %CPU = (ticks usados / CLK) / segundos transcurridos * 100
        $p['cpu'] = isset($a[$pid]) ? round(($p['ticks'] - $a[$pid]['ticks']) / $k['clk'] / $dt * 100, 1) : 0.0;
        $p['lanzado'] = in_array($pid, $lanz, true);
        unset($p['ticks']);
        $lista[] = $p;
    }
    $dtc = $c2[0] - $c1[0];
    return ['procesos' => $lista, 'cpu_uso' => $dtc > 0 ? round((1 - ($c2[1] - $c1[1]) / $dtc) * 100, 1) : 0.0];
}

function procCpuInfo(): array {
    $info = procLeer('/proc/cpuinfo');
    preg_match('/model name\s*:\s*(.+)/', $info, $m);
    return [
        'modelo'  => $m[1] ?? 'desconocido',
        'nucleos' => preg_match_all('/^processor/m', $info),
        'uptime'  => (int) explode(' ', procLeer('/proc/uptime'))[0],
        'load'    => array_map('floatval', array_slice(explode(' ', procLeer('/proc/loadavg')), 0, 3)),
    ];
}

function procMemoria(): array {
    preg_match_all('/^(\w+):\s+(\d+)/m', procLeer('/proc/meminfo'), $m);
    $kb = array_combine($m[1], $m[2]);
    $mb = fn(string $k) => (int) round(($kb[$k] ?? 0) / 1024);
    return [
        'total' => $mb('MemTotal'), 'cache' => $mb('Cached'),
        'usada' => $mb('MemTotal') - $mb('MemAvailable'),
        'swap_total' => $mb('SwapTotal'), 'swap_usada' => $mb('SwapTotal') - $mb('SwapFree'),
    ];
}

// ---------- Procesos de prueba (solo estos se pueden controlar) ----------
function procArchivo(): string { return sys_get_temp_dir() . '/linuxcore_lanzados.json'; }

function procLanzados(): array {
    $ids = json_decode(procLeer(procArchivo()), true) ?: [];
    return array_values(array_filter($ids, fn($p) => file_exists("/proc/$p")));
}

function procLanzar(string $tipo): int {
    $cmd = $tipo === 'sleep' ? 'sleep 300' : 'sh -c "while :; do :; done"';
    $pid = (int) shell_exec("nohup $cmd > /dev/null 2>&1 & echo $!");
    file_put_contents(procArchivo(), json_encode(array_merge(procLanzados(), [$pid])));
    return $pid;
}

function procSenal(int $pid, string $senal): bool {
    $n = ['SIGTERM' => 15, 'SIGKILL' => 9, 'SIGSTOP' => 19, 'SIGCONT' => 18];
    return isset($n[$senal]) && in_array($pid, procLanzados(), true) && posix_kill($pid, $n[$senal]);
}

function procRenice(int $pid, int $nice): bool {
    if (!in_array($pid, procLanzados(), true) || $nice < -20 || $nice > 19) return false;
    exec('renice -n ' . $nice . ' -p ' . $pid . ' 2>&1', $o, $rc);
    return $rc === 0; // bajar el nice requiere root
}
