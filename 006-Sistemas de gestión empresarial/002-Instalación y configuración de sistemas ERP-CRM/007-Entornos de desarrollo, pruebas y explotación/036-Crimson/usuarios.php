<?php require_once __DIR__."/api/auth_common.php"; requireAdmin(false); ?>
<!doctype html><html lang="es"><head><link rel="icon" type="image/svg+xml" href="https://static.jocarsa.com/logos/crimson.svg"><meta charset="utf-8"><title>Usuarios · Crimson</title><link rel="stylesheet" href="nucleo/estilo/general.css?v=031">
<style>
body{display:block;background:#f5f6f7}.top{height:60px;background:crimson;color:white;display:flex;align-items:center;padding:10px 14px;gap:12px}.top .spacer{flex:1}.top a{background:white;color:crimson;padding:9px 12px;text-decoration:none}.shell{display:grid;grid-template-columns:260px 1fr;height:calc(100vh - 60px)}aside{background:crimson;padding:10px;overflow:auto}aside button{display:block;width:100%;padding:10px;margin-bottom:7px;background:white;color:crimson;border:0;text-align:left;cursor:pointer}aside button.active{background:#a80f2d;color:white;border:1px solid white}main{padding:20px;overflow:auto}.card{background:white;border:1px solid #ddd;padding:18px;max-width:1100px}.form{display:grid;grid-template-columns:repeat(2,minmax(180px,1fr));gap:10px}.form label{display:flex;flex-direction:column;gap:4px}.form input,.form select{padding:9px;border:1px solid #ccc}.actions{margin:14px 0;display:flex;gap:8px}.actions button{padding:9px 13px;border:0;background:crimson;color:white;cursor:pointer}.permissions{width:100%;border-collapse:collapse}.permissions th,.permissions td{padding:8px;border-bottom:1px solid #ddd;text-align:center}.permissions th:first-child,.permissions td:first-child{text-align:left}.muted{color:#777}
</style></head><body>
<header class="top"><strong>jocarsa | crimson · usuarios y permisos</strong><span class="spacer"></span><a href="index.html">Volver al ERP</a></header>
<div class="shell"><aside><button id="newUser">+ Nuevo usuario</button><div id="users"></div></aside><main><div class="card" id="editor"><h1>Usuarios</h1><p class="muted">Selecciona un usuario o crea uno nuevo.</p></div></main></div>
<script>
let state,current=null;
async function api(action,body){const r=await fetch("api/usuarios.php?action="+action,{method:body?"POST":"GET",headers:body?{"Content-Type":"application/json"}:{},body:body?JSON.stringify(body):null,cache:"no-store"});const d=await r.json();if(!r.ok||!d.ok)throw new Error(d.error||"Error");return d}
async function load(id=null){state=await api("list");renderList();if(id)edit(id)}
function renderList(){users.innerHTML="";state.users.forEach(u=>{const b=document.createElement("button");b.textContent=u.nombre+" · "+u.username;b.classList.toggle("active",u.Identificador==current);b.onclick=()=>edit(u.Identificador);users.appendChild(b)})}
function edit(id){current=id;renderList();const u=state.users.find(x=>x.Identificador==id);showForm(u)}
function showForm(u=null){
 const isNew=!u;editor.innerHTML=`<h1>${isNew?"Nuevo usuario":u.nombre}</h1><div class="form">
 <label>Nombre<input id="fNombre" value="${u?.nombre||""}"></label><label>Usuario<input id="fUser" value="${u?.username||""}"></label>
 <label>Email<input id="fEmail" value="${u?.email||""}"></label><label>Contraseña ${isNew?"":"(dejar vacía para conservar)"}<input id="fPass" type="password"></label>
 <label>Rol<select id="fRol"><option value="usuario">Usuario</option><option value="admin">Administrador</option></select></label><label>Activo<input id="fActivo" type="checkbox" ${isNew||u.activo==1?"checked":""}></label></div>
 <div class="actions"><button id="saveUser">Guardar usuario</button>${isNew?"":'<button id="deleteUser">Eliminar usuario</button>'}</div>
 ${isNew?"":`<h2>Acceso por módulos</h2><p class="muted">Cada permiso es independiente. Si no tiene ningún permiso en un módulo, el módulo no aparecerá para ese usuario.</p><table class="permissions"><tr><th>Módulo</th><th>Ver / seleccionar</th><th>Insertar</th><th>Actualizar</th><th>Eliminar</th><th>Todo</th><th>Nada</th></tr><tbody id="permBody"></tbody></table><div class="actions"><button id="savePerm">Guardar permisos</button></div>`}`;
 fRol.value=u?.rol||"usuario";
 saveUser.onclick=async()=>{try{const d=await api("save",{Identificador:u?.Identificador||0,nombre:fNombre.value,username:fUser.value,email:fEmail.value,password:fPass.value,rol:fRol.value,activo:fActivo.checked});await load(d.Identificador)}catch(e){alert(e.message)}};
 if(!isNew){
   deleteUser.onclick=async()=>{if(!confirm("¿Eliminar este usuario?"))return;try{await api("delete",{Identificador:u.Identificador});current=null;await load();editor.innerHTML="<h1>Usuarios</h1>"}catch(e){alert(e.message)}};
   const by={};state.permissions.filter(p=>p.usuario_id==u.Identificador).forEach(p=>by[p.modulo]=p);
   state.modules.forEach(m=>{const p=by[m.modulo]||{};const tr=document.createElement("tr");tr.dataset.modulo=m.modulo;tr.innerHTML=`<td>${m.nombre}</td><td><input data-p="seleccionar" type="checkbox" ${p.puede_seleccionar==1?"checked":""}></td><td><input data-p="insertar" type="checkbox" ${p.puede_insertar==1?"checked":""}></td><td><input data-p="actualizar" type="checkbox" ${p.puede_actualizar==1?"checked":""}></td><td><input data-p="eliminar" type="checkbox" ${p.puede_eliminar==1?"checked":""}></td><td><button type="button" class="rowAll">Todo</button></td><td><button type="button" class="rowNone">Nada</button></td>`;
     tr.querySelector(".rowAll").onclick=()=>tr.querySelectorAll('input[type="checkbox"]').forEach(c=>c.checked=true);
     tr.querySelector(".rowNone").onclick=()=>tr.querySelectorAll('input[type="checkbox"]').forEach(c=>c.checked=false);
     permBody.appendChild(tr)});
   savePerm.onclick=async()=>{const permissions=[...permBody.querySelectorAll("tr")].map(tr=>({modulo:tr.dataset.modulo,seleccionar:tr.querySelector('[data-p=seleccionar]').checked,insertar:tr.querySelector('[data-p=insertar]').checked,actualizar:tr.querySelector('[data-p=actualizar]').checked,eliminar:tr.querySelector('[data-p=eliminar]').checked}));try{await api("permissions",{usuario_id:u.Identificador,permissions});await load(u.Identificador);alert("Permisos guardados")}catch(e){alert(e.message)}};
 }
}
newUser.onclick=()=>{current=null;renderList();showForm(null)};load().catch(e=>alert(e.message));
</script></body></html>