<?php
/* API de M3 — guarda/consulta simulaciones de planificación en SQLite.
   Colócalo en la raíz del proyecto (junto a api.php). La BD se crea sola en data/planificacion.sqlite */
header('Content-Type: application/json; charset=utf-8');

function m3_db(): PDO {
    $file = __DIR__ . '/data/planificacion.sqlite';
    if (!is_dir(dirname($file))) { mkdir(dirname($file), 0775, true); }
    $pdo = new PDO('sqlite:' . $file);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("CREATE TABLE IF NOT EXISTS simulaciones (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        fecha TEXT NOT NULL,
        algoritmo TEXT NOT NULL,
        quantum INTEGER,
        num_procesos INTEGER NOT NULL,
        prom_espera REAL NOT NULL,
        prom_retorno REAL NOT NULL,
        datos TEXT NOT NULL
    )");
    return $pdo;
}

try {
    $pdo = m3_db();
    switch ($_GET['action'] ?? '') {
        case 'list':
            echo json_encode($pdo->query("SELECT id,fecha,algoritmo,quantum,num_procesos,prom_espera,prom_retorno
                                          FROM simulaciones ORDER BY id DESC LIMIT 100")->fetchAll(PDO::FETCH_ASSOC));
            break;
        case 'get':
            $st = $pdo->prepare("SELECT datos FROM simulaciones WHERE id = ?");
            $st->execute([(int)($_GET['id'] ?? 0)]);
            $r = $st->fetchColumn();
            echo $r ?: json_encode(['error' => 'No existe']);
            break;
        case 'save':
            $d = json_decode(file_get_contents('php://input'), true);
            if (!$d || empty($d['algo']) || empty($d['procs'])) { throw new Exception('Datos inválidos'); }
            $st = $pdo->prepare("INSERT INTO simulaciones (fecha,algoritmo,quantum,num_procesos,prom_espera,prom_retorno,datos)
                                 VALUES (?,?,?,?,?,?,?)");
            $st->execute([date('Y-m-d H:i:s'), (string)$d['algo'], (int)($d['q'] ?? 0), count($d['procs']),
                          (float)$d['avgW'], (float)$d['avgT'], json_encode($d, JSON_UNESCAPED_UNICODE)]);
            echo json_encode(['ok' => true, 'id' => (int)$pdo->lastInsertId()]);
            break;
        case 'delete':
            $st = $pdo->prepare("DELETE FROM simulaciones WHERE id = ?");
            $st->execute([(int)($_GET['id'] ?? 0)]);
            echo json_encode(['ok' => true]);
            break;
        default:
            echo json_encode(['error' => 'Acción desconocida']);
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}