<?php
$modelo=dirname(__DIR__)."/modelo/schema.json";
$initial=file_exists($modelo)?file_get_contents($modelo):'{"version":1,"tables":[],"relations":[]}';
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><title>Instalador visual · Crimson</title>
<link rel="stylesheet" href="../nucleo/estilo/designer.css?v=025">
<style>
html,body{height:100%;margin:0;font-family:Ubuntu,Arial,sans-serif;color:#292d31;overflow:hidden}.top{height:64px;background:crimson;color:#fff;display:flex;align-items:center;gap:10px;padding:10px 14px}.top strong{font-size:17px}.top span{opacity:.8}.top .spacer{flex:1}.top button{border:0;background:white;color:crimson;padding:9px 12px;cursor:pointer;font:inherit}.work{height:calc(100vh - 64px)}dialog{width:min(1000px,92vw);border:0;box-shadow:0 10px 40px #0006}dialog textarea{width:100%;height:60vh;font-family:monospace;font-size:12px}.dialog-actions{text-align:right;margin-top:8px}.dialog-actions button{padding:8px 12px}
</style></head><body>
<header class="top"><strong>jocarsa | crimson · instalador visual</strong><span>Diseña el modelo inicial</span><span class="spacer"></span><button id="sqlBtn">Ver SQL</button><button id="installBtn">Crear base de datos</button></header>
<div class="work"><div class="designer-shell">
<aside class="designer-tools"><button class="primary" id="addEntity">+ Entidad</button><button id="fkMode">→ Crear FK</button><button id="layoutBtn">↔ Redistribuir</button><button id="deleteSelected">Eliminar selección</button><button id="clearBtn">Vaciar diagrama</button>
<small>Este es ahora el instalador: diseña entidades, campos y flechas FK directamente. Al crear la base se genera <b>schema.sql</b> y <b>crimson.sqlite</b>.</small><div class="status" id="status"></div></aside>
<div class="viewport"><div class="canvas" id="canvas"><svg class="arrows" id="arrows"><defs><marker id="arrow" markerWidth="8" markerHeight="8" refX="7" refY="3" orient="auto"><path d="M0,0 L0,6 L8,3 z" fill="#8a8f95"/></marker></defs></svg></div></div>
</div></div>
<dialog id="sqlDialog"><h2>SQL generado desde el diagrama</h2><textarea id="sqlText" readonly></textarea><div class="dialog-actions"><button onclick="sqlDialog.close()">Cerrar</button></div></dialog>
<script>window.INITIAL_MODEL=<?=$initial?>;</script>
<script src="../nucleo/js/designer.js?v=025"></script>
<script>
const designer=CrimsonDesigner({canvas:"#canvas",arrows:"#arrows",status:"#status",addButton:"#addEntity",fkButton:"#fkMode",deleteButton:"#deleteSelected",clearButton:"#clearBtn",layoutButton:"#layoutBtn",model:window.INITIAL_MODEL});
async function call(action){const r=await fetch("modelo_api.php?action="+action,{method:"POST",headers:{"Content-Type":"application/json"},body:JSON.stringify({model:designer.getModel()})});const d=await r.json();if(!d.ok)throw new Error(d.error);return d}
sqlBtn.onclick=async()=>{try{sqlText.value=(await call("preview")).sql;sqlDialog.showModal()}catch(e){alert(e.message)}};
installBtn.onclick=async()=>{if(!confirm("Se recreará crimson.sqlite desde el diagrama actual. Los datos existentes se perderán. ¿Continuar?"))return;try{const d=await call("install");alert(`Instalación completada: ${d.tables} entidades y ${d.relations} FK`);location.href="../index.html"}catch(e){alert(e.message)}};
</script></body></html>