<?php
require_once __DIR__.'/auth_common.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
requireAdmin(true);
$path='/home/josevicente/log/registros.csv';
if(!is_readable($path)){http_response_code(503);echo json_encode(['error'=>'No se puede leer el CSV de recursos']);exit;}
$rows=[];
if(($f=fopen($path,'r'))!==false){
 $header=fgetcsv($f);
 while(($r=fgetcsv($f))!==false){
  if(count($r)<4 || !is_numeric($r[1]) || !is_numeric($r[2]) || !is_numeric($r[3]))continue;
  $rows[]=['fecha'=>$r[0],'cpu'=>(float)$r[1],'ram'=>(float)$r[2],'disco'=>(float)$r[3]];
  if(count($rows)>1500)array_shift($rows);
 }
 fclose($f);
}
echo json_encode(['registros'=>$rows,'total_mostrado'=>count($rows)],JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE);
