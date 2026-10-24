<?php

namespace App\Http\Controllers;

// M2 - CPU y Memoria: lee /proc/cpuinfo, /proc/uptime, /proc/stat, /proc/loadavg y /proc/meminfo
class CpuMemoriaController extends Controller
{
    public function index()
    {
        return view('cpu', ['cpu' => self::infoCpu()]);
    }

    public function datos()
    {
        return response()->json(self::metricas());
    }

    // ---- Lecturas de /proc (estaticas para poder probarlas sin Laravel) ----

    public static function infoCpu(): array
    {
        $txt = @file_get_contents('/proc/cpuinfo') ?: '';
        $modelo = null;
        if (preg_match('/^model name\s*:\s*(.+)$/mi', $txt, $m)) {
            $modelo = trim($m[1]);
        } elseif (preg_match('/^Hardware\s*:\s*(.+)$/mi', $txt, $m)) {
            $modelo = trim($m[1]);
        } elseif (preg_match('/^Model\s*:\s*(.+)$/mi', $txt, $m)) {
            $modelo = trim($m[1]);
        }
        $nucleos = preg_match_all('/^processor\s*:/mi', $txt) ?: 1;
        $mhz = null;
        if (preg_match_all('/^cpu MHz\s*:\s*([\d.]+)/mi', $txt, $m) && count($m[1])) {
            $mhz = array_sum($m[1]) / count($m[1]);
        }
        return [
            'modelo'  => $modelo ?: php_uname('m'),
            'nucleos' => $nucleos,
            'mhz'     => $mhz,
        ];
    }

    // Primera linea de /proc/stat: [total, inactivo]
    public static function leerStat(): array
    {
        $linea = '';
        $f = @fopen('/proc/stat', 'r');
        if ($f) {
            $linea = (string) fgets($f);
            fclose($f);
        }
        $v = array_map('intval', preg_split('/\s+/', trim(substr($linea, 3))) ?: []);
        $v = array_pad($v, 8, 0);
        $idle = $v[3] + $v[4];                        // idle + iowait
        $total = array_sum(array_slice($v, 0, 8));    // user nice system idle iowait irq softirq steal
        return [$total, $idle];
    }

    public static function usoCpu(int $pausaMicro = 300000): float
    {
        [$t1, $i1] = self::leerStat();
        usleep($pausaMicro);
        [$t2, $i2] = self::leerStat();
        $dt = $t2 - $t1;
        if ($dt <= 0) {
            return 0.0;
        }
        return round(100 * (1 - ($i2 - $i1) / $dt), 1);
    }

    public static function leerMeminfo(): array
    {
        $r = [];
        foreach (@file('/proc/meminfo', FILE_IGNORE_NEW_LINES) ?: [] as $l) {
            if (preg_match('/^(\w+)(?:\(\w+\))?:\s+(\d+)/', $l, $m)) {
                $r[$m[1]] = (int) $m[2];   // en kB
            }
        }
        return $r;
    }

    public static function metricas(): array
    {
        $cpu = self::usoCpu();

        $mem = self::leerMeminfo();
        $total = $mem['MemTotal'] ?? 0;
        $libre = $mem['MemFree'] ?? 0;
        $cache = ($mem['Buffers'] ?? 0) + ($mem['Cached'] ?? 0) + ($mem['SReclaimable'] ?? 0);
        $usada = max(0, $total - $libre - $cache);
        $swapTotal = $mem['SwapTotal'] ?? 0;
        $swapUsada = max(0, $swapTotal - ($mem['SwapFree'] ?? 0));

        $la = preg_split('/\s+/', trim((string) @file_get_contents('/proc/loadavg')));
        $ejecutando = isset($la[3]) ? (int) explode('/', $la[3])[0] : 0;

        $up = (float) (explode(' ', (string) @file_get_contents('/proc/uptime'))[0] ?? 0);

        $procs = 0;
        foreach (@scandir('/proc') ?: [] as $d) {
            if (ctype_digit($d)) {
                $procs++;
            }
        }

        return [
            'cpu'        => $cpu,
            'carga'      => [(float) ($la[0] ?? 0), (float) ($la[1] ?? 0), (float) ($la[2] ?? 0)],
            'memoria'    => [
                'total' => $total, 'usada' => $usada, 'cache' => $cache, 'libre' => $libre,
                'disponible' => $mem['MemAvailable'] ?? ($libre + $cache),
                'swap_total' => $swapTotal, 'swap_usada' => $swapUsada,
            ],
            'uptime'     => $up,
            'encendido'  => date('Y-m-d H:i', (int) (time() - $up)),
            'procesos'   => $procs,
            'ejecutando' => $ejecutando,
        ];
    }
}
