<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
try {
 if (!extension_loaded('jocarsabbdd')) throw new RuntimeException('Falta la extensión PHP jocarsabbdd. Instala el motor y reinicia Apache.');
 if (!is_file('/etc/jocarsa/config.json')) throw new RuntimeException('No existe /etc/jocarsa/config.json');
 jocarsabbdd_configurar('/etc/jocarsa/config.json');
 $input=json_decode(file_get_contents('php://input'),true,512,JSON_THROW_ON_ERROR);
 if (!is_array($input)) throw new RuntimeException('JSON inválido');
 $root=__DIR__.'/private';if(!is_dir($root)&&!mkdir($root,0770,true))throw new RuntimeException('No se puede crear private/');
 $meta=$root.'/projects.json';
 $fp=fopen($root.'/lock','c');if(!$fp||!flock($fp,LOCK_EX))throw new RuntimeException('Bloqueo no disponible');
 $state=is_file($meta)?json_decode(file_get_contents($meta),true):null;
 if(!is_array($state))$state=['activeId'=>null,'projects'=>[]];
 $action=$input['action']??'';
 function required($v,$max=80){if(!is_string($v)||!preg_match('/^[\pL\pN_ -]{1,'.$max.'}$/u',$v))throw new RuntimeException('Nombre inválido');return trim($v);}
 function dbname($id){return str_starts_with($id,'db:')?substr($id,3):'visual_'.preg_replace('/[^a-zA-Z0-9_]/','',$id);}
 function databaseKey($label){
  $key=collectionKey($label);
  if(strlen($key)>70)throw new RuntimeException('Nombre de base demasiado largo');
  return $key;
 }
 function renameDatabase($old,$new){
  if($old===$new)return;
  $cfg=json_decode(file_get_contents('/etc/jocarsa/config.json'),true);
  $root=$cfg['instalacion']??null;
  if(!is_string($root)||!str_starts_with($root,'/'))throw new RuntimeException('Para renombrar bases, configura una ruta absoluta en /etc/jocarsa/config.json (instalacion).');
  $root=realpath($root);
  if(!$root||!is_dir($root))throw new RuntimeException('Directorio de bases no disponible');
  foreach([$old,$new] as $v)if(!preg_match('/^[a-z0-9_-]+$/',$v))throw new RuntimeException('Identificador de base inválido');
  $src=$root.'/'.$old;$dst=$root.'/'.$new;
  if(!is_dir($src)||is_link($src))throw new RuntimeException('Base de origen no encontrada');
  if(file_exists($dst))throw new RuntimeException('Ya existe una base llamada '.$new);
  if(!rename($src,$dst))throw new RuntimeException('No se pudo renombrar la carpeta de la base. Revisa permisos de Apache.');
 }

 function saveState(){global $state,$meta;$tmp=$meta.'.tmp';if(file_put_contents($tmp,json_encode($state,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR))===false||!rename($tmp,$meta))throw new RuntimeException('No se pudo guardar el estado');}
 function &project($id){global $state;foreach($state['projects'] as &$p)if($p['id']===$id)return $p;throw new RuntimeException('Proyecto desconocido');}
 function useProject($id){project($id);if(!jocarsabbdd_usar_base(dbname($id)))throw new RuntimeException('No se pudo abrir la base');}
 function entity($p,$id){foreach($p['data']['entities'] as $e)if($e['id']===$id)return $e;throw new RuntimeException('Entidad desconocida');}
 function cols($e){$result=[];foreach($e['properties'] as $prop){$parts=explode(':',$prop['text'],2);if(count($parts)!==2)throw new RuntimeException('Propiedad sin tipo: '.$prop['text']);$name=required(trim($parts[0]));if(in_array(strtolower($name),['activo','__id'],true))throw new RuntimeException('Columna reservada: '.$name);$result[$name]=strtoupper(trim($parts[1]));}return $result;}
 function typeName($t){$t=preg_replace('/\(.*$/','',strtoupper($t));return match($t){'INTEGER','INT','ENTERO','BIGINT'=>'ENTERO','REAL','FLOAT','DOUBLE'=>'REAL','DECIMAL','NUMERIC','NUMERICO'=>'DECIMAL','BOOL','BOOLEAN','BOOLEANO'=>'BOOLEANO','DATE','FECHA'=>'FECHA',default=>'TEXTO'};}
 function collectionKey($label){
  $label=trim((string)$label);
  if($label==='')throw new RuntimeException('Colección sin nombre');
  $map=['ñ'=>'n','Ñ'=>'N','ß'=>'ss','æ'=>'ae','Æ'=>'AE','ø'=>'o','Ø'=>'O'];
  $ascii=strtr($label,$map);
  if(function_exists('transliterator_transliterate'))$ascii=transliterator_transliterate('Any-Latin; Latin-ASCII',$ascii);
  elseif(function_exists('iconv')){$converted=@iconv('UTF-8','ASCII//TRANSLIT//IGNORE',$ascii);if($converted!==false)$ascii=$converted;}
  $ascii=strtolower(preg_replace('/[^a-zA-Z0-9_-]+/','_', $ascii));
  $ascii=trim($ascii,'_-');
  if($ascii==='')throw new RuntimeException('El nombre de colección debe contener alguna letra o número: '.$label);
  return $ascii;
 }
 function syncData($old,$new,$allowDelete=false){
  // El motor es la fuente de verdad. Una tabla descubierta no se debe recrear.
  $tables=jocarsabbdd_listar_tablas();
  $oldEntities=[];foreach(($old['entities']??[]) as $e)$oldEntities[$e['id']]=$e;
  $newNames=[];
  foreach($new['entities'] as $e){
   $name=required($e['title']);
   if(isset($oldEntities[$e['id']])&&$oldEntities[$e['id']]['title']!==$name)throw new RuntimeException('Renombrar tablas existentes requiere migración explícita.');
   if(isset($newNames[$name]))throw new RuntimeException('Tabla duplicada: '.$name);
   $newNames[$name]=true;
   $c=cols($e);
   if(!array_key_exists('id',$c))throw new RuntimeException('Cada entidad necesita id: integer');
   if(in_array($name,$tables,true)){
    $schema=jocarsabbdd_esquema($name);
    foreach($c as $col=>$type){
     if(in_array($col,['id','activo'],true))continue;
     if(!in_array($col,$schema,true)){
      if(!jocarsabbdd_alterar_anadir($name,$col))throw new RuntimeException('No se pudo añadir columna '.$name.'.'.$col);
     }
    }
    // La eliminación de columnas requiere migración explícita; nunca se hace aquí.
   }else{
    $typed=[];foreach($c as $col=>$type)if($col!=='id')$typed[$col]=typeName($type);
    if(!jocarsabbdd_crear_tabla_tipada($name,$typed))throw new RuntimeException('No se pudo crear tabla '.$name.'. Comprueba permisos de escritura en el directorio de la base.');
    $tables[]=$name;
   }
  }
  $removed=[];foreach($oldEntities as $e)if(!isset($newNames[$e['title']])&&in_array($e['title'],$tables,true))$removed[]=$e['title'];
  if($removed&&!$allowDelete)throw new RuntimeException('Eliminar tablas requiere confirmación explícita: '.implode(', ',$removed));
  // Eliminar las tablas solicitadas después de validar las entidades nuevas.
  foreach($removed as $name){if(!jocarsabbdd_eliminar_tabla($name))throw new RuntimeException('No se pudo eliminar tabla '.$name);}
  $desired=[];
  foreach($new['entities'] as $e)foreach(($e['categories']??[]) as $cat){
   $cat=collectionKey($cat);
   $desired[$cat][]=$e['title'];
  }
  $existing=jocarsabbdd_listar_colecciones();
  foreach($desired as $cat=>$wanted){
   if(!in_array($cat,$existing,true)){
    if(!jocarsabbdd_crear_coleccion($cat))throw new RuntimeException('No se pudo crear colección '.$cat);
   }
   $current=jocarsabbdd_tablas_de_coleccion($cat);
   foreach($wanted as $table)if(!in_array($table,$current,true)){
    // API PHP: (tabla, coleccion), no (coleccion, tabla).
    if(!jocarsabbdd_agregar_tabla_coleccion($table,$cat))throw new RuntimeException('No se pudo asociar '.$table.' a '.$cat);
   }
   foreach($current as $table)if(!in_array($table,$wanted,true)){
    if(!jocarsabbdd_quitar_tabla_coleccion($table,$cat))throw new RuntimeException('No se pudo desasociar '.$table.' de '.$cat);
   }
  }
  foreach($existing as $cat)if(!isset($desired[$cat])){
   foreach(jocarsabbdd_tablas_de_coleccion($cat) as $table){
    if(!jocarsabbdd_quitar_tabla_coleccion($table,$cat))throw new RuntimeException('No se pudo desasociar '.$table.' de '.$cat);
   }
  }
  foreach(($new['links']??[]) as $link){
   $from=null;$to=null;
   foreach($new['entities'] as $e){if($e['id']===$link['from'])$from=$e;if($e['id']===$link['to'])$to=$e;}
   if(!$from||!$to)continue;
   $a=null;$b=null;
   foreach($from['properties'] as $prop)if($prop['id']===$link['fromProp'])$a=trim(explode(':',$prop['text'],2)[0]);
   foreach($to['properties'] as $prop)if($prop['id']===$link['toProp'])$b=trim(explode(':',$prop['text'],2)[0]);
   if(strtolower((string)$a)!=='id'||!$b)continue;
   $exists=false;
   foreach(jocarsabbdd_claves_foraneas($to['title']) as $fk)if(($fk['columna']??null)===$b&&($fk['tabla_referenciada']??null)===$from['title'])$exists=true;
   if(!$exists&&!jocarsabbdd_anadir_clave_foranea($to['title'],$b,$from['title'],'id'))throw new RuntimeException('No se pudo crear FK '.$to['title'].'.'.$b);
  }
 }
 // Sincronizar el catálogo visual con TODAS las bases del motor.
 // El motor es la fuente de verdad; projects.json solo conserva posiciones y nombres.
 function discoverDatabases(){
  global $state;
  $names=jocarsabbdd_listar_bases();
  if(!is_array($names))throw new RuntimeException('El conector no devolvió el catálogo de bases');
  $seen=[];
  foreach($names as $name){
   if(!is_string($name)||$name==='')continue;
   $id='db:'.$name;
   $matched=null;
   foreach($state['projects'] as $i=>$p){if(dbname($p['id'])===$name){$matched=$i;break;}}
   if($matched===null){
    $state['projects'][]=['id'=>$id,'name'=>$name,'data'=>['entities'=>[],'links'=>[]],'view'=>['x'=>350,'y'=>110,'z'=>1]];
    $matched=count($state['projects'])-1;
   }
   $seen[$state['projects'][$matched]['id']]=true;
   $p=&$state['projects'][$matched];
   if(!jocarsabbdd_usar_base($name))throw new RuntimeException('No se pudo abrir '.$name);
   $tables=jocarsabbdd_listar_tablas();
   $old=[];
   foreach(($p['data']['entities']??[]) as $e)$old[$e['title']]=$e;
   $entities=[];$links=[];
   foreach($tables as $idx=>$table){
    $schema=jocarsabbdd_esquema($table);
    $types=jocarsabbdd_tipos($table);
    $prior=$old[$table]??null;
    $id=$prior['id']??('table:'.hash('sha256',$name."\0".$table));
    $propIds=[];
    foreach(($prior['properties']??[]) as $pr){$col=trim(explode(':',$pr['text'],2)[0]);$propIds[$col]=$pr['id'];}
    $properties=[];
    foreach($schema as $col){
     if($col==='activo')continue;
     $typ=strtolower((string)($types[$col]??'texto'));
     $properties[]=['id'=>$propIds[$col]??('col:'.hash('sha256',$name."\0".$table."\0".$col)),'text'=>$col.': '.$typ];
    }
    $entities[]=['id'=>$id,'title'=>$table,'properties'=>$properties,
     'categories'=>array_map(fn($cat)=>$p['collectionLabels'][$cat]??$cat,jocarsabbdd_colecciones_de_tabla($table)),
     'x'=>$prior['x']??(100+($idx%4)*330),'y'=>$prior['y']??(100+intdiv($idx,4)*320)];
   }
   $byTitle=[];foreach($entities as $e)$byTitle[$e['title']]=$e;
   foreach($entities as $e){
    foreach(jocarsabbdd_claves_foraneas($e['title']) as $fk){
     $parent=$byTitle[$fk['tabla_referenciada']]??null;if(!$parent)continue;
     $from=null;$to=null;
     foreach($parent['properties'] as $prop)if(explode(':',$prop['text'],2)[0]===$fk['columna_referenciada'])$from=$prop['id'];
     foreach($e['properties'] as $prop)if(explode(':',$prop['text'],2)[0]===$fk['columna'])$to=$prop['id'];
     if($from&&$to)$links[]=['id'=>'fk:'.hash('sha256',$parent['id'].$from.$e['id'].$to),'from'=>$parent['id'],'fromProp'=>$from,'to'=>$e['id'],'toProp'=>$to];
    }
   }
   // Conservar enlaces visuales que aún sean válidos aunque no sean FK físicas.
   $valid=[];foreach($entities as $e)foreach($e['properties'] as $pr)$valid[$e['id']][$pr['id']]=true;
   foreach(($p['data']['links']??[]) as $l){
    if(isset($valid[$l['from']][$l['fromProp']],$valid[$l['to']][$l['toProp']])){
     $key=$l['from'].$l['fromProp'].$l['to'].$l['toProp'];$exists=false;
     foreach($links as $v)if($v['from'].$v['fromProp'].$v['to'].$v['toProp']===$key){$exists=true;break;}
     if(!$exists)$links[]=$l;
    }
   }
   $p['data']=['entities'=>$entities,'links'=>$links];
   unset($p);
  }
  $state['projects']=array_values(array_filter($state['projects'],fn($p)=>isset($seen[$p['id']])));
  if(!in_array($state['activeId'],array_column($state['projects'],'id'),true))$state['activeId']=$state['projects'][0]['id']??null;
  saveState();
 }
 if($action==='state'){discoverDatabases();}
 if(!$state['projects']&&$action==='state'){$id='db:mi_primer_proyecto';if(!jocarsabbdd_crear_base(dbname($id)))throw new RuntimeException('No se pudo crear la base inicial');$state['projects'][]=['id'=>$id,'name'=>'Mi primer proyecto','data'=>['entities'=>[],'links'=>[]],'view'=>['x'=>350,'y'=>110,'z'=>1]];$state['activeId']=$id;saveState();}
 $result=null;
 switch($action){
 case 'state':$result=$state;break;
 case 'create_project':$name=required($input['name']);$id='db:'.databaseKey($name);if(in_array(dbname($id),jocarsabbdd_listar_bases(),true))throw new RuntimeException('Ya existe una base con ese nombre');if(!jocarsabbdd_crear_base(dbname($id)))throw new RuntimeException('No se pudo crear base');$state['projects'][]=['id'=>$id,'name'=>$name,'data'=>['entities'=>[],'links'=>[]],'view'=>['x'=>350,'y'=>110,'z'=>1]];$state['activeId']=$id;saveState();$result=$id;break;
 case 'activate':project($input['project']);$state['activeId']=$input['project'];saveState();$result=true;break;
 case 'rename_project':
  $oldId=$input['project'];$p=&project($oldId);$newName=required($input['name']);
  $oldDb=dbname($oldId);$newDb=databaseKey($newName);$newId='db:'.$newDb;
  if($newDb!==$oldDb){
   foreach($state['projects'] as $other)if($other['id']!==$oldId&&dbname($other['id'])===$newDb)throw new RuntimeException('Ya existe un proyecto con ese nombre');
   renameDatabase($oldDb,$newDb);
   $p['id']=$newId;
   if($state['activeId']===$oldId)$state['activeId']=$newId;
  }
  $p['name']=$newName;saveState();$result=true;break;
 case 'delete_project':if(str_starts_with((string)$input['project'],'db:') && !str_starts_with(dbname($input['project']),'visual_'))throw new RuntimeException('No se permite borrar bases externas desde el gestor visual.');if(count($state['projects'])<=1)throw new RuntimeException('Debe quedar un proyecto');$id=$input['project'];project($id);if(!jocarsabbdd_eliminar_base(dbname($id)))throw new RuntimeException('No se pudo eliminar la base');$state['projects']=array_values(array_filter($state['projects'],fn($p)=>$p['id']!==$id));$state['activeId']=$state['projects'][0]['id'];saveState();$result=true;break;
 case 'save':$p=&project($input['project']);useProject($p['id']);$new=$input['data'];if(!isset($new['entities'],$new['links'])||!is_array($new['entities'])||!is_array($new['links']))throw new RuntimeException('Diagrama inválido');syncData($p['data'],$new,($input['confirm_delete']??false)===true);foreach($new['entities'] as $ent)foreach(($ent['categories']??[]) as $label){$key=collectionKey($label);$p['collectionLabels'][$key]=$label;}foreach($new['entities'] as &$e)unset($e['rows']);unset($e);$p['data']=$new;$p['view']=$input['view']??$p['view'];saveState();$result=true;break;
 case 'rows':case 'add_row':case 'cell':case 'delete_row':$p=project($input['project']);useProject($p['id']);$e=entity($p,$input['entity']);$schema=jocarsabbdd_esquema($e['title']);$props=[];foreach($e['properties'] as $prop){$name=trim(explode(':',$prop['text'])[0]);$props[$prop['id']]=$name;}
  if($action==='rows'){$result=[];foreach(jocarsabbdd_listar($e['title']) as $r){$item=['__id'=>(int)$r['id']];foreach($props as $id=>$name)$item[$id]=$r[$name]??'';$result[]=$item;}break;}
  if($action==='add_row'){$vals=[];foreach(array_slice($schema,2) as $col)$vals[]='';$id=jocarsabbdd_insertar($e['title'],$vals);if($id<0)throw new RuntimeException('No se pudo insertar (comprueba campos obligatorios)');$result=$id;break;}
  $id=(int)$input['row'];if($action==='delete_row'){$result=jocarsabbdd_eliminar($e['title'],$id);if(!$result)throw new RuntimeException('No se pudo eliminar (posible clave foránea)');break;}
  $column=$props[$input['prop']]??null;if(!$column)throw new RuntimeException('Propiedad desconocida');if($column==='id')throw new RuntimeException('ID generado automáticamente');$r=jocarsabbdd_seleccionar($e['title'],$id);if(!$r)throw new RuntimeException('Registro no encontrado');$r[$column]=(string)$input['value'];$vals=[];foreach(array_slice($schema,2) as $col)$vals[]=$r[$col]??'';$result=jocarsabbdd_actualizar($e['title'],$id,$vals);if(!$result)throw new RuntimeException('Valor inválido o restricción incumplida');break;
 default:throw new RuntimeException('Acción desconocida');
 }
 echo json_encode(['ok'=>true,'result'=>$result],JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);
} catch(Throwable $e){http_response_code(400);echo json_encode(['ok'=>false,'error'=>$e->getMessage()],JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE);}
