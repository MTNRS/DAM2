<?php
header("Content-Type: application/json; charset=utf-8");
header("Cache-Control: no-store");

function out($x,$code=200){ http_response_code($code); echo json_encode($x,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES); exit; }
function qi($s){ return '"'.str_replace('"','""',$s).'"'; }
function validName($s){ return is_string($s) && preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/',$s); }
function typeSql($t){
  $t=strtoupper(trim((string)$t));
  $allowed=["INTEGER","TEXT","REAL","BLOB","NUMERIC"];
  return in_array($t,$allowed,true)?$t:"TEXT";
}
function readActual(PDO $db){
  $names=$db->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' AND name NOT LIKE '__crimson_%' ORDER BY name")->fetchAll(PDO::FETCH_COLUMN);
  $tables=[]; $rels=[];
  foreach($names as $i=>$name){
    $cols=$db->query("PRAGMA table_info(".qi($name).")")->fetchAll(PDO::FETCH_ASSOC);
    $fks=$db->query("PRAGMA foreign_key_list(".qi($name).")")->fetchAll(PDO::FETCH_ASSOC);
    $tables[]=["name"=>$name,"x"=>40+($i%7)*260,"y"=>40+intdiv($i,7)*250,"columns"=>array_map(fn($c)=>[
      "name"=>$c["name"],"type"=>strtoupper($c["type"]?:"TEXT"),"pk"=>(bool)$c["pk"]
    ],$cols)];
    foreach($fks as $fk) $rels[]=["fromTable"=>$name,"fromColumn"=>$fk["from"],"toTable"=>$fk["table"],"toColumn"=>$fk["to"]?:"Identificador"];
  }
  return ["version"=>1,"tables"=>$tables,"relations"=>$rels];
}
function normalizeModel($m){
  if(!is_array($m) || !isset($m["tables"]) || !is_array($m["tables"])) throw new Exception("Modelo no válido");
  $seen=[]; $tables=[];
  foreach($m["tables"] as $t){
    $name=$t["name"]??"";
    if(!validName($name)) throw new Exception("Nombre de tabla no válido: ".$name);
    if(isset($seen[$name])) throw new Exception("Tabla duplicada: ".$name);
    $seen[$name]=1; $cols=[]; $seenCols=[];
    foreach(($t["columns"]??[]) as $c){
      $cn=$c["name"]??"";
      if(!validName($cn)) throw new Exception("Campo no válido en ".$name.": ".$cn);
      if(isset($seenCols[$cn])) throw new Exception("Campo duplicado ".$name.".".$cn);
      $seenCols[$cn]=1;
      $cols[]=["name"=>$cn,"type"=>typeSql($c["type"]??"TEXT"),"pk"=>!empty($c["pk"])];
    }
    if(!$cols) throw new Exception("La tabla ".$name." debe tener al menos un campo");
    $tables[]=["name"=>$name,"x"=>(int)($t["x"]??40),"y"=>(int)($t["y"]??40),"columns"=>$cols];
  }
  $rels=[];
  foreach(($m["relations"]??[]) as $r){
    foreach(["fromTable","fromColumn","toTable","toColumn"] as $k) if(!validName($r[$k]??"")) throw new Exception("Relación FK no válida");
    $rels[]=["fromTable"=>$r["fromTable"],"fromColumn"=>$r["fromColumn"],"toTable"=>$r["toTable"],"toColumn"=>$r["toColumn"]];
  }
  return ["version"=>1,"tables"=>$tables,"relations"=>$rels];
}
function createTableSql($table,$relations,$nameOverride=null){
  $name=$nameOverride?:$table["name"]; $defs=[];
  foreach($table["columns"] as $c){
    $d=qi($c["name"])." ".typeSql($c["type"]);
    if($c["pk"]) $d.=" PRIMARY KEY".(typeSql($c["type"])==="INTEGER"?" AUTOINCREMENT":"");
    $defs[]=$d;
  }
  foreach($relations as $r) if($r["fromTable"]===$table["name"]){
    $defs[]="FOREIGN KEY (".qi($r["fromColumn"]).") REFERENCES ".qi($r["toTable"])."(".qi($r["toColumn"]).")";
  }
  return "CREATE TABLE ".qi($name)." (\n  ".implode(",\n  ",$defs)."\n)";
}
function sigTable($t,$rels){
  $rr=array_values(array_filter($rels,fn($r)=>$r["fromTable"]===$t["name"]));
  usort($rr,fn($a,$b)=>strcmp(json_encode($a),json_encode($b)));
  return json_encode([$t["columns"],$rr]);
}
function migrationSql($actual,$desired){
  $A=[];$D=[]; foreach($actual["tables"] as $t)$A[$t["name"]]=$t; foreach($desired["tables"] as $t)$D[$t["name"]]=$t;
  $lines=["PRAGMA foreign_keys = OFF;","BEGIN IMMEDIATE;"];
  foreach($D as $name=>$t){
    if(!isset($A[$name])){ $lines[]=createTableSql($t,$desired["relations"]).";"; continue; }
    if(sigTable($A[$name],$actual["relations"])===sigTable($t,$desired["relations"])) continue;
    $tmp="__new_".$name;
    $lines[]="DROP TABLE IF EXISTS ".qi($tmp).";";
    $lines[]=createTableSql($t,$desired["relations"],$tmp).";";
    $oldCols=array_column($A[$name]["columns"],"name"); $newCols=array_column($t["columns"],"name");
    $common=array_values(array_intersect($oldCols,$newCols));
    if($common){
      $q=implode(",",array_map("qi",$common));
      $lines[]="INSERT INTO ".qi($tmp)." (".$q.") SELECT ".$q." FROM ".qi($name).";";
    }
    $lines[]="DROP TABLE ".qi($name).";";
    $lines[]="ALTER TABLE ".qi($tmp)." RENAME TO ".qi($name).";";
  }
  foreach($A as $name=>$t) if(!isset($D[$name])) $lines[]="DROP TABLE ".qi($name).";";
  $lines[]="COMMIT;"; $lines[]="PRAGMA foreign_keys = ON;";
  return implode("\n",$lines)."\n";
}

try{
  $root=dirname(__DIR__); $dbFile=$root."/data/crimson.sqlite";
  if(!file_exists($dbFile)) throw new Exception("No existe crimson.sqlite");
  $db=new PDO("sqlite:".$dbFile); $db->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION); $db->exec("PRAGMA foreign_keys=ON");
  $action=$_GET["action"]??"get";

  if($action==="get"){
    $file=$root."/modelo/schema.json";
    $model=file_exists($file)?json_decode(file_get_contents($file),true):null;
    $actual=readActual($db);
    if(!$model || !is_array($model)) $model=$actual;
    out(["ok"=>true,"model"=>$model,"actual"=>$actual]);
  }
  if($action==="preview" || $action==="apply"){
    $body=json_decode(file_get_contents("php://input"),true);
    $desired=normalizeModel($body["model"]??null);
    $actual=readActual($db);
    $sql=migrationSql($actual,$desired);
    if($action==="preview") out(["ok"=>true,"sql"=>$sql]);

    $stamp=date("Ymd_His");
    $backup=$root."/data/crimson-backup-".$stamp.".sqlite";
    if(!copy($dbFile,$backup)) throw new Exception("No se ha podido crear backup antes de migrar");
    $mig=$root."/instalacion/migraciones/".$stamp.".sql";
    file_put_contents($mig,$sql);

    try{
      $db->exec($sql);
      $check=$db->query("PRAGMA foreign_key_check")->fetchAll(PDO::FETCH_ASSOC);
      if($check) throw new Exception("La migración deja ".count($check)." violaciones de FK");
      file_put_contents($root."/modelo/schema.json",json_encode($desired,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
      out(["ok"=>true,"migration"=>basename($mig),"backup"=>basename($backup),"sql"=>$sql]);
    }catch(Throwable $e){
      // Restore backup atomically after closing PDO.
      $db=null;
      copy($backup,$dbFile);
      throw new Exception("Migración revertida: ".$e->getMessage());
    }
  }
  throw new Exception("Acción desconocida");
}catch(Throwable $e){ out(["ok"=>false,"error"=>$e->getMessage()],500); }
