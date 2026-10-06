<?php
header("Content-Type: application/json; charset=utf-8");header("Cache-Control: no-store");
require_once __DIR__."/auth_common.php";$me=requireAdmin(true);$db=crimsonDb();
function out($x,$c=200){http_response_code($c);echo json_encode($x,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);exit;}
try{
 $action=$_GET["action"]??"list";$body=json_decode(file_get_contents("php://input"),true)?:[];
 if($action==="list"){
   $users=$db->query("SELECT Identificador,username,nombre,email,rol,activo,creado_en FROM __crimson_users ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
   $mods=[];$root=dirname(__DIR__)."/modulos";
   foreach(scandir($root) as $id){if($id==="."||$id==="..")continue;$m=moduleConfig($id);if($m)$mods[]=["modulo"=>$id,"nombre"=>$m["nombre"]??ucfirst($id)];}
   usort($mods,fn($a,$b)=>strcasecmp($a["nombre"],$b["nombre"]));
   $perms=$db->query("SELECT * FROM __crimson_permissions")->fetchAll(PDO::FETCH_ASSOC);
   out(["ok"=>true,"users"=>$users,"modules"=>$mods,"permissions"=>$perms,"me"=>$me["Identificador"]]);
 }
 if($action==="save"){
   $id=(int)($body["Identificador"]??0);$username=trim($body["username"]??"");$nombre=trim($body["nombre"]??"");$email=trim($body["email"]??"");$rol=($body["rol"]??"usuario")==="admin"?"admin":"usuario";$activo=!empty($body["activo"])?1:0;$password=$body["password"]??"";
   if($username===""||$nombre==="")throw new Exception("Nombre y usuario son obligatorios");
   if($id){
     if($id===(int)$me["Identificador"]){$rol="admin";$activo=1;}
     if($password!==""){$q=$db->prepare("UPDATE __crimson_users SET username=?,nombre=?,email=?,rol=?,activo=?,password_hash=? WHERE Identificador=?");$q->execute([$username,$nombre,$email,$rol,$activo,password_hash($password,PASSWORD_DEFAULT),$id]);}
     else{$q=$db->prepare("UPDATE __crimson_users SET username=?,nombre=?,email=?,rol=?,activo=? WHERE Identificador=?");$q->execute([$username,$nombre,$email,$rol,$activo,$id]);}
   }else{
     if(strlen($password)<6)throw new Exception("La contraseña debe tener al menos 6 caracteres");
     $q=$db->prepare("INSERT INTO __crimson_users(username,password_hash,nombre,email,rol,activo) VALUES(?,?,?,?,?,?)");$q->execute([$username,password_hash($password,PASSWORD_DEFAULT),$nombre,$email,$rol,$activo]);$id=(int)$db->lastInsertId();
   }
   out(["ok"=>true,"Identificador"=>$id]);
 }
 if($action==="delete"){
   $id=(int)($body["Identificador"]??0);if($id===(int)$me["Identificador"])throw new Exception("No puedes eliminar tu propio usuario administrador");
   $q=$db->prepare("DELETE FROM __crimson_users WHERE Identificador=?");$q->execute([$id]);out(["ok"=>true]);
 }
 if($action==="permissions"){
   $uid=(int)($body["usuario_id"]??0);$rows=$body["permissions"]??[];
   $db->beginTransaction();$q=$db->prepare("DELETE FROM __crimson_permissions WHERE usuario_id=?");$q->execute([$uid]);
   $ins=$db->prepare("INSERT INTO __crimson_permissions(usuario_id,modulo,puede_seleccionar,puede_insertar,puede_actualizar,puede_eliminar) VALUES(?,?,?,?,?,?)");
   foreach($rows as $r){if(!preg_match('/^[a-zA-Z0-9_-]+$/',$r["modulo"]??""))continue;$ins->execute([$uid,$r["modulo"],!empty($r["seleccionar"])?1:0,!empty($r["insertar"])?1:0,!empty($r["actualizar"])?1:0,!empty($r["eliminar"])?1:0]);}
   $db->commit();out(["ok"=>true]);
 }
 throw new Exception("Acción desconocida");
}catch(Throwable $e){if($db->inTransaction())$db->rollBack();out(["ok"=>false,"error"=>$e->getMessage()],500);}
?>