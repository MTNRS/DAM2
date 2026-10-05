<?php
$presets=[];
foreach(glob(__DIR__."/presets/*.json") as $f){
  $m=json_decode(file_get_contents($f),true);
  if(!is_array($m))continue;
  $id=basename($f,".json");
  $presets[$id]=[
    "id"=>$id,
    "nombre"=>$id==="erp-completo"?"ERP completo actual":"Empresa pequeña",
    "descripcion"=>$id==="erp-completo"?"Modelo ERP completo con todos los dominios y relaciones.":"Modelo sencillo para una pequeña empresa: clientes, proveedores, productos, ventas, compras, facturas y empleados.",
    "entidades"=>count($m["tables"]??[]),
    "relaciones"=>count($m["relations"]??[])
  ];
}
?>
<!doctype html><html lang="es"><head><link rel="icon" type="image/svg+xml" href="https://static.jocarsa.com/logos/crimson.svg"><meta charset="utf-8"><title>Instalador visual · Crimson</title>
<link rel="stylesheet" href="../nucleo/estilo/designer.css?v=031">
<style>
html,body{height:100%;margin:0;font-family:Ubuntu,Arial,sans-serif;color:#292d31;overflow:hidden}.top{height:64px;background:crimson;color:#fff;display:flex;align-items:center;gap:10px;padding:10px 14px}.top strong{font-size:17px}.top span{opacity:.82}.top .spacer{flex:1}.top button{border:0;background:white;color:crimson;padding:9px 12px;cursor:pointer;font:inherit}.work{height:calc(100vh - 64px)}.welcome{position:fixed;inset:0;background:#f5f6f7;z-index:20;display:flex;align-items:center;justify-content:center;padding:30px}.welcome.hidden{display:none}.box{width:min(1000px,96vw);background:white;border:1px solid #ddd;padding:26px}.box h1{margin-top:0;color:crimson}.choices{display:grid;grid-template-columns:repeat(3,1fr);gap:14px}.choice{border:1px solid #ddd;background:#fafafa;padding:18px;cursor:pointer;text-align:left}.choice:hover{border-color:crimson;background:#fff5f7}.choice strong{display:block;color:crimson;font-size:17px;margin-bottom:8px}.choice small{display:block;color:#777;margin-top:10px}.presetbar{display:flex;gap:8px;align-items:center}.presetbar select{padding:8px;border:1px solid #ddd}dialog{width:min(1000px,92vw);border:0;box-shadow:0 10px 40px #0006}dialog textarea{width:100%;height:60vh;font-family:monospace;font-size:12px}.dialog-actions{text-align:right;margin-top:8px}.dialog-actions button{padding:8px 12px}
</style></head><body>
<div class="welcome" id="welcome"><div class="box"><h1>Crear Crimson ERP</h1><p>Escoge cómo quieres comenzar el modelo de datos. Después podrás modificar visualmente entidades, campos y relaciones antes de crear SQLite.</p><div class="choices">
<button class="choice" id="blankChoice"><strong>1. Diseñar desde cero</strong>Empieza con un diagrama completamente vacío.<small>0 entidades · 0 relaciones</small></button>
<?php foreach($presets as $p){ ?><button class="choice presetChoice" data-preset="<?=htmlspecialchars($p["id"])?>"><strong><?=htmlspecialchars($p["nombre"])?></strong><?=htmlspecialchars($p["descripcion"])?><small><?=$p["entidades"]?> entidades · <?=$p["relaciones"]?> relaciones FK</small></button><?php } ?>
</div></div></div>
<header class="top"><strong>jocarsa | crimson · instalador visual</strong><span>Diseña o carga un preset</span><span class="spacer"></span><div class="presetbar"><select id="presetSelect"><option value="">Cargar preset…</option><?php foreach($presets as $p){ ?><option value="<?=htmlspecialchars($p["id"])?>"><?=htmlspecialchars($p["nombre"])?></option><?php } ?></select><button id="loadPreset">Cargar</button></div><button id="sqlBtn">Ver SQL</button><button id="installBtn">Crear base de datos</button></header>
<div class="work"><div class="designer-shell"><aside class="designer-tools"><button class="primary" id="addEntity">+ Entidad</button><button id="fkMode">→ Crear FK</button><button id="layoutBtn">↔ Redistribuir</button><button id="deleteSelected">Eliminar selección</button><button id="clearBtn">Vaciar diagrama</button><small>Puedes comenzar vacío o cargar cualquiera de los presets incluidos. Cargar un preset reemplaza el diagrama actual, pero todavía puedes editarlo antes de instalar.</small>
<hr><strong>Administrador inicial</strong>
<label>Nombre<input id="adminNombre" style="width:100%;padding:7px;margin:4px 0 7px" value="Administrador"></label>
<label>Usuario<input id="adminUsuario" style="width:100%;padding:7px;margin:4px 0 7px" value="admin"></label>
<label>Email<input id="adminEmail" type="email" style="width:100%;padding:7px;margin:4px 0 7px"></label>
<label>Contraseña<input id="adminPassword" type="password" style="width:100%;padding:7px;margin:4px 0 7px" minlength="6"></label><small><b>Navegación:</b> rueda = zoom · botón central + arrastrar = desplazar.</small><div class="status" id="status"></div></aside>
<div class="viewport"><div class="canvas" id="canvas"><svg class="arrows" id="arrows"><defs><marker id="arrow" markerWidth="8" markerHeight="8" refX="7" refY="3" orient="auto"><path d="M0,0 L0,6 L8,3 z" fill="#8a8f95"/></marker></defs></svg></div></div></div></div>
<dialog id="sqlDialog"><h2>SQL generado desde el diagrama</h2><textarea id="sqlText" readonly></textarea><div class="dialog-actions"><button onclick="sqlDialog.close()">Cerrar</button></div></dialog>
<script src="../nucleo/js/designer.js?v=031"></script>
<script>
const empty={version:1,tables:[],relations:[]};
const designer=CrimsonDesigner({canvas:"#canvas",arrows:"#arrows",status:"#status",addButton:"#addEntity",fkButton:"#fkMode",deleteButton:"#deleteSelected",clearButton:"#clearBtn",layoutButton:"#layoutBtn",model:empty});
async function getPreset(id){const r=await fetch("presets/"+encodeURIComponent(id)+".json",{cache:"no-store"});if(!r.ok)throw new Error("No se ha podido cargar el preset");return await r.json()}
async function choosePreset(id){designer.setModel(await getPreset(id));welcome.classList.add("hidden")}
blankChoice.onclick=()=>{designer.setModel(empty);welcome.classList.add("hidden")};
document.querySelectorAll(".presetChoice").forEach(b=>b.onclick=()=>choosePreset(b.dataset.preset).catch(e=>alert(e.message)));
loadPreset.onclick=()=>{if(!presetSelect.value)return;if(!confirm("El preset reemplazará el diagrama actual. ¿Continuar?"))return;choosePreset(presetSelect.value).catch(e=>alert(e.message))};
async function call(action){const r=await fetch("modelo_api.php?action="+action,{method:"POST",headers:{"Content-Type":"application/json"},body:JSON.stringify({model:designer.getModel(),admin:{
 nombre:adminNombre.value,username:adminUsuario.value,email:adminEmail.value,password:adminPassword.value
}})});const d=await r.json();if(!d.ok)throw new Error(d.error);return d}
sqlBtn.onclick=async()=>{try{sqlText.value=(await call("preview")).sql;sqlDialog.showModal()}catch(e){alert(e.message)}};
installBtn.onclick=async()=>{if(!designer.getModel().tables.length){alert("El modelo no contiene entidades");return}if(!adminNombre.value.trim()||!adminUsuario.value.trim()||adminPassword.value.length<6){alert("Completa los datos del administrador y usa una contraseña de al menos 6 caracteres");return}if(!confirm("Se recreará crimson.sqlite desde el diagrama actual. ¿Continuar?"))return;try{const d=await call("install");alert(`Instalación completada: ${d.tables} entidades y ${d.relations} FK`);location.href="../index.html"}catch(e){alert(e.message)}};
</script></body></html>