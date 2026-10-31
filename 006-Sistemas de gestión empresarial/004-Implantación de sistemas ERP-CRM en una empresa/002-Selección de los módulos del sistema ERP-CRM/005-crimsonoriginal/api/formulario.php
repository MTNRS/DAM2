<?php
session_start();header('Cache-Control: no-store');header('X-Content-Type-Options: nosniff');header('Referrer-Policy: no-referrer');header('X-Frame-Options: DENY');
require_once __DIR__.'/nucleo/JocarsaDB.php';
function esc($s){return htmlspecialchars((string)$s,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
$token=(string)($_GET['f']??'');$forms=json_decode(@file_get_contents(__DIR__.'/modelo/formularios-publicos.json'),true);if(!is_array($forms))$forms=[];
$form=null;foreach($forms as $x)if(!empty($x['activo'])&&isset($x['token'])&&hash_equals($x['token'],$token)){$form=$x;break;}
if(!$form){http_response_code(404);exit('Formulario no disponible');}
$db=JocarsaDB::instance(true);$table=$form['tabla'];if(str_starts_with($table,'__crimson_')||!in_array($table,$db->tables(false),true)){http_response_code(404);exit('Formulario no disponible');}
$schema=[];foreach($db->schema($table) as $c)$schema[$c['name']]=$c;
$fields=array_values(array_filter($form['campos'],fn($f)=>isset($schema[$f])&&$f!=='Identificador'&&!str_starts_with($f,'__')));
if(!$fields){http_response_code(404);exit('Formulario no disponible');}
$key='public_form_'.hash('sha256',$token);if(empty($_SESSION[$key]))$_SESSION[$key]=bin2hex(random_bytes(24));
$error='';$done=false;$values=[];
if($_SERVER['REQUEST_METHOD']==='POST'){
try{
if(!hash_equals($_SESSION[$key],(string)($_POST['_csrf']??'')))throw new Exception('Sesión caducada. Recarga el formulario.');
if(!empty($_POST['_website']))throw new Exception('Solicitud no válida');
$last=(int)($_SESSION[$key.'_last']??0);if(time()-$last<15)throw new Exception('Espera unos segundos antes de enviar otra respuesta.');
$data=[];foreach($fields as $field){$v=$_POST['campo'][$field]??'';if(!is_string($v))throw new Exception('Valor inválido');$v=trim($v);if(mb_strlen($v)>4000)throw new Exception('Campo demasiado largo: '.$field);if(in_array($field,$form['obligatorios']??[],true)&&$v==='')throw new Exception('El campo '. $field.' es obligatorio');if($v==='')continue;$type=strtoupper($schema[$field]['type']??'TEXT');if(in_array($type,['INTEGER','REAL','NUMERIC'],true)&&!is_numeric($v))throw new Exception('Número inválido en '.$field);$data[$field]=$v;$values[$field]=$v;}
if(!$data)throw new Exception('No hay datos para guardar');
$db->insert($table,$data);$_SESSION[$key.'_last']=time();$_SESSION[$key]=bin2hex(random_bytes(24));$done=true;
}catch(Throwable $e){$error=$e->getMessage();}
}
?><!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?=esc($form['titulo'])?></title><style>body{font-family:Ubuntu,Arial,sans-serif;background:#f5f5f7;margin:0;color:#242424}.wrap{max-width:650px;margin:6vh auto;padding:30px;background:white;border-radius:12px;box-shadow:0 12px 45px #0001}h1{color:crimson;margin-top:0}label{display:block;margin:18px 0 5px;font-weight:600}input,textarea{box-sizing:border-box;width:100%;border:1px solid #bbb;border-radius:6px;padding:12px;font:inherit}button{margin-top:22px;background:crimson;color:white;border:0;border-radius:6px;padding:13px 24px;cursor:pointer}.error{color:#a00020}.success{color:#176a35}.hp{position:absolute;left:-9999px}@media(max-width:700px){.wrap{margin:0;min-height:100vh;border-radius:0;padding:22px}}</style></head><body><main class="wrap"><h1><?=esc($form['titulo'])?></h1><p><?=nl2br(esc($form['descripcion']??''))?></p><?php if($done):?><p class="success">Datos enviados correctamente. Gracias.</p><?php else:?><?php if($error):?><p class="error"><?=esc($error)?></p><?php endif;?><form method="post"><input type="hidden" name="_csrf" value="<?=esc($_SESSION[$key])?>"><div class="hp" aria-hidden="true"><input tabindex="-1" autocomplete="off" name="_website"></div><?php foreach($fields as $f):$type=strtoupper($schema[$f]['type']??'TEXT');$required=in_array($f,$form['obligatorios']??[],true);?><label for="<?=esc($f)?>"><?=esc(str_replace('_',' ',$f))?><?=$required?' *':''?></label><?php if($type==='TEXT'&&preg_match('/descripcion|comentario|observacion|mensaje/i',$f)):?><textarea id="<?=esc($f)?>" name="campo[<?=esc($f)?>]" maxlength="4000" <?=$required?'required':''?>><?=esc($values[$f]??'')?></textarea><?php else:?><input id="<?=esc($f)?>" name="campo[<?=esc($f)?>]" type="<?=in_array($type,['INTEGER','REAL','NUMERIC'])?'number':(stripos($f,'email')!==false?'email':'text')?>" <?=in_array($type,['REAL','NUMERIC'])?'step="any"':''?> maxlength="4000" value="<?=esc($values[$f]??'')?>" <?=$required?'required':''?>><?php endif;endforeach;?><button type="submit">Enviar datos</button></form><?php endif;?></main></body></html>
