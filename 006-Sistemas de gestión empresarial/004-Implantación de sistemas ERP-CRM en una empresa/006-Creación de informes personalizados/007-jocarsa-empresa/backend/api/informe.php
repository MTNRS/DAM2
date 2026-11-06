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
    $total = count($rows);
    $reportRows = [];
    $limit = 1250000; // reservar espacio para estadísticas y gráficas (API 2 MB)
    $size = 10000;
    foreach ($rows as $row) {
        $cells = [];
        foreach ($columns as $col) {
            $value = $row['__fk__'.$col] ?? $row[$col] ?? '';
            $cells[] = is_scalar($value) || $value === null ? (string)($value ?? '') : json_encode($value, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        }
        $bytes = strlen(json_encode($cells, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE));
        if ($size + $bytes > $limit) break;
        $reportRows[] = $cells;
        $size += $bytes;
    }
    $included = count($reportRows);
    // Las estadísticas se calculan sobre las filas incluidas, no sobre un subconjunto invisible.
    $analysis = erpReportAnalyze($columns, $reportRows);
    $title = 'Informe de '.str_replace('_', ' ', $table);
    $intro = [
        ['tipo'=>'titulo','texto'=>$title],
        ['tipo'=>'parrafo','texto'=>'Entidad: '.$table.' · Registros analizados: '.$included.' de '.$total.' · Generado: '.date('d/m/Y H:i')],
        ['tipo'=>'contador','etiqueta'=>'Registros analizados','valor'=>$included],
        ['tipo'=>'contador','etiqueta'=>'Campos','valor'=>count($columns)],
    ];
    if ($included < $total) $intro[] = ['tipo'=>'nota','texto'=>'Informe parcial: se alcanzó el límite de tamaño. Las estadísticas se calculan únicamente sobre los registros incluidos.'];
    $sections = [['titulo'=>'Resumen ejecutivo','contenedores'=>[['componentes'=>$intro]]]];
    if ($analysis['numeric']) {
        $parts = [['tipo'=>'subtitulo','texto'=>'Estadísticas numéricas']];
        foreach ($analysis['numeric'] as $n) {
            $parts[] = ['tipo'=>'kpi','etiqueta'=>'Suma · '.$n['field'],'valor'=>erpReportFormat($n['sum']),'descripcion'=>'Media: '.erpReportFormat($n['avg']).' · Mínimo: '.erpReportFormat($n['min']).' · Máximo: '.erpReportFormat($n['max']).' · Valores: '.$n['count']];
        }
        $parts[] = ['tipo'=>'tabla_simple','columnas'=>['Campo','Valores','Suma','Media','Mínimo','Máximo'],
            'filas'=>array_map(static fn($n)=>[$n['field'],(string)$n['count'],erpReportFormat($n['sum']),erpReportFormat($n['avg']),erpReportFormat($n['min']),erpReportFormat($n['max'])],$analysis['numeric'])];
        $sections[] = ['titulo'=>'Magnitudes y sumas','contenedores'=>[['componentes'=>$parts]]];
    }
    foreach ($analysis['groups'] as $g) {
        $parts = [
            ['tipo'=>'subtitulo','texto'=>'Distribución por '.$g['field']],
            ['tipo'=>'tabla_simple','columnas'=>['Categoría','Registros','Porcentaje'], 'filas'=>array_map(static fn($item)=>[$item['label'],(string)$item['count'],number_format($item['count']*100/max(1,$included),1,',','.').'%'],$g['items'])],
            ['tipo'=>'barras','titulo'=>'Registros por '.$g['field'],'etiquetas'=>array_column($g['items'],'label'),'valores'=>array_column($g['items'],'count')]
        ];
        if (count($g['items']) <= 10) $parts[] = ['tipo'=>'circular','titulo'=>'Proporción por '.$g['field'],'etiquetas'=>array_column($g['items'],'label'),'valores'=>array_column($g['items'],'count')];
        if ($analysis['numeric']) {
            $num = $analysis['numeric'][0]['field'];
            $idxGroup = array_search($g['field'], $columns, true);
            $idxNum = array_search($num, $columns, true);
            $sums = [];
            foreach ($reportRows as $r) {
                $key = trim((string)($r[$idxGroup] ?? '')) ?: 'Sin especificar';
                $value = erpReportNumber($r[$idxNum] ?? null);
                if ($value !== null) $sums[$key] = ($sums[$key] ?? 0) + $value;
            }
            $sums = array_filter($sums,static fn($k)=>in_array($k,array_column($g['items'],'label'),true),ARRAY_FILTER_USE_KEY);
            if ($sums) {
                $parts[] = ['tipo'=>'tabla_simple','columnas'=>[$g['field'],'Suma de '.$num], 'filas'=>array_map(static fn($key)=>[$key,erpReportFormat($sums[$key])],array_keys($sums))];
                if (min($sums) >= 0) $parts[] = ['tipo'=>'barras','titulo'=>'Suma de '.$num.' por '.$g['field'],'etiquetas'=>array_keys($sums),'valores'=>array_values($sums)];
            }
        }
        $sections[] = ['titulo'=>'Agrupación: '.$g['field'],'contenedores'=>[['componentes'=>$parts]]];
    }
    $sections[] = ['titulo'=>'Detalle de registros','contenedores'=>[['componentes'=>[
        ['tipo'=>'subtitulo','texto'=>'Relación de registros'],
        ['tipo'=>'tabla_simple','columnas'=>$columns,'filas'=>$reportRows],
    ]]]];
    $report = ['version'=>1,'titulo'=>$title,'paginas'=>[['secciones'=>$sections]]];
    // Reducir únicamente el detalle si las secciones analíticas elevan demasiado el JSON.
    while (strlen(json_encode($report,JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE)) > 1900000 && $reportRows) {
        array_pop($reportRows);
        $included = count($reportRows);
        $report['paginas'][0]['secciones'][count($sections)-1]['contenedores'][0]['componentes'][1]['filas'] = $reportRows;
    }
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

function erpReportNumber($value): ?float {
    if (is_int($value) || is_float($value)) return is_finite((float)$value) ? (float)$value : null;
    if (!is_string($value)) return null;
    $v = trim($value);
    if ($v === '' || !preg_match('/^-?(?:\d+)(?:\.\d+)?$/D', $v)) return null;
    $n = (float)$v;
    return is_finite($n) ? $n : null;
}
function erpReportFormat(float $v): string { return number_format($v, 2, ',', '.'); }
function erpReportAnalyze(array $columns, array $rows): array {
    $numeric=[]; $groups=[];
    $total=count($rows);
    foreach ($columns as $i=>$field) {
        $values=array_column($rows,$i);
        $nonempty=array_values(array_filter($values,static fn($v)=>trim((string)$v)!==''));
        if (!$nonempty) continue;
        $nums=array_values(array_filter(array_map('erpReportNumber',$nonempty),static fn($v)=>$v!==null));
        $isIdentifier=(bool)preg_match('/^(id|identificador|.*_id|.*id_)$/i',$field);
        if (!$isIdentifier && count($nums)===count($nonempty) && count($nums)>0 && count($numeric)<8) {
            $sum=array_sum($nums);
            $numeric[]=['field'=>$field,'count'=>count($nums),'sum'=>$sum,'avg'=>$sum/count($nums),'min'=>min($nums),'max'=>max($nums)];
        }
        if ($isIdentifier || count($groups)>=3) continue;
        $freq=[];
        foreach ($values as $value) {
            $key=trim((string)$value); if ($key==='') $key='Sin especificar';
            if (mb_strlen($key)>60) $key=mb_substr($key,0,57).'…';
            $freq[$key]=($freq[$key]??0)+1;
        }
        $distinct=count($freq);
        // Evitar agrupaciones por nombres, emails, descripciones o casi todos los registros.
        if ($distinct<2 || $distinct>12 || ($total>=8 && $distinct>$total*.65) || preg_match('/email|correo|telefono|direccion|descripcion|observacion|nota|password|clave/i',$field)) continue;
        arsort($freq);
        $items=[];foreach ($freq as $key=>$count) $items[]=['label'=>(string)$key,'count'=>$count];
        $groups[]=['field'=>$field,'items'=>$items];
    }
    return ['numeric'=>$numeric,'groups'=>$groups];
}
