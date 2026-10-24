<?php

namespace App\Http\Controllers;

// M5 - Usuarios del sistema (/etc/passwd), grupos (/etc/group) y sesiones activas (who). Solo lectura.
class UsuariosController extends Controller
{
    public function index()
    {
        $grupos = [];
        $gidNombre = [];
        foreach (@file('/etc/group', FILE_IGNORE_NEW_LINES) ?: [] as $l) {
            $c = explode(':', $l);
            if (count($c) >= 4) {
                $gidNombre[(int) $c[2]] = $c[0];
                $grupos[] = ['nombre' => $c[0], 'gid' => (int) $c[2], 'miembros' => $c[3] === '' ? [] : explode(',', $c[3])];
            }
        }

        $usuarios = [];
        foreach (@file('/etc/passwd', FILE_IGNORE_NEW_LINES) ?: [] as $l) {
            $c = explode(':', $l);
            if (count($c) < 7) {
                continue;
            }
            $uid = (int) $c[2];
            $gid = (int) $c[3];
            $shell = $c[6];
            $login = !preg_match('#/(nologin|false)$#', $shell) && $shell !== '/bin/sync';
            // Humano: UID desde 1000 (excepto nobody 65534). root y demas son de sistema.
            $humano = $uid >= 1000 && $uid < 65534;
            $usuarios[] = [
                'usuario' => $c[0], 'uid' => $uid, 'grupo' => $gidNombre[$gid] ?? (string) $gid,
                'info' => mb_scrub(explode(',', $c[4])[0]), 'home' => $c[5], 'shell' => $shell,
                'tipo' => $humano ? 'humano' : 'sistema', 'login' => $login,
            ];
        }
        usort($usuarios, fn($a, $b) => $a['uid'] <=> $b['uid']);

        // Sesiones activas con who
        $sesiones = [];
        $bin = null;
        foreach (['/usr/bin/who', '/bin/who'] as $c) {
            if (is_executable($c)) {
                $bin = $c;
                break;
            }
        }
        if ($bin) {
            $proc = @proc_open([$bin], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, null, ['LC_ALL' => 'C', 'PATH' => '/usr/bin:/bin']);
            if (is_resource($proc)) {
                $salida = stream_get_contents($pipes[1]);
                fclose($pipes[1]);
                fclose($pipes[2]);
                proc_close($proc);
                foreach (explode("\n", trim((string) $salida)) as $l) {
                    if (preg_match('/^(\S+)\s+(\S+)\s+(\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2})\s*(?:\((.*)\))?/', $l, $m)) {
                        $sesiones[] = ['usuario' => $m[1], 'terminal' => $m[2], 'inicio' => $m[3], 'origen' => $m[4] ?? ''];
                    }
                }
            }
        }

        return view('usuarios', compact('usuarios', 'grupos', 'sesiones'));
    }
}
