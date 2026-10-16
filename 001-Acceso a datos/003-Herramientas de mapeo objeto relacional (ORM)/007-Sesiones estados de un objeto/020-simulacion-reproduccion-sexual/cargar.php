<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
function q(string $s): string { return '"'.str_replace('"','""',$s).'"'; }
function responder(int $status,array $data): never { http_response_code($status); echo json_encode($data,JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE|JSON_PARTIAL_OUTPUT_ON_ERROR); exit; }
function estructura(PDO $db): array {
    $result=[];
    foreach ($db->query("SELECT name FROM sqlite_master WHERE type='table' AND name LIKE 'simulacion%' AND name NOT LIKE 'sqlite_%'")->fetchAll(PDO::FETCH_COLUMN) as $tabla) {
        $cols=$db->query('PRAGMA table_info('.q($tabla).')')->fetchAll(PDO::FETCH_ASSOC);
        $fks=$db->query('PRAGMA foreign_key_list('.q($tabla).')')->fetchAll(PDO::FETCH_ASSOC);
        $result[$tabla]=['cols'=>array_column($cols,'name'),'fks'=>$fks];
    }
    return $result;
}
function reconstruir(PDO $db,string $tabla,array $fila,array $esquema,int $nivel=0): array {
    if ($nivel>40) throw new RuntimeException('Estructura demasiado profunda');
    $id=$fila['id']; unset($fila['id'],$fila['posicion']);
    foreach ($esquema[$tabla]['fks'] as $fk) unset($fila[$fk['from']]);
    foreach ($esquema as $hija=>$meta) {
        foreach ($meta['fks'] as $fk) {
            if ($fk['table']!==$tabla || !str_starts_with($hija,$tabla.'_')) continue;
            $clave=substr($hija,strlen($tabla)+1);
            $lista=in_array('posicion',$meta['cols'],true);
            $orden=$lista?'"posicion", "id"':'"id"';
            $stmt=$db->prepare('SELECT * FROM '.q($hija).' WHERE '.q($fk['from']).' = ? ORDER BY '.$orden);
            $stmt->execute([$id]);
            $elementos=[];
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
                $e=reconstruir($db,$hija,$r,$esquema,$nivel+1);
                if ($lista && count($e)===1 && array_key_exists('valor',$e)) $e=$e['valor'];
                $elementos[]=$e;
            }
            if ($lista) $fila[$clave]=$elementos;
            elseif (count($elementos)===1) $fila[$clave]=$elementos[0];
            elseif ($elementos) $fila[$clave]=$elementos;
        }
    }
    return $fila;
}
if ($_SERVER['REQUEST_METHOD']!=='GET') { header('Allow: GET'); responder(405,['ok'=>false,'error'=>'Solo GET']); }
$ruta=__DIR__.'/simulacion.db';
if (!is_file($ruta)) responder(200,['ok'=>true,'existe'=>false]);
try {
    $db=new PDO('sqlite:'.$ruta,null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE,PDO::FETCH_ASSOC);
    $esquema=estructura($db);
    if (!isset($esquema['simulacion'])) responder(200,['ok'=>true,'existe'=>false]);
    $fila=$db->query('SELECT * FROM "simulacion" ORDER BY "id" DESC LIMIT 1')->fetch();
    if (!$fila) responder(200,['ok'=>true,'existe'=>false]);
    $datos=reconstruir($db,'simulacion',$fila,$esquema);
    // Las listas vacías no generan tablas en el guardado recursivo.
    foreach (['personas','comidas','historialPoblacion'] as $clave) if (!isset($datos[$clave])) $datos[$clave]=[];
    responder(200,['ok'=>true,'existe'=>true,'datos'=>$datos]);
} catch(Throwable $e) { error_log('cargar.php: '.$e->getMessage()); responder(500,['ok'=>false,'error'=>'No se pudo recuperar la simulación']); }
