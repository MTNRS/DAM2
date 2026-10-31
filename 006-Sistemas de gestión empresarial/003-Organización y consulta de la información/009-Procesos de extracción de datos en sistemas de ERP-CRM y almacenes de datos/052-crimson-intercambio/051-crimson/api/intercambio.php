<?php
require_once __DIR__.'/auth_common.php';
header('Cache-Control: no-store');
function responder($x,$status=200){http_response_code($status);header('Content-Type: application/json; charset=utf-8');echo json_encode($x,JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE);exit;}
try{
 $u=requireLogin(true);$db=crimsonDb();$t=$_GET['tabla']??$_POST['tabla']??'';
 if(!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/D',$t)||str_starts_with($t,'__')||!$db->tableExists($t))throw new Exception('Entidad no válida');
 $accion=$_GET['accion']??'';$importar=$accion==='importar';
 if(!permissionForTable($u,$t,$importar?'insertar':'seleccionar')){http_response_code(403);throw new Exception('Permiso insuficiente');}
 $schema=$db->schema($t);$columns=array_values(array_filter(array_column($schema,'name'),fn($c)=>$c!=='Identificador'&&$c!=='id'));
 $format=$_GET['formato']??'';if(!in_array($format,['csv','json'],true))throw new Exception('Formato inválido');
 if($importar){
  if($_SERVER['REQUEST_METHOD']!=='POST')throw new Exception('Se requiere POST');
  if(empty($_FILES['archivo'])||$_FILES['archivo']['error']!==UPLOAD_ERR_OK)throw new Exception('Error al subir el archivo');
  if($_FILES['archivo']['size']>5*1024*1024)throw new Exception('Archivo demasiado grande (máximo 5 MB)');
  $raw=file_get_contents($_FILES['archivo']['tmp_name']);
  if($format==='json'){$records=json_decode($raw,true,512,JSON_THROW_ON_ERROR);if(isset($records['registros']))$records=$records['registros'];if(!is_array($records)||!array_is_list($records))throw new Exception('JSON: se esperaba una lista de registros');}
  else{$f=fopen('php://temp','w+');fwrite($f,preg_replace('/^\xEF\xBB\xBF/','',$raw));rewind($f);$header=fgetcsv($f,0,',', '"', '');if(!$header)throw new Exception('CSV vacío');$header=array_map('trim',$header);if(count($header)!==count(array_unique($header)))throw new Exception('Columnas repetidas');$records=[];while(($row=fgetcsv($f,0,',','"',''))!==false){if($row===[null])continue;if(count($row)!==count($header))throw new Exception('Número de columnas incorrecto');$records[]=array_combine($header,$row);}fclose($f);}
  if(count($records)>5000)throw new Exception('Máximo 5000 registros por importación');
  $clean=[];foreach($records as $n=>$r){if(!is_array($r)||array_is_list($r))throw new Exception('Registro '.($n+1).' inválido');$unknown=array_diff(array_keys($r),array_merge($columns,['Identificador','id']));if($unknown)throw new Exception('Columnas desconocidas: '.implode(', ',$unknown));$data=[];foreach($columns as $c)if(array_key_exists($c,$r)){$v=$r[$c];if(is_array($v)||is_object($v))throw new Exception('Valor no escalar en '.$c);$data[$c]=$v===''?null:$v;}if(!$data)throw new Exception('Registro '.($n+1).' sin datos');$clean[]=$data;}
  $count=0;$db->begin();try{foreach($clean as $data){$db->insert($t,$data);$count++;}$db->commit();}catch(Throwable $e){$db->rollback();throw $e;}
  responder(['ok'=>true,'insertados'=>$count]);
 }
 if(!in_array($accion,['exportar','plantilla'],true))throw new Exception('Operación inválida');
 $rows=$accion==='plantilla'?[array_fill_keys($columns,'')]:array_map(function($r)use($columns){$x=[];foreach($columns as $c)$x[$c]=$r[$c]??null;return $x;},$db->rows($t));
 $filename=$t.'_'.$accion.'.'.$format;
 header('Content-Disposition: attachment; filename="'.$filename.'"');
 if($format==='json'){header('Content-Type: application/json; charset=utf-8');echo json_encode($rows,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE);exit;}
 header('Content-Type: text/csv; charset=utf-8');$out=fopen('php://output','w');fwrite($out,"\xEF\xBB\xBF");fputcsv($out,$columns,',','"','');foreach($rows as $r){$values=[];foreach($columns as $c){$v=$r[$c]??'';$v=(string)$v;if(preg_match('/^[\s]*[=+@\-\t\r]/u',$v))$v="'".$v;$values[]=$v;}fputcsv($out,$values,',','"','');}fclose($out);exit;
}catch(Throwable $e){responder(['error'=>$e->getMessage()],http_response_code()===403?403:400);}
