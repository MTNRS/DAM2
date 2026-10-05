<?php
if(session_status() !== PHP_SESSION_ACTIVE) session_start();

function crimsonDb(){
    static $db = null;
    if($db === null){
        $file = dirname(__DIR__)."/data/crimson.sqlite";
        if(!file_exists($file)) throw new Exception("Crimson no está instalado");
        $db = new PDO("sqlite:".$file);
        $db->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
        $db->exec("PRAGMA foreign_keys=ON");
    }
    return $db;
}
function crimsonUser(){
    if(empty($_SESSION["crimson_user_id"])) return null;
    $db=crimsonDb();
    $q=$db->prepare("SELECT Identificador,username,nombre,email,rol,activo FROM __crimson_users WHERE Identificador=?");
    $q->execute([$_SESSION["crimson_user_id"]]);
    $u=$q->fetch(PDO::FETCH_ASSOC);
    return ($u && (int)$u["activo"]===1)?$u:null;
}
function requireLogin($json=true){
    $u=crimsonUser();
    if(!$u){
        if($json){http_response_code(401);echo json_encode(["error"=>"No autenticado"]);exit;}
        header("Location: login.php");exit;
    }
    return $u;
}
function requireAdmin($json=true){
    $u=requireLogin($json);
    if($u["rol"]!=="admin"){
        if($json){http_response_code(403);echo json_encode(["error"=>"Acceso reservado al administrador"]);exit;}
        http_response_code(403);echo "Acceso reservado al administrador";exit;
    }
    return $u;
}
function moduleConfig($id){
    if(!preg_match('/^[a-zA-Z0-9_-]+$/',$id)) return null;
    $f=dirname(__DIR__)."/modulos/".$id."/modulo.json";
    if(!is_file($f)) return null;
    $m=json_decode(file_get_contents($f),true);
    return is_array($m)?$m:null;
}
function userModules($user){
    $root=dirname(__DIR__)."/modulos";$out=[];
    foreach(scandir($root) as $id){
        if($id==="."||$id==="..")continue;
        $m=moduleConfig($id);if(!$m)continue;
        if($user["rol"]==="admin"){
            $out[]=["modulo"=>$m["modulo"]??$id,"nombre"=>$m["nombre"]??ucfirst($id)];
            continue;
        }
        $db=crimsonDb();
        $q=$db->prepare("SELECT 1 FROM __crimson_permissions WHERE usuario_id=? AND modulo=? AND (puede_seleccionar=1 OR puede_insertar=1 OR puede_actualizar=1 OR puede_eliminar=1)");
        $q->execute([$user["Identificador"],$id]);
        if($q->fetchColumn())$out[]=["modulo"=>$m["modulo"]??$id,"nombre"=>$m["nombre"]??ucfirst($id)];
    }
    usort($out,fn($a,$b)=>strcasecmp($a["nombre"],$b["nombre"]));
    return $out;
}
function permissionForTable($user,$table,$operation){
    if($user["rol"]==="admin") return true;
    $col=[
      "seleccionar"=>"puede_seleccionar","insertar"=>"puede_insertar",
      "actualizar"=>"puede_actualizar","eliminar"=>"puede_eliminar"
    ][$operation]??null;
    if(!$col)return false;
    $db=crimsonDb();$root=dirname(__DIR__)."/modulos";
    foreach(scandir($root) as $id){
        if($id==="."||$id==="..")continue;
        $m=moduleConfig($id);if(!$m||!in_array($table,$m["tablas"]??[],true))continue;
        $q=$db->prepare("SELECT ".$col." FROM __crimson_permissions WHERE usuario_id=? AND modulo=?");
        $q->execute([$user["Identificador"],$id]);
        if((int)$q->fetchColumn()===1)return true;
    }
    return false;
}
?>