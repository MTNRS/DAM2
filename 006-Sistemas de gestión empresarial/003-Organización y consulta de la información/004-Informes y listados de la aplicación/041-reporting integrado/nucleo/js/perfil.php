<?php require_once __DIR__."/api/auth_common.php"; $u=requireLogin(false); ?>
<!doctype html><html lang="es"><head><link rel="icon" type="image/svg+xml" href="https://static.jocarsa.com/logos/crimson.svg"><meta charset="utf-8"><title>Mi perfil · Crimson</title>
<style>
:root{--c:crimson}*{box-sizing:border-box}body{margin:0;background:#f5f6f7;font-family:Ubuntu,Arial,sans-serif;color:#30343a}.top{height:60px;background:var(--c);color:white;display:flex;align-items:center;padding:10px 16px}.top img{width:38px;height:38px;margin-right:10px}.top .spacer{flex:1}.top a{background:white;color:var(--c);padding:9px 13px;text-decoration:none}.card{max-width:720px;margin:28px auto;background:white;border:1px solid #ddd;padding:24px}.grid{display:grid;grid-template-columns:1fr 1fr;gap:14px}.grid label{display:flex;flex-direction:column;gap:5px}.grid input{padding:10px;border:1px solid #ccc;font:inherit}.full{grid-column:1/-1}.actions{margin-top:18px}.actions button{padding:10px 16px;border:0;background:var(--c);color:white;font:inherit;cursor:pointer}.msg{margin-top:12px;color:#666}
</style></head><body>
<header class="top"><img src="https://static.jocarsa.com/logos/crimson.svg"><strong>jocarsa | crimson · mi perfil</strong><span class="spacer"></span><a href="index.html">Volver al ERP</a></header>
<main class="card"><h1>Mis datos</h1><div class="grid">
<label>Usuario<input value="<?=htmlspecialchars($u["username"])?>" disabled></label>
<label>Rol<input value="<?=htmlspecialchars($u["rol"])?>" disabled></label>
<label>Nombre<input id="nombre" value="<?=htmlspecialchars($u["nombre"])?>"></label>
<label>Email<input id="email" type="email" value="<?=htmlspecialchars($u["email"]??"")?>"></label>
<label class="full">Nueva contraseña <small>Déjala vacía para conservar la actual.</small><input id="password" type="password" minlength="6"></label>
</div><div class="actions"><button id="guardar">Guardar cambios</button></div><div class="msg" id="msg"></div></main>
<script>
guardar.onclick=async()=>{
 msg.textContent="";
 const r=await fetch("api/auth.php?action=update_profile",{method:"POST",headers:{"Content-Type":"application/json"},body:JSON.stringify({nombre:nombre.value,email:email.value,password:password.value})});
 const d=await r.json();
 if(!r.ok){msg.textContent=d.error||"Error";return}
 password.value="";msg.textContent="Datos actualizados correctamente.";
};
</script></body></html>
