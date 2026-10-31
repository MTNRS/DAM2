<?php
require_once __DIR__."/nucleo/JocarsaDB.php";
if(!JocarsaDB::installed()){header("Location: instalacion/instalar.php");exit;}
?>
<!doctype html><html lang="es"><head><link rel="icon" type="image/svg+xml" href="https://static.jocarsa.com/logos/crimson.svg"><meta charset="utf-8"><title>Acceso · Crimson</title>
<style>
:root{--c:crimson}*{box-sizing:border-box}body{margin:0;min-height:100vh;display:grid;place-items:center;background:#f3f4f5;font-family:Ubuntu,Arial,sans-serif;color:#30343a}.login{width:360px;background:white;border:1px solid #ddd;padding:26px;box-shadow:0 10px 30px #0001}.brand{color:var(--c);font-weight:700;font-size:20px;margin-bottom:24px}.login label{display:block;margin:12px 0 5px}.login input{width:100%;padding:11px;border:1px solid #cfd2d5;font:inherit}.login button{width:100%;margin-top:18px;padding:11px;border:0;background:var(--c);color:white;font:inherit;cursor:pointer}.error{min-height:20px;color:#a00;margin-top:10px;font-size:13px}
</style></head><body><form class="login" id="login"><div class="brand">jocarsa | crimson</div><h1>Acceso</h1><label>Usuario</label><input id="username" autocomplete="username" required autofocus><label>Contraseña</label><input id="password" type="password" autocomplete="current-password" required><button>Entrar</button><div class="error" id="error"></div></form>
<script>
fetch("api/auth.php?action=status",{cache:"no-store"})
.then(r=>r.json())
.then(d=>{
  if(d.installed===false){location.href="instalacion/instalar.php";return}
  if(d.authenticated)location.href="index.html";
});
login.onsubmit=async e=>{e.preventDefault();error.textContent="";const r=await fetch("api/auth.php?action=login",{method:"POST",headers:{"Content-Type":"application/json"},body:JSON.stringify({username:username.value,password:password.value})});const d=await r.json();if(!r.ok){error.textContent=d.error||"Error";return}location.href="index.html"};
</script></body></html>
