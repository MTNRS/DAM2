<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
require_once __DIR__.'/auth_common.php';

function out($x,$c=200){
    http_response_code($c);
    echo json_encode($x,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
    exit;
}

try{
    $body=json_decode(file_get_contents('php://input'),true);
    if(!is_array($body))$body=[];

    // Admitimos action por query string, formulario o JSON.
    $action=$_GET['action']??$_POST['action']??$body['action']??'me';

    // Estado público: lo usa login.php antes de intentar autenticar.
    if($action==='status'){
        $installed=JocarsaDB::installed();
        $u=$installed?crimsonUser():null;
        out([
            'ok'=>true,
            'installed'=>$installed,
            'authenticated'=>$u!==null,
            'user'=>$u ? array_diff_key($u,['password_hash'=>true]) : null
        ]);
    }

    if($action==='login'){
        if(!JocarsaDB::installed())out(['ok'=>false,'error'=>'Crimson no está instalado'],409);
        $username=trim((string)($body['username']??$_POST['username']??''));
        $password=(string)($body['password']??$_POST['password']??'');
        $rows=crimsonDb()->search('__crimson_users','username',$username);
        $u=$rows[0]??null;
        if(!$u||(int)($u['activo']??0)!==1||!password_verify($password,$u['password_hash']??'')){
            out(['ok'=>false,'error'=>'Usuario o contraseña incorrectos'],401);
        }
        session_regenerate_id(true);
        $_SESSION['crimson_user_id']=(int)$u['Identificador'];
        unset($u['password_hash']);
        out(['ok'=>true,'user'=>$u]);
    }

    if($action==='logout'){
        $_SESSION=[];
        if(session_status()===PHP_SESSION_ACTIVE)session_destroy();
        out(['ok'=>true]);
    }

    if($action==='me'){
        $u=requireLogin(true);
        out(['ok'=>true,'user'=>$u,'modules'=>userModules($u)]);
    }

    if($action==='update_profile'){
        $u=requireLogin(true);
        $d=[
            'nombre'=>trim((string)($body['nombre']??$u['nombre'])),
            'email'=>trim((string)($body['email']??($u['email']??'')))
        ];
        if(!empty($body['password']))$d['password_hash']=password_hash($body['password'],PASSWORD_DEFAULT);
        crimsonDb()->update('__crimson_users',$u['Identificador'],$d);
        out(['ok'=>true]);
    }

    out(['ok'=>false,'error'=>'Acción desconocida'],400);
}catch(Throwable $e){
    out(['ok'=>false,'error'=>$e->getMessage()],500);
}
