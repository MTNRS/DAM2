<?php
header("Content-Type: application/json; charset=utf-8");
header("Cache-Control: no-store");
function out($x,$c=200){http_response_code($c);echo json_encode($x,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);exit;}
function slug($s){$s=trim(mb_strtolower($s,"UTF-8"));$s=iconv("UTF-8","ASCII//TRANSLIT//IGNORE",$s);$s=preg_replace('/[^a-z0-9_-]+/','-',$s);return trim($s,'-');}
try{
 $root=dirname(__DIR__);$mods=$root."/modulos";$dbfile=$root."/data/crimson.sqlite";
 if(!is_dir($mods))mkdir($mods,0775,true);
 if(!file_exists($dbfile))throw new Exception("No existe crimson.sqlite");
 $db=new PDO("sqlite:".$dbfile);$db->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
 $action=$_GET["action"]??"list";
 if($action==="list"){
   $tables=$db->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' AND name NOT LIKE '__crimson_%' ORDER BY name")->fetchAll(PDO::FETCH_COLUMN);
   $modules=[];
   foreach(scandir($mods) as $dir){
     if($dir==="."||$dir==="..")continue;$file=$mods."/".$dir."/modulo.json";if(!is_file($file))continue;
     $m=json_decode(file_get_contents($file),true);if(!is_array($m))continue;
     $modules[]=["modulo"=>$m["modulo"]??$dir,"nombre"=>$m["nombre"]??ucfirst($dir),"tablas"=>array_values($m["tablas"]??[])];
   }
   usort($modules,fn($a,$b)=>strcasecmp($a["nombre"],$b["nombre"]));
   out(["ok"=>true,"modules"=>$modules,"tables"=>$tables]);
 }
 $body=json_decode(file_get_contents("php://input"),true)?:[];
 if($action==="create"){
   $nombre=trim($body["nombre"]??"");$id=slug($body["modulo"]??$nombre);
   if($nombre===""||$id==="")throw new Exception("Indica un nombre válido");
   if(!preg_match('/^[a-z0-9_-]+$/',$id))throw new Exception("Identificador de módulo no válido");
   $dir=$mods."/".$id;if(file_exists($dir))throw new Exception("El módulo ya existe");
   if(!mkdir($dir,0775,true))throw new Exception("No se ha podido crear la carpeta del módulo");
   file_put_contents($dir."/modulo.json",json_encode(["modulo"=>$id,"nombre"=>$nombre,"tablas"=>[]],JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
   out(["ok"=>true,"modulo"=>$id]);
 }
 if($action==="delete"){
   $id=$body["modulo"]??"";if(!preg_match('/^[a-z0-9_-]+$/',$id))throw new Exception("Módulo no válido");
   $dir=$mods."/".$id;$file=$dir."/modulo.json";if(!is_file($file))throw new Exception("El módulo no existe");
   unlink($file);
   foreach(array_diff(scandir($dir),[".",".."]) as $x){$f=$dir."/".$x;if(is_file($f))unlink($f);}
   if(!rmdir($dir))throw new Exception("No se ha podido eliminar la carpeta del módulo");
   out(["ok"=>true]);
 }
 if($action==="save"){
   $id=$body["modulo"]??"";$tables=$body["tablas"]??[];
   if(!preg_match('/^[a-z0-9_-]+$/',$id)||!is_array($tables))throw new Exception("Datos no válidos");
   $file=$mods."/".$id."/modulo.json";if(!is_file($file))throw new Exception("El módulo no existe");
   $m=json_decode(file_get_contents($file),true);
   $real=$db->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'")->fetchAll(PDO::FETCH_COLUMN);
   $tables=array_values(array_unique(array_intersect($tables,$real)));
   $m["tablas"]=$tables;
   file_put_contents($file,json_encode($m,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
   out(["ok"=>true,"tablas"=>$tables]);
 }
 throw new Exception("Acción desconocida");
}catch(Throwable $e){out(["ok"=>false,"error"=>$e->getMessage()],500);}
