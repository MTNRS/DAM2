<?php
declare(strict_types=1);
require_once __DIR__.'/inc/core.php';
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');
$started=microtime(true);$api=null;$input='';$status=500;$output='';$error=null;
try {
    if($_SERVER['REQUEST_METHOD']!=='POST'){header('Allow: POST');throw new InvalidArgumentException('Utiliza POST.');}
    $slug=(string)($_GET['ruta']??'');if(!preg_match('/^[a-z0-9][a-z0-9-]{2,63}$/',$slug))throw new InvalidArgumentException('Ruta inválida.');
    $q=db()->prepare('SELECT a.* FROM apis a JOIN cuentas c ON c.id=a.cuenta_id JOIN usuarios u ON u.id=a.usuario_id WHERE a.slug=? AND a.activo=1 AND c.activo=1 AND u.activo=1');$q->execute([$slug]);$api=$q->fetch();
    if(!$api){$status=404;throw new RuntimeException('API inexistente o inactiva.');}
    $key=(string)($_SERVER['HTTP_X_API_KEY']??'');
    if(!$key||!hash_equals($api['key_hash'],hash('sha256',$key))){$status=401;throw new RuntimeException('Clave API inválida.');}
    $input=file_get_contents('php://input',false,null,0,2097153);
    if(strlen($input)>2097152){$status=413;throw new RuntimeException('JSON demasiado grande (máximo 2 MB).');}
    $data=json_decode($input,true,128,JSON_THROW_ON_ERROR);
    if(!is_array($data)||array_is_list($data)){$status=422;throw new InvalidArgumentException('Se requiere un objeto JSON.');}
    require_once __DIR__.'/motor/bootstrap.php';
    $report=(new ProcesadorJSON())->procesar($data);
    // Estilos embebidos para que el HTML sea autónomo incluso fuera del servidor.
    $css=file_get_contents(__DIR__.'/motor/estilos.css');
    $output='<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>'.h($data['titulo']??'Informe').'</title><style>'.str_replace('</style','<\/style',$css).'</style></head><body>'.$report->render().'</body></html>';
    $status=200;
} catch(JsonException|InvalidArgumentException $e){$status=$status===500?422:$status;$error=$e->getMessage();}
catch(Throwable $e){$error=$e->getMessage();}
finally {
    if($api){try{$q=db()->prepare('INSERT INTO solicitudes(api_id,estado,duracion_ms,bytes_entrada,bytes_salida,error) VALUES(?,?,?,?,?,?)');$q->execute([$api['id'],$status,(int)round((microtime(true)-$started)*1000),strlen($input),strlen($output),$error]);}catch(Throwable $ignored){}}
}
http_response_code($status);
if($status===200){header('Content-Type: text/html; charset=utf-8');echo $output;}
else {header('Content-Type: application/json; charset=utf-8');echo json_encode(['error'=>$status===500?'Error interno del servidor':$error],JSON_UNESCAPED_UNICODE);}
