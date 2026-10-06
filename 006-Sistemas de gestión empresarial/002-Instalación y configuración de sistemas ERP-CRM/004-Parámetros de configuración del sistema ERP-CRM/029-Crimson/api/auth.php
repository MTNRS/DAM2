<?php
header("Content-Type: application/json; charset=utf-8");
header("Cache-Control: no-store");
require_once __DIR__."/auth_common.php";
$action=$_GET["action"]??"status";
try{
 if($action==="status"){
   $u=crimsonUser();
   echo json_encode(["ok"=>true,"authenticated"=>(bool)$u,"user"=>$u],JSON_UNESCAPED_UNICODE);exit;
 }
 if($action==="login"){
   $body=json_decode(file_get_contents("php://input"),true)?:[];
   $db=crimsonDb();$q=$db->prepare("SELECT * FROM __crimson_users WHERE username=? AND activo=1");$q->execute([trim($body["username"]??"")]);$u=$q->fetch(PDO::FETCH_ASSOC);
   if(!$u || !password_verify($body["password"]??"",$u["password_hash"])){http_response_code(401);echo json_encode(["error"=>"Usuario o contraseña incorrectos"]);exit;}
   session_regenerate_id(true);$_SESSION["crimson_user_id"]=$u["Identificador"];
   echo json_encode(["ok"=>true]);exit;
 }
 if($action==="logout"){session_destroy();echo json_encode(["ok"=>true]);exit;}
 throw new Exception("Acción no válida");
}catch(Throwable $e){http_response_code(500);echo json_encode(["error"=>$e->getMessage()]);}
?>