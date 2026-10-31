<?php
require_once __DIR__.'/auth_common.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
try {
 requireAdmin(true); $db=crimsonDb();
 $input=json_decode(file_get_contents('php://input'),true)?:[];
 $action=$_GET['action']??'schema';
 $tables=array_values(array_filter($db->tables(false),fn($t)=>!str_starts_with($t,'__')));
 if($action==='schema'){$out=[];foreach($tables as $t)$out[]=['name'=>$t,'columns'=>$db->schema($t),'fks'=>$db->foreignKeys($t),'count'=>count($db->rows($t))];echo json_encode(['ok'=>true,'tables'=>$out]);exit;}
 if($action!=='generate'||$_SERVER['REQUEST_METHOD']!=='POST')throw new Exception('Operación no permitida');
 $chosen=array_values(array_intersect($tables,(array)($input['tables']??[])));
 $count=(int)($input['count']??0);if(!$chosen||$count<1||$count>1000)throw new Exception('Selecciona tablas y entre 1 y 1000 registros por tabla');
 $mode=$input['mode']??'empty';if(!in_array($mode,['empty','append'],true))throw new Exception('Modo inválido');
 $preview=($input['preview']??true)===true;
 $ordered=[];$pending=$chosen;
 while($pending){$progress=false;foreach($pending as $i=>$t){$deps=array_values(array_unique(array_column($db->foreignKeys($t),'tabla')));if(!array_intersect($deps,$pending)||count($pending)===1){$ordered[]=$t;unset($pending[$i]);$progress=true;}}if(!$progress)throw new Exception('Dependencias circulares: genera primero las tablas relacionadas');$pending=array_values($pending);}
 $names=['Ana','Luis','Marta','Pablo','Elena','Carlos','Lucía','Daniel','Sara','Javier'];$surnames=['García','Martínez','López','Sánchez','Ruiz','Navarro'];$report=[];
 $db->begin();try{
 foreach($ordered as $t){$existing=$db->rows($t);if($mode==='empty'&&$existing){$report[]=['table'=>$t,'inserted'=>0,'reason'=>'La tabla ya contiene registros'];continue;}
 $columns=$db->schema($t);$fks=$db->foreignKeys($t);$made=0;
 for($i=0;$i<$count;$i++){$row=[];$seq=count($existing)+$i+1;
 foreach($columns as $col){$key=$col['name'];if($key==='Identificador'||($col['pk']??false))continue;
 $type=strtoupper($col['type']??'TEXT');$lower=strtolower($key);
 if(isset($fks[$key])){$fk=$fks[$key];$related=$db->rows($fk['tabla']);if(!$related)throw new Exception("No hay registros para FK $t.$key → {$fk['tabla']}");$item=$related[($seq-1)%count($related)];$row[$key]=$item[$fk['campo']]??$item['Identificador'];continue;}
 $first=$names[$seq%count($names)];$last=$surnames[$seq%count($surnames)];
 if(preg_match('/email|correo/', $lower))$value='demo.'.strtolower($t).'.'.$seq.'@example.com';
 elseif(preg_match('/apellid/', $lower))$value=$last;
 elseif(preg_match('/nombre|razon_social|titulo/', $lower))$value=$first.' '.$last.' Demo '.$seq;
 elseif(preg_match('/telefono|movil/', $lower))$value='600'.str_pad((string)($seq%1000000),6,'0',STR_PAD_LEFT);
 elseif(preg_match('/fecha|date/', $lower)||$type==='DATE')$value=date('Y-m-d',strtotime('-'.(($seq*7)%365).' days'));
 elseif(preg_match('/precio|importe|total|coste|saldo/', $lower))$value=round(15+($seq%79)*12.45,2);
 elseif(preg_match('/activo|habilitado/', $lower))$value=1;
 elseif(preg_match('/estado/', $lower))$value=['pendiente','procesado','completado'][$seq%3];
 elseif(preg_match('/cantidad|stock|unidades/', $lower))$value=($seq*7)%150;
 elseif(preg_match('/codigo|referencia|numero/', $lower))$value='DEMO-'.strtoupper(substr($t,0,4)).'-'.str_pad((string)$seq,6,'0',STR_PAD_LEFT);
 elseif(in_array($type,['INTEGER','INT','REAL','NUMERIC','DECIMAL','FLOAT'],true))$value=in_array($type,['INTEGER','INT'],true)?($seq%100)+1:round(($seq%100)*1.73,2);
 else $value='Demostración '.$key.' '.$seq;
 $row[$key]=$value;
 }
 if(!$preview){$id=$db->insert($t,$row);if($id<=0)throw new Exception("Falló la inserción en $t");}$made++;
 }$report[]=['table'=>$t,'inserted'=>$made,'reason'=>$preview?'Simulación':'Insertados'];
 }
 if($preview)$db->rollback();else $db->commit();
 }catch(Throwable $e){$db->rollback();throw $e;}
 echo json_encode(['ok'=>true,'preview'=>$preview,'results'=>$report],JSON_UNESCAPED_UNICODE);
}catch(Throwable $e){http_response_code(400);echo json_encode(['ok'=>false,'error'=>$e->getMessage()],JSON_UNESCAPED_UNICODE);}
