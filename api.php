<?php
require __DIR__ . '/includes/proc.php';
header('Content-Type: application/json; charset=utf-8');

function salir($datos, int $codigo = 200) { http_response_code($codigo); echo json_encode($datos); exit; }
function top(array $lista, string $k): array { usort($lista, fn($a, $b) => $b[$k] <=> $a[$k]); return array_slice($lista, 0, 5); }

$entrada = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Solo JSON: evita que otra página web envíe formularios a este endpoint
    if (stripos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') === false) salir(['error' => 'Petición no válida'], 400);
    $entrada = json_decode(file_get_contents('php://input'), true) ?: [];
}

switch ($_GET['action'] ?? '') {
    case 'procesos':   // M1
        salir(procMuestrear()['procesos']);
    case 'sistema':    // M2: CPU + memoria + top 5
        $m = procMuestrear();
        salir(procCpuInfo() + [
            'uso' => $m['cpu_uso'], 'mem' => procMemoria(),
            'top_cpu' => top($m['procesos'], 'cpu'), 'top_mem' => top($m['procesos'], 'mem'),
        ]);
    case 'lanzar':
        $tipo = $entrada['tipo'] ?? '';
        if (!in_array($tipo, ['sleep', 'carga'], true)) salir(['error' => 'Tipo inválido'], 400);
        salir(['pid' => procLanzar($tipo)]);
    case 'senal':
        if (!procSenal((int) ($entrada['pid'] ?? 0), (string) ($entrada['senal'] ?? ''))) {
            salir(['error' => 'Solo se pueden controlar procesos lanzados desde la app'], 403);
        }
        salir(['ok' => true]);
    case 'renice':
        if (!procRenice((int) ($entrada['pid'] ?? 0), (int) ($entrada['nice'] ?? 0))) {
            salir(['error' => 'No se pudo cambiar (¿proceso ajeno o bajar el nice sin ser root?)'], 403);
        }
        salir(['ok' => true]);
    default:
        salir(['error' => 'Acción desconocida'], 404);
}
