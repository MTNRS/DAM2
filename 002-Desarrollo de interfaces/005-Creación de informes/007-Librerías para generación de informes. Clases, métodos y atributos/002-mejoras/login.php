<?php
require_once __DIR__.'/inc/core.php';
if(me()){header('Location: index.php');exit;}
$error='';if($_SERVER['REQUEST_METHOD']==='POST'){
    try{
        verify_csrf();$q=db()->prepare('SELECT * FROM usuarios WHERE usuario=? AND activo=1');$q->execute([(string)($_POST['usuario']??'')]);$u=$q->fetch();
        if(!$u||!password_verify((string)($_POST['password']??''),$u['password_hash']))throw new RuntimeException('Credenciales incorrectas.');
        session_regenerate_id(true);$_SESSION['uid']=$u['id'];header('Location: index.php');exit;
    }catch(Throwable $e){$error=$e->getMessage();}
}
?><!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Acceso · jocarsa | informes</title><link rel="stylesheet" href="assets/jocarsa-iu.css"><link rel="stylesheet" href="assets/app.css"></head><body class="jocarsa-iu login"><main class="login-card"><h1>jocarsa | informes</h1><p>Acceso al panel de administración</p><?php if($error):?><p class="error"><?=h($error)?></p><?php endif;?><form method="post"><input type="hidden" name="csrf" value="<?=h(csrf())?>"><label>Usuario<input name="usuario" required autocomplete="username"></label><label>Contraseña<input type="password" name="password" required autocomplete="current-password"></label><button>Entrar</button></form></main></body></html>
