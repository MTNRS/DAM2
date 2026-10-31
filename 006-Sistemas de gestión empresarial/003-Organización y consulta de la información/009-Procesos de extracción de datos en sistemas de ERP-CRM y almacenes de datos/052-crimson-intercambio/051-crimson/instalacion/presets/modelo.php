<?php require_once __DIR__."/api/auth_common.php"; requireAdmin(false); ?>
<!doctype html><html lang="es"><head><link rel="icon" type="image/svg+xml" href="https://static.jocarsa.com/logos/crimson.svg"><meta charset="utf-8"><title>Modelo de datos · Crimson</title>
<link rel="stylesheet" href="nucleo/estilo/designer.css?v=031">
<style>
html,body{height:100%;margin:0;font-family:Ubuntu,Arial,sans-serif;overflow:hidden}.top{height:64px;background:crimson;color:white;display:flex;align-items:center;gap:10px;padding:10px 14px}.top .spacer{flex:1}.top button,.top a{border:0;background:white;color:crimson;padding:9px 12px;text-decoration:none;font:inherit;cursor:pointer}.work{height:calc(100vh - 64px)}dialog{width:min(1000px,92vw);border:0;box-shadow:0 10px 40px #0006}dialog textarea{width:100%;height:60vh;font-family:monospace;font-size:12px}
</style></head><body>
<header class="top"><strong>jocarsa | crimson · modelo de datos</strong><span>Migraciones / actualizaciones</span><span class="spacer"></span><button id="sqlBtn">Ver migración SQL</button><button id="applyBtn">Aplicar migración</button><a href="index.html">Volver</a></header>
<div class="work"><div class="designer-shell"><aside class="designer-tools"><button class="primary" id="addEntity">+ Entidad</button><button id="fkMode">→ Crear FK</button><button id="layoutBtn">↔ Redistribuir</button><button id="deleteSelected">Eliminar selección</button><button id="clearBtn">Vaciar diagrama</button><small>El mismo diseñador del instalador. Aquí los cambios se convierten en una migración sobre la base existente, con backup previo.</small><small><b>Navegación:</b> rueda = zoom · botón central + arrastrar = desplazar.</small><div class="status" id="status"></div></aside>
<div class="viewport"><div class="canvas" id="canvas"><svg class="arrows" id="arrows"><defs><marker id="arrow" markerWidth="8" markerHeight="8" refX="7" refY="3" orient="auto"><path d="M0,0 L0,6 L8,3 z" fill="#8a8f95"/></marker></defs></svg></div></div></div></div>
<dialog id="sqlDialog"><h2>SQL de migración</h2><textarea id="sqlText" readonly></textarea><button onclick="sqlDialog.close()">Cerrar</button></dialog>
<script src="nucleo/js/designer.js?v=031"></script><script>
let designer;
(async()=>{
 const r=await fetch("api/modelo.php?action=get",{cache:"no-store"}),d=await r.json();if(!d.ok){alert(d.error);return}
 designer=CrimsonDesigner({canvas:"#canvas",arrows:"#arrows",status:"#status",addButton:"#addEntity",fkButton:"#fkMode",deleteButton:"#deleteSelected",clearButton:"#clearBtn",layoutButton:"#layoutBtn",model:d.model});
})();
async function call(action){const r=await fetch("api/modelo.php?action="+action,{method:"POST",headers:{"Content-Type":"application/json"},body:JSON.stringify({model:designer.getModel()})});const d=await r.json();if(!d.ok)throw new Error(d.error);return d}
sqlBtn.onclick=async()=>{try{sqlText.value=(await call("preview")).sql;sqlDialog.showModal()}catch(e){alert(e.message)}};
applyBtn.onclick=async()=>{if(!confirm("Se creará un backup y se aplicará la migración. ¿Continuar?"))return;try{const d=await call("apply");alert(`Migración aplicada: ${d.migration}\\nBackup: ${d.backup}`)}catch(e){alert(e.message)}};
</script></body></html>
