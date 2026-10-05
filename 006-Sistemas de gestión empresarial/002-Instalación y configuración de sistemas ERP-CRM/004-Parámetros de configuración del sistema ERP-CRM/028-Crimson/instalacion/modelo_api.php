<?php
header("Content-Type: application/json; charset=utf-8");
function out($x,$c=200){http_response_code($c);echo json_encode($x,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);exit;}
function qi($s){return '"'.str_replace('"','""',$s).'"';}
function valid($s){return is_string($s)&&preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/',$s);}
function typ($t){$t=strtoupper(trim((string)$t));return in_array($t,["INTEGER","TEXT","REAL","NUMERIC","BLOB"],true)?$t:"TEXT";}
function normalize($m){
 if(!is_array($m)||!is_array($m["tables"]??null))throw new Exception("Modelo inválido");
 $names=[];$tables=[];
 foreach($m["tables"] as $t){
  $n=$t["name"]??"";if(!valid($n)||isset($names[$n]))throw new Exception("Entidad inválida o duplicada: ".$n);$names[$n]=1;
  $cols=[];$cn=[];
  foreach(($t["columns"]??[]) as $c){$x=$c["name"]??"";if(!valid($x)||isset($cn[$x]))throw new Exception("Campo inválido/duplicado en ".$n.": ".$x);$cn[$x]=1;$cols[]=["name"=>$x,"type"=>typ($c["type"]??"TEXT"),"pk"=>!empty($c["pk"])];}
  if(!$cols)throw new Exception("La entidad ".$n." no tiene campos");
  $tables[]=["name"=>$n,"x"=>(int)($t["x"]??40),"y"=>(int)($t["y"]??40),"columns"=>$cols];
 }
 $rels=[];
 foreach(($m["relations"]??[]) as $r){
  foreach(["fromTable","fromColumn","toTable","toColumn"] as $k)if(!valid($r[$k]??""))throw new Exception("FK inválida");
  $rels[]=["fromTable"=>$r["fromTable"],"fromColumn"=>$r["fromColumn"],"toTable"=>$r["toTable"],"toColumn"=>$r["toColumn"]];
 }
 return["version"=>1,"tables"=>$tables,"relations"=>$rels];
}
function schemaSql($m){
 $sql=["PRAGMA foreign_keys = ON;",""];
 foreach($m["tables"] as $t){
  $defs=[];
  foreach($t["columns"] as $c){$d=qi($c["name"])." ".typ($c["type"]);if($c["pk"])$d.=" PRIMARY KEY".(typ($c["type"])==="INTEGER"?" AUTOINCREMENT":"");$defs[]=$d;}
  foreach($m["relations"] as $r)if($r["fromTable"]===$t["name"])$defs[]="FOREIGN KEY (".qi($r["fromColumn"]).") REFERENCES ".qi($r["toTable"])."(".qi($r["toColumn"]).")";
  $sql[]="CREATE TABLE ".qi($t["name"])." (\n  ".implode(",\n  ",$defs)."\n);";$sql[]="";
 }
 return implode("\n",$sql);
}
try{
 $root=dirname(__DIR__);$body=json_decode(file_get_contents("php://input"),true);$m=normalize($body["model"]??null);$sql=schemaSql($m);
 $action=$_GET["action"]??"preview";
 if($action==="preview")out(["ok"=>true,"sql"=>$sql]);
 if($action!=="install")throw new Exception("Acción desconocida");
 $data=$root."/data";if(!is_dir($data))mkdir($data,0775,true);
 $dbfile=$data."/crimson.sqlite";
 // El instalador siempre crea una base NUEVA. Eliminamos también los
 // ficheros auxiliares SQLite para no reutilizar un esquema anterior.
 foreach([$dbfile,$dbfile."-wal",$dbfile."-shm"] as $f){
   if(file_exists($f) && !@unlink($f)) throw new Exception("No se puede eliminar ".$f.". Comprueba permisos.");
 }
 clearstatcache(true,$dbfile);
 if(file_exists($dbfile)) throw new Exception("No se ha podido vaciar la base de datos anterior");
 $db=new PDO("sqlite:".$dbfile);
 $db->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
 $db->exec("PRAGMA foreign_keys=ON");
 $db->exec($sql);
 $check=$db->query("PRAGMA foreign_key_check")->fetchAll(PDO::FETCH_ASSOC);if($check)throw new Exception("El modelo contiene relaciones FK inválidas");
 file_put_contents(__DIR__."/schema.sql",$sql);
 if(!is_dir($root."/modelo"))mkdir($root."/modelo",0775,true);
 file_put_contents($root."/modelo/schema.json",json_encode($m,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
 out(["ok"=>true,"tables"=>count($m["tables"]),"relations"=>count($m["relations"])]);
}catch(Throwable $e){out(["ok"=>false,"error"=>$e->getMessage()],500);}
