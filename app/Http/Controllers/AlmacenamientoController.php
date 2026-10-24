<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

// M5 - Almacenamiento: lsblk, df, findmnt, /etc/fstab, /proc/swaps y explorador de solo lectura.
// Los comandos salen de una lista blanca fija (sin datos del usuario) y se ejecutan sin shell.
class AlmacenamientoController extends Controller
{
    const COMANDOS = [
        'lsblk'   => ['lsblk', '-b', '-J', '-o', 'NAME,TYPE,SIZE,FSTYPE,MOUNTPOINT,PTTYPE,MODEL,RO'],
        'df'      => ['df', '-PT', '-B1'],
        'findmnt' => ['findmnt', '-J', '-o', 'TARGET,SOURCE,FSTYPE,OPTIONS'],
    ];

    public function index()
    {
        $errores = [];

        // ---- df ----
        $df = [];
        $r = self::comando('df');
        if ($r['codigo'] === 0) {
            foreach (array_slice(explode("\n", trim($r['salida'])), 1) as $l) {
                $c = preg_split('/\s+/', trim($l), 7);
                if (count($c) === 7 && str_starts_with($c[0], '/dev/') && !str_starts_with($c[0], '/dev/loop')) {
                    $df[] = ['fuente' => $c[0], 'tipo' => $c[1], 'total' => (int) $c[2], 'usado' => (int) $c[3],
                             'libre' => (int) $c[4], 'pct' => (int) rtrim($c[5], '%'), 'montaje' => $c[6]];
                }
            }
        } else {
            $errores[] = 'No se pudo ejecutar df.';
        }
        $dfPorFuente = [];
        foreach ($df as $f) {
            $dfPorFuente[$f['fuente']] ??= $f;
        }

        // ---- swap ----
        $swaps = [];
        foreach (array_slice(@file('/proc/swaps', FILE_IGNORE_NEW_LINES) ?: [], 1) as $l) {
            $c = preg_split('/\s+/', trim($l));
            if (count($c) >= 4) {
                $swaps[$c[0]] = ['total' => (int) $c[2] * 1024, 'usado' => (int) $c[3] * 1024];
            }
        }

        // ---- lsblk ----
        $discos = [];
        $particiones = [];
        $r = self::comando('lsblk');
        $json = $r['codigo'] === 0 ? json_decode($r['salida'], true) : null;
        if (!$json) {
            $errores[] = 'No se pudo ejecutar lsblk.';
        } else {
            $recorrer = function ($nodo, $esRaiz) use (&$recorrer, &$discos, &$particiones, $dfPorFuente, $swaps) {
                $tipo = $nodo['type'] ?? '';
                $hijos = $nodo['children'] ?? [];
                if ($esRaiz && $tipo === 'disk' && (int) $nodo['size'] > 0) {
                    $discos[] = [
                        'nombre' => '/dev/' . $nodo['name'], 'modelo' => trim((string) ($nodo['model'] ?? '')) ?: '—',
                        'tam' => (int) $nodo['size'],
                        'tabla' => match ($nodo['pttype'] ?? null) { 'gpt' => 'GPT', 'dos' => 'MBR', null => '—', default => strtoupper($nodo['pttype']) },
                        'ro' => filter_var($nodo['ro'] ?? false, FILTER_VALIDATE_BOOLEAN),
                        'particiones' => count($hijos),
                    ];
                }
                if (!in_array($tipo, ['loop', 'rom']) && ($tipo !== 'disk' || (!$hijos && (!empty($nodo['fstype']) || !empty($nodo['mountpoint']))))) {
                    $dev = '/dev/' . $nodo['name'];
                    $dfi = $dfPorFuente[$dev] ?? $dfPorFuente['/dev/mapper/' . $nodo['name']] ?? null;
                    $sw = $swaps[$dev] ?? $swaps['/dev/mapper/' . $nodo['name']] ?? null;
                    $total = (int) $nodo['size'];
                    $usado = $dfi['usado'] ?? ($sw['usado'] ?? null);
                    $particiones[] = [
                        'dispositivo' => $dev, 'montaje' => $nodo['mountpoint'] ?? '—', 'fs' => $nodo['fstype'] ?? ($dfi['tipo'] ?? '—'),
                        'tam' => $total, 'usado' => $usado, 'libre' => $usado === null ? null : max(0, ($dfi['total'] ?? $total) - $usado),
                        'pct' => $dfi ? $dfi['pct'] : ($sw && $sw['total'] ? (int) round(100 * $sw['usado'] / $sw['total']) : null),
                    ];
                }
                foreach ($hijos as $h) {
                    $recorrer($h, false);
                }
            };
            foreach ($json['blockdevices'] ?? [] as $d) {
                $recorrer($d, true);
            }
        }

        // ---- resumen (una vez por dispositivo) ----
        $totalFs = array_sum(array_column($dfPorFuente, 'total'));
        $usadoFs = array_sum(array_column($dfPorFuente, 'usado'));
        $libreFs = array_sum(array_column($dfPorFuente, 'libre'));
        $resumen = [
            'discos'   => array_sum(array_column($discos, 'tam')),
            'usado'    => $usadoFs, 'libre' => $libreFs,
            'pct'      => ($usadoFs + $libreFs) > 0 ? (int) round(100 * $usadoFs / ($usadoFs + $libreFs)) : 0,
            'pctLibre' => ($usadoFs + $libreFs) > 0 ? 100 - (int) round(100 * $usadoFs / ($usadoFs + $libreFs)) : 0,
            'particiones' => count($particiones),
        ];

        // ---- findmnt (con respaldo en /proc/mounts) ----
        $montajes = [];
        $r = self::comando('findmnt');
        $fj = $r['codigo'] === 0 ? json_decode($r['salida'], true) : null;
        if ($fj) {
            $plano = function ($lista) use (&$plano, &$montajes) {
                foreach ($lista as $f) {
                    if (str_starts_with((string) ($f['source'] ?? ''), '/dev/') && !str_starts_with($f['source'], '/dev/loop')) {
                        $montajes[] = ['destino' => $f['target'], 'origen' => $f['source'], 'fs' => $f['fstype'], 'opciones' => $f['options']];
                    }
                    $plano($f['children'] ?? []);
                }
            };
            $plano($fj['filesystems'] ?? []);
        } else {
            foreach (@file('/proc/mounts', FILE_IGNORE_NEW_LINES) ?: [] as $l) {
                $c = explode(' ', $l);
                if (count($c) >= 4 && str_starts_with($c[0], '/dev/') && !str_starts_with($c[0], '/dev/loop')) {
                    $montajes[] = ['destino' => $c[1], 'origen' => $c[0], 'fs' => $c[2], 'opciones' => $c[3]];
                }
            }
        }

        // ---- /etc/fstab ----
        $fstab = [];
        foreach (@file('/etc/fstab', FILE_IGNORE_NEW_LINES) ?: [] as $l) {
            $l = trim($l);
            if ($l === '' || $l[0] === '#') {
                continue;
            }
            $c = preg_split('/\s+/', $l);
            $fstab[] = array_pad(array_slice($c, 0, 6), 6, '');
        }

        return view('almacenamiento', [
            'discos' => $discos, 'particiones' => $particiones, 'df' => $df, 'resumen' => $resumen,
            'montajes' => $montajes, 'fstab' => $fstab, 'errores' => $errores,
        ]);
    }

    // ---------------------------------------------------------------- explorador de solo lectura
    public function explorador(Request $request)
    {
        $permitidas = array_values(array_filter(array_map('realpath', config('sysmonitor.rutas_permitidas'))));
        $ruta = (string) $request->query('ruta', '');
        $entradas = [];
        $real = null;
        $error = null;
        $padre = null;

        if ($ruta !== '') {
            $real = self::resolver($ruta, $permitidas);
            if ($real === null) {
                $error = 'La ruta no existe o no está dentro de los directorios permitidos.';
            } else {
                $lista = @scandir($real);
                if ($lista === false) {
                    $error = 'No hay permiso de lectura sobre este directorio.';
                } else {
                    foreach ($lista as $nombre) {
                        if ($nombre === '.' || $nombre === '..') {
                            continue;
                        }
                        $ruta2 = rtrim($real, '/') . '/' . $nombre;
                        $st = @lstat($ruta2);
                        if (!$st) {
                            continue;
                        }
                        $esDir = ($st['mode'] & 0170000) === 0040000;
                        $entradas[] = [
                            'nombre' => mb_scrub($nombre), 'ruta' => $ruta2, 'dir' => $esDir && self::resolver($ruta2, $permitidas) !== null,
                            'tipo' => $esDir ? 'dir' : (($st['mode'] & 0170000) === 0120000 ? 'link' : 'archivo'),
                            'permisos' => self::permisos($st['mode']),
                            'usuario' => self::nombreUsuario($st['uid']), 'grupo' => self::nombreGrupo($st['gid']),
                            'tam' => $esDir ? null : $st['size'], 'fecha' => date('Y-m-d H:i', $st['mtime']),
                        ];
                    }
                    usort($entradas, fn($a, $b) => [$a['tipo'] !== 'dir', strtolower($a['nombre'])] <=> [$b['tipo'] !== 'dir', strtolower($b['nombre'])]);
                    $p = dirname($real);
                    $padre = self::resolver($p, $permitidas) !== null ? $p : '';
                }
            }
        }

        return view('explorador', compact('permitidas', 'ruta', 'real', 'entradas', 'error', 'padre'));
    }

    // ---------------------------------------------------------------- utilidades
    public static function resolver(string $ruta, array $permitidas): ?string
    {
        if ($ruta === '' || str_contains($ruta, "\0")) {
            return null;
        }
        $real = @realpath($ruta);
        if ($real === false || !is_dir($real)) {
            return null;
        }
        foreach ($permitidas as $p) {
            if ($real === $p || str_starts_with($real, rtrim($p, '/') . '/')) {
                return $real;
            }
        }
        return null;
    }

    public static function permisos(int $m): string
    {
        $t = match ($m & 0170000) {
            0140000 => 's', 0120000 => 'l', 0100000 => '-', 0060000 => 'b', 0040000 => 'd', 0020000 => 'c', 0010000 => 'p', default => '?',
        };
        $s = $t;
        $s .= ($m & 0400) ? 'r' : '-';
        $s .= ($m & 0200) ? 'w' : '-';
        $s .= ($m & 04000) ? (($m & 0100) ? 's' : 'S') : (($m & 0100) ? 'x' : '-');
        $s .= ($m & 040) ? 'r' : '-';
        $s .= ($m & 020) ? 'w' : '-';
        $s .= ($m & 02000) ? (($m & 010) ? 's' : 'S') : (($m & 010) ? 'x' : '-');
        $s .= ($m & 04) ? 'r' : '-';
        $s .= ($m & 02) ? 'w' : '-';
        $s .= ($m & 01000) ? (($m & 01) ? 't' : 'T') : (($m & 01) ? 'x' : '-');
        return $s;
    }

    private static function nombreUsuario(int $uid): string
    {
        return function_exists('posix_getpwuid') ? (posix_getpwuid($uid)['name'] ?? (string) $uid) : (string) $uid;
    }

    private static function nombreGrupo(int $gid): string
    {
        return function_exists('posix_getgrgid') ? (posix_getgrgid($gid)['name'] ?? (string) $gid) : (string) $gid;
    }

    // Ejecuta un comando de la lista blanca, sin shell
    private static function comando(string $clave): array
    {
        if (!isset(self::COMANDOS[$clave])) {
            return ['codigo' => -1, 'salida' => '', 'error' => 'Comando no permitido'];
        }
        $argv = self::COMANDOS[$clave];
        $bin = null;
        foreach (['/usr/bin', '/bin', '/usr/sbin', '/sbin'] as $dir) {
            if (is_executable("$dir/{$argv[0]}")) {
                $bin = "$dir/{$argv[0]}";
                break;
            }
        }
        if (!$bin) {
            return ['codigo' => -1, 'salida' => '', 'error' => 'No se encontró el comando'];
        }
        $argv[0] = $bin;
        $proc = @proc_open($argv, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, null, ['LC_ALL' => 'C', 'PATH' => '/usr/sbin:/usr/bin:/sbin:/bin']);
        if (!is_resource($proc)) {
            return ['codigo' => -1, 'salida' => '', 'error' => 'No se pudo ejecutar'];
        }
        $salida = stream_get_contents($pipes[1]);
        $error = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        return ['codigo' => proc_close($proc), 'salida' => (string) $salida, 'error' => (string) $error];
    }
}
