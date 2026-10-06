<?php
header("Content-Type: application/json; charset=utf-8");
header("Cache-Control: no-store");
require_once __DIR__."/auth_common.php";
$action=$_GET["action"]??"status";
$basededatos = dirname(__DIR__)."/data/crimson.sqlite";

if(!file_exists($basededatos)){
    if($action==="status"){
        echo json_encode(["ok"=>true,"installed"=>false,"authenticated"=>false,"user"=>null],JSON_UNESCAPED_UNICODE);
        exit;
    }
    http_response_code(409);
    echo json_encode(["error"=>"Crimson no está instalado","installed"=>false],JSON_UNESCAPED_UNICODE);
    exit;
}

try{
 if($action==="status"){
   $u=crimsonUser();
   echo json_encode(["ok"=>true,"installed"=>true,"authenticated"=>(bool)$u,"user"=>$u],JSON_UNESCAPED_UNICODE);exit;
 }
 if($action==="login"){
   $body=json_decode(file_get_contents("php://input"),true)?:[];
   $db=crimsonDb();$q=$db->prepare("SELECT * FROM __crimson_users WHERE username=? AND activo=1");$q->execute([trim($body["username"]??"")]);$u=$q->fetch(PDO::FETCH_ASSOC);
   if(!$u || !password_verify($body["password"]??"",$u["password_hash"])){http_response_code(401);echo json_encode(["error"=>"Usuario o contraseña incorrectos"]);exit;}
   session_regenerate_id(true);$_SESSION["crimson_user_id"]=$u["Identificador"];
   echo json_encode(["ok"=>true]);exit;
 }
 if($action==="profile"){
   $u=requireLogin(true);
   echo json_encode(["ok"=>true,"user"=>$u],JSON_UNESCAPED_UNICODE);exit;
 }
 if($action==="update_profile"){
   $u=requireLogin(true);
   $body=json_decode(file_get_contents("php://input"),true)?:[];
   $nombre=trim($body["nombre"]??"");
   $email=trim($body["email"]??"");
   $password=$body["password"]??"";
   if($nombre==="")throw new Exception("El nombre es obligatorio");
   $db=crimsonDb();
   if($password!==""){
     if(strlen($password)<6)throw new Exception("La contraseña debe tener al menos 6 caracteres");
     $q=$db->prepare("UPDATE __crimson_users SET nombre=?,email=?,password_hash=? WHERE Identificador=?");
     $q->execute([$nombre,$email,password_hash($password,PASSWORD_DEFAULT),$u["Identificador"]]);
   }else{
     $q=$db->prepare("UPDATE __crimson_users SET nombre=?,email=? WHERE Identificador=?");
     $q->execute([$nombre,$email,$u["Identificador"]]);
   }
   echo json_encode(["ok"=>true]);exit;
 }
 if($action==="logout"){session_destroy();echo json_encode(["ok"=>true]);exit;}
 throw new Exception("Acción no válida");
}catch(Throwable $e){http_response_code(500);echo json_encode(["error"=>$e->getMessage()]);}
?>