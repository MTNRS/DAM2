<?php
header('Content-Type: application/json; charset=utf-8');header('Cache-Control: no-store');
require_once __DIR__.'/auth_common.php';requireAdmin(true);
function reply($x,$status=200){http_response_code($status);echo json_encode($x,JSON_UNESCAPED_UNICODE);exit;}
function configPath(){return dirname(__DIR__).'/modelo/formularios-publicos.json';}
function configs(){ $f=configPath();$d=is_file($f)?json_decode(file_get_contents($f),true):[];return is_array($d)?$d:[]; }
function saveConfigs($d){$f=configPath();$h=fopen($f,'c+');if(!$h)throw new Exception('No se puede guardar configuración');flock($h,LOCK_EX);ftruncate($h,0);rewind($h);fwrite($h,json_encode($d,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));fflush($h);flock($h,LOCK_UN);fclose($h);}
try{
$db=crimsonDb();$action=$_GET['action']??'list';$d=configs();
if($action==='list'){$tables=[];foreach($db->tables(false) as $t){$columns=array_values(array_filter($db->schema($t),fn($c)=>$c['name']!=='Identificador'));$tables[]=['name'=>$t,'columns'=>$columns];}reply(['ok'=>true,'tables'=>$tables,'forms'=>array_values($d)]);}
if($_SERVER['REQUEST_METHOD']!=='POST')reply(['ok'=>false,'error'=>'Se requiere POST'],405);
$b=json_decode(file_get_contents('php://input'),true);if(!is_array($b))throw new Exception('JSON inválido');
if($action==='save'){
$t=(string)($b['tabla']??'');if(str_starts_with($t,'__crimson_')||!in_array($t,$db->tables(false),true))throw new Exception('Tabla no permitida');
$allowed=array_values(array_filter(array_column($db->schema($t),'name'),fn($x)=>$x!=='Identificador'&&!str_starts_with($x,'__')));
$fields=$b['campos']??[];if(!is_array($fields)||!$fields||count($fields)>100||count($fields)!==count(array_unique($fields))||array_diff($fields,$allowed))throw new Exception('Selecciona campos válidos');
$required=$b['obligatorios']??[];if(!is_array($required)||array_diff($required,$fields))throw new Exception('Obligatorios inválidos');
$old=$d[$t]??[];$token=$old['token']??bin2hex(random_bytes(24));
$d[$t]=['tabla'=>$t,'titulo'=>mb_substr(trim((string)($b['titulo']??$t)),0,120),'descripcion'=>mb_substr(trim((string)($b['descripcion']??'')),0,500),'activo'=>!empty($b['activo']),'campos'=>array_values($fields),'obligatorios'=>array_values($required),'token'=>$token];saveConfigs($d);reply(['ok'=>true,'form'=>$d[$t]]);
}
if($action==='rotate'){$t=(string)($b['tabla']??'');if(!isset($d[$t]))throw new Exception('Formulario no encontrado');$d[$t]['token']=bin2hex(random_bytes(24));saveConfigs($d);reply(['ok'=>true,'form'=>$d[$t]]);}
throw new Exception('Acción desconocida');
}catch(Throwable $e){reply(['ok'=>false,'error'=>$e->getMessage()],400);}
