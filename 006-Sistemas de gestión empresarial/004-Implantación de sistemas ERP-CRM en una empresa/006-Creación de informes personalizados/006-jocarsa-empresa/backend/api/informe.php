<?php
declare(strict_types=1);
require_once __DIR__.'/auth_common.php';
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
try {
    $user = requireLogin(true);
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); throw new RuntimeException('Se requiere POST'); }
    $input = json_decode(file_get_contents('php://input'), true, 16, JSON_THROW_ON_ERROR);
    $table = (string)($input['tabla'] ?? '');
    if (!preg_match('/^[a-zA-Z0-9_-]{1,120}$/D', $table) || !permissionForTable($user, $table, 'seleccionar')) {
        http_response_code(403); throw new RuntimeException('No tienes permiso para consultar esta entidad');
    }
    $db = crimsonDb();
    $schema = array_merge($db->schema($table), array_map(static fn($f) => ['name'=>$f['name']], erpReportFields($db, $table)));
    $columns = array_values(array_unique(array_filter(array_map(static fn($c) => (string)($c['name'] ?? ''), $schema), static fn($n) => $n !== '' && !str_starts_with($n, '__'))));
    if (!$columns) throw new RuntimeException('La entidad no tiene columnas');
    $rows = erpReportValues($db, $table, erpReportEnrich($db, $table, $db->rows($table)));
    $reportRows = [];
    $limit = 1500000; // Margen respecto al límite remoto de 2 MB
    $size = 3000;
    foreach ($rows as $row) {
        $cells = [];
        foreach ($columns as $col) {
            $value = $row['__fk__'.$col] ?? $row[$col] ?? '';
            $cells[] = is_scalar($value) || $value === null ? (string)($value ?? '') : json_encode($value, JSON_UNESCAPED_UNICODE);
        }
        $bytes = strlen(json_encode($cells, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE));
        if ($size + $bytes > $limit) break;
        $reportRows[] = $cells;
        $size += $bytes;
    }
    $total = count($rows);
    $included = count($reportRows);
    $components = [
        ['tipo'=>'titulo','texto'=>'Informe de '.str_replace('_',' ', $table)],
        ['tipo'=>'parrafo','texto'=>'Entidad: '.$table.' · Registros incluidos: '.$included.' de '.$total.' · Generado: '.date('d/m/Y H:i')],
        ['tipo'=>'tabla_simple','columnas'=>$columns,'filas'=>$reportRows],
    ];
    if ($included < $total) $components[] = ['tipo'=>'parrafo','texto'=>'Informe parcial: se ha alcanzado el límite de tamaño de la API de informes.'];
    $report = ['version'=>1,'titulo'=>'Informe de '.$table,'paginas'=>[['secciones'=>[['titulo'=>'Registros de '.$table,'contenedores'=>[['componentes'=>$components]]]]]]];
    if (($input['formato'] ?? '') === 'json') {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok'=>true,'informe'=>$report], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        exit;
    }
    $key = getenv('JOCARSA_INFORMES_API_KEY') ?: '';
    $privateConfig = dirname(__DIR__).'/informes-config.local.php';
    if (is_file($privateConfig)) {
        $local = require $privateConfig;
        if (is_array($local) && isset($local['api_key'])) $key = (string)$local['api_key'];
    }
    if (!$key) throw new RuntimeException('Configura JOCARSA_INFORMES_API_KEY en el servidor o backend/informes-config.local.php');
    if (!function_exists('curl_init')) throw new RuntimeException('La extensión PHP cURL no está disponible');
    $body = json_encode($report, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR);
    if (strlen($body) > 2097152) throw new RuntimeException('Informe demasiado grande');
    $ch = curl_init('https://informes.jocarsa.com/api.php?ruta=jocarsa-erp');
    curl_setopt_array($ch, [CURLOPT_POST=>true, CURLOPT_POSTFIELDS=>$body, CURLOPT_RETURNTRANSFER=>true,
        CURLOPT_CONNECTTIMEOUT=>10, CURLOPT_TIMEOUT=>45, CURLOPT_FOLLOWLOCATION=>false,
        CURLOPT_HTTPHEADER=>['Content-Type: application/json','Accept: text/html','X-API-Key: '.$key]]);
    $html = curl_exec($ch);
    $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    if ($html === false) throw new RuntimeException('No se pudo conectar con el servicio de informes: '.$curlError);
    if ($status !== 200) throw new RuntimeException('El servicio de informes devolvió HTTP '.$status.': '.mb_substr(strip_tags((string)$html), 0, 250));
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok'=>true,'html'=>$html,'total'=>$total,'incluidos'=>$included], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR);
} catch (Throwable $e) {
    if (http_response_code() < 400) http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error'=>$e->getMessage()], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
}
function erpReportFields($db, string $table): array {
    if (!$db->tableExists('crimson_ui_config')) return [];
    foreach ($db->rows('crimson_ui_config') as $r) {
        if (($r['entidad']??'') === $table && ($r['tipo']??'') === 'config') {
            $cfg = json_decode((string)($r['contenido']??''), true);
            return array_values(array_filter($cfg['customFields']??[], static fn($f) => is_array($f) && isset($f['name']) && preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/D', $f['name'])));
        }
    }
    return [];
}
function erpReportValues($db, string $table, array $rows): array {
    $fields = erpReportFields($db, $table);
    if (!$fields || !$db->tableExists('crimson_ui_values')) return $rows;
    $map = [];
    foreach ($db->rows('crimson_ui_values') as $v) if (($v['entidad']??'') === $table) $map[(string)$v['registro']][$v['campo']] = $v['valor'];
    foreach ($rows as &$row) foreach ($fields as $f) $row[$f['name']] = $map[(string)$row['Identificador']][$f['name']] ?? null;
    return $rows;
}
function erpReportEnrich($db, string $table, array $rows): array {
    $fks = $db->foreignKeys($table);
    foreach ($rows as &$row) foreach ($fks as $field=>$fk) {
        $id = $row[$field]??null;
        if ($id === null || $id === '') continue;
        $target = $db->find($fk['tabla'], $id);
        if (!$target) continue;
        foreach (['nombre','razon_social','descripcion','titulo','codigo','username','email'] as $label) {
            if (!empty($target[$label])) { $row['__fk__'.$field] = (string)$target[$label]; break; }
        }
    }
    return $rows;
}
