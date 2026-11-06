<?php
if(session_status()!==PHP_SESSION_ACTIVE)session_start();
require_once dirname(__DIR__).'/nucleo/JocarsaDB.php';
function crimsonDb(bool $required=true): JocarsaDB{return JocarsaDB::instance($required);}
function crimsonUser(){if(empty($_SESSION['crimson_user_id'])||!JocarsaDB::installed())return null;$u=crimsonDb()->find('crimson_users',(int)$_SESSION['crimson_user_id']);return($u&&(int)($u['activo']??0)===1)?$u:null;}
function requireLogin($json=true){$u=crimsonUser();if(!$u){if($json){http_response_code(401);echo json_encode(['error'=>'No autenticado']);exit;}header('Location: ' . rtrim(dirname($_SERVER['SCRIPT_NAME'], 2), '/') . '/io/login.php');exit;}return $u;}
function requireAdmin($json=true){$u=requireLogin($json);if(($u['rol']??'')!=='admin'){if($json){http_response_code(403);echo json_encode(['error'=>'Acceso reservado al administrador']);exit;}http_response_code(403);echo 'Acceso reservado al administrador';exit;}return $u;}
function moduleConfig($id){
    $db=crimsonDb();
    if(!in_array($id,$db->collections(),true))return null;
    return ['modulo'=>$id,'nombre'=>ucfirst(str_replace('_',' ',$id)),'tablas'=>$db->collectionTables($id)];
}
function userModules($user){
    $db=crimsonDb();$out=[];
    $perms=($user['rol']??'')==='admin'?[]:$db->search('crimson_permissions','usuario_id',$user['Identificador']);
    foreach($db->collections() as $id){
        if(($user['rol']??'')!=='admin'){
            $allowed=false;
            foreach($perms as $p)if(($p['modulo']??'')===$id && ((int)($p['puede_seleccionar']??0)+(int)($p['puede_insertar']??0)+(int)($p['puede_actualizar']??0)+(int)($p['puede_eliminar']??0))>0)$allowed=true;
            if(!$allowed)continue;
        }
        $out[]=['modulo'=>$id,'nombre'=>ucfirst(str_replace('_',' ',$id))];
    }
    usort($out,fn($a,$b)=>strcasecmp($a['nombre'],$b['nombre']));return $out;
}
function permissionForTable($user,$table,$operation){
    $db=crimsonDb();
    if(!$db->tableExists($table)||in_array($table,['crimson_users','crimson_permissions'],true))return false;
    if(($user['rol']??'')==='admin')return true;
    $field=['seleccionar'=>'puede_seleccionar','insertar'=>'puede_insertar','actualizar'=>'puede_actualizar','eliminar'=>'puede_eliminar'][$operation]??null;
    if(!$field)return false;
    $perms=$db->search('crimson_permissions','usuario_id',$user['Identificador']);
    $departments=$db->tableCollections($table);
    if(function_exists('jocarsabbdd_arbol_colecciones')){
        $raw=jocarsabbdd_arbol_colecciones();$tree=is_string($raw)?json_decode($raw,true):$raw;
        if(is_array($tree))foreach($departments as $set){
            $seen=[];$parent=$tree[$set]['parent']??null;
            while(is_string($parent)&&isset($tree[$parent])&&!isset($seen[$parent])){
                $seen[$parent]=true;$departments[]=$parent;$parent=$tree[$parent]['parent']??null;
            }
        }
    }
    $departments=array_unique($departments);
    foreach($perms as $p)if(in_array($p['modulo']??'', $departments,true)&&(int)($p[$field]??0)===1)return true;
    return false;
}
