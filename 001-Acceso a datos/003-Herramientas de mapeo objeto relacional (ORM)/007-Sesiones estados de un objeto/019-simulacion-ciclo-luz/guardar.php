<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function responder(int $codigo, array $datos): never {
    http_response_code($codigo);
    echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}
function identificador(string $nombre): string {
    return '"' . str_replace('"', '""', $nombre) . '"';
}
function columnas(PDO $db, string $tabla): array {
    $consulta = $db->query('PRAGMA table_info(' . identificador($tabla) . ')');
    return array_column($consulta->fetchAll(PDO::FETCH_ASSOC), 'name');
}
function prepararTabla(PDO $db, string $tabla, array $claves, ?string $padre, bool $tienePosicion): void {
    $fk = $padre !== null ? $padre . '_id' : null;
    $definiciones = ['"id" INTEGER PRIMARY KEY AUTOINCREMENT'];
    if ($fk !== null) $definiciones[] = identificador($fk) . ' INTEGER';
    if ($tienePosicion) $definiciones[] = '"posicion" INTEGER';
    if ($fk !== null) {
        $definiciones[] = 'FOREIGN KEY (' . identificador($fk) . ') REFERENCES ' . identificador($padre) . '(id) ON DELETE CASCADE';
    }
    $db->exec('CREATE TABLE IF NOT EXISTS ' . identificador($tabla) . ' (' . implode(',', $definiciones) . ')');
    $existentes = columnas($db, $tabla);
    foreach ($claves as $clave) {
        if (!in_array($clave, $existentes, true) && !in_array($clave, ['id', 'posicion', $fk], true)) {
            $db->exec('ALTER TABLE ' . identificador($tabla) . ' ADD COLUMN ' . identificador($clave) . ' TEXT');
            $existentes[] = $clave;
        }
    }
}
function procesarObjeto(PDO $db, string $tabla, mixed $datos, ?string $padre = null, ?int $idPadre = null, ?int $posicion = null, int $profundidad = 0): int {
    if ($profundidad > 40) throw new RuntimeException('JSON demasiado profundo');
    if (!is_array($datos) || array_is_list($datos)) $datos = ['valor' => $datos];
    $simples = [];
    foreach ($datos as $clave => $valor) {
        if (!is_array($valor)) $simples[(string)$clave] = $valor;
    }
    prepararTabla($db, $tabla, array_keys($simples), $padre, $posicion !== null);
    $campos = [];
    $valores = [];
    if ($padre !== null) { $campos[] = $padre . '_id'; $valores[] = $idPadre; }
    if ($posicion !== null) { $campos[] = 'posicion'; $valores[] = $posicion; }
    foreach ($simples as $clave => $valor) {
        $campos[] = $clave;
        $valores[] = is_bool($valor) ? (int)$valor : $valor;
    }
    if ($campos) {
        $sql = 'INSERT INTO ' . identificador($tabla) . ' (' . implode(',', array_map('identificador', $campos)) . ') VALUES (' . implode(',', array_fill(0, count($campos), '?')) . ')';
        $stmt = $db->prepare($sql);
        $stmt->execute($valores);
    } else {
        $db->exec('INSERT INTO ' . identificador($tabla) . ' DEFAULT VALUES');
    }
    $idActual = (int)$db->lastInsertId();
    foreach ($datos as $clave => $valor) {
        if (!is_array($valor)) continue;
        $hija = $tabla . '_' . $clave;
        if (array_is_list($valor)) {
            foreach ($valor as $i => $elemento) procesarObjeto($db, $hija, $elemento, $tabla, $idActual, $i, $profundidad + 1);
        } else {
            procesarObjeto($db, $hija, $valor, $tabla, $idActual, null, $profundidad + 1);
        }
    }
    return $idActual;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    responder(405, ['ok' => false, 'error' => 'Solo se admite POST']);
}
$raw = file_get_contents('php://input');
if ($raw === false || $raw === '') responder(400, ['ok' => false, 'error' => 'Cuerpo vacío']);
try {
    $datos = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
    if (!is_array($datos) || array_is_list($datos) || !isset($datos['personas']) || !is_array($datos['personas']) || !array_is_list($datos['personas'])) {
        responder(400, ['ok' => false, 'error' => 'Se requiere un objeto JSON con personas como array']);
    }
    $ruta = __DIR__ . '/simulacion.db';
    $db = new PDO('sqlite:' . $ruta, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $db->exec('PRAGMA foreign_keys = ON');
    $db->exec('PRAGMA busy_timeout = 5000');
    $db->beginTransaction();
    $tablas = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name LIKE 'simulacion%' AND name NOT LIKE 'sqlite_%'")->fetchAll(PDO::FETCH_COLUMN);
    usort($tablas, static fn($a, $b) => substr_count($b, '_') <=> substr_count($a, '_'));
    foreach ($tablas as $tabla) $db->exec('DROP TABLE ' . identificador($tabla));
    $id = procesarObjeto($db, 'simulacion', $datos);
    $db->commit();
    responder(200, ['ok' => true, 'personas' => count($datos['personas']), 'archivo' => 'simulacion.db', 'id' => $id]);
} catch (Throwable $e) {
    if (isset($db) && $db->inTransaction()) $db->rollBack();
    error_log('guardar.php: ' . $e->getMessage());
    responder(500, ['ok' => false, 'error' => 'No se pudo guardar la simulación']);
}
