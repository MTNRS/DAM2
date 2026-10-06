document.addEventListener("DOMContentLoaded",async()=>{
const canvas=document.querySelector("#canvas"), arrows=document.querySelector("#arrows"), status=document.querySelector("#status");
let model={version:1,tables:[],relations:[]}, fkMode=false, firstPort=null, selected=null;

function esc(s){return String(s??"").replace(/[&<>"']/g,c=>({"&":"&amp;","<":"&lt;",">":"&gt;",'"':"&quot;","'":"&#39;"}[c]));}
function tableByName(n){return model.tables.find(t=>t.name===n)}
function columnExists(t,c){return !!tableByName(t)?.columns.some(x=>x.name===c)}
function mark(msg){status.textContent=msg}

async function load(){
 const r=await fetch("api/modelo.php?action=get",{cache:"no-store"}); const d=await r.json();
 if(!d.ok) throw new Error(d.error); model=d.model; render(); mark(`${model.tables.length} entidades · ${model.relations.length} FK`);
}
function entityHTML(t){
 const e=document.createElement("div"); e.className="entity"; e.style.left=t.x+"px"; e.style.top=t.y+"px"; e.dataset.table=t.name;
 e.innerHTML=`<div class="entity-title" title="Doble clic para renombrar">${esc(t.name)}</div><div class="cols"></div><button class="addcol">+ campo</button>`;
 const cols=e.querySelector(".cols");
 t.columns.forEach(c=>cols.appendChild(colRow(t,c)));
 e.querySelector(".addcol").onclick=()=>{let n="campo";let i=1;while(t.columns.some(c=>c.name===n))n="campo_"+(++i);t.columns.push({name:n,type:"TEXT",pk:false});render();};
 e.querySelector(".entity-title").ondblclick=()=>{const n=prompt("Nombre de la entidad:",t.name);if(!n||n===t.name)return;if(model.tables.some(x=>x.name===n)){alert("Ya existe esa entidad");return}const old=t.name;t.name=n;model.relations.forEach(r=>{if(r.fromTable===old)r.fromTable=n;if(r.toTable===old)r.toTable=n});render();};
 dragEntity(e,t);
 e.onclick=ev=>{if(ev.target.closest(".port,.col input,.col select,.col button,.addcol"))return;selected={type:"table",table:t.name};highlight()};
 return e;
}
function colRow(t,c){
 const row=document.createElement("div");row.className="col";row.dataset.col=c.name;
 row.innerHTML=`<span class="port left"></span><input value="${esc(c.name)}" title="Nombre"><select><option>INTEGER</option><option>TEXT</option><option>REAL</option><option>NUMERIC</option><option>BLOB</option></select><button title="Eliminar">×</button><span class="port right"></span>`;
 row.querySelector("select").value=(c.type||"TEXT").toUpperCase().split("(")[0];
 const inp=row.querySelector("input");
 inp.onchange=()=>{const old=c.name,n=inp.value.trim();if(!n||t.columns.some(x=>x!==c&&x.name===n)){inp.value=old;return}c.name=n;model.relations.forEach(r=>{if(r.fromTable===t.name&&r.fromColumn===old)r.fromColumn=n;if(r.toTable===t.name&&r.toColumn===old)r.toColumn=n});renderArrows()};
 row.querySelector("select").onchange=e=>c.type=e.target.value;
 row.querySelector("button").onclick=()=>{model.relations=model.relations.filter(r=>!(r.fromTable===t.name&&r.fromColumn===c.name)&&!(r.toTable===t.name&&r.toColumn===c.name));t.columns=t.columns.filter(x=>x!==c);render()};
 row.querySelectorAll(".port").forEach(p=>p.onclick=ev=>{ev.stopPropagation();portClick(p,t,c)});
 return row;
}
function portClick(p,t,c){
 if(!fkMode){selected={type:"column",table:t.name,column:c.name};highlight();return}
 if(!firstPort){firstPort={p,t,c};p.classList.add("pick");mark(`FK: origen ${t.name}.${c.name}; selecciona destino`);return}
 if(firstPort.t===t&&firstPort.c===c)return;
 model.relations.push({fromTable:firstPort.t.name,fromColumn:firstPort.c.name,toTable:t.name,toColumn:c.name});
 firstPort=null;fkMode=false;document.querySelector("#fkMode").textContent="→ Crear FK";render();mark("FK creada. Pendiente de migración.");
}
function dragEntity(el,t){
 const head=el.querySelector(".entity-title");let sx,sy,ox,oy,m=false;
 head.onmousedown=e=>{if(e.detail>1)return;m=true;sx=e.clientX;sy=e.clientY;ox=t.x;oy=t.y;document.body.style.userSelect="none"};
 window.addEventListener("mousemove",e=>{if(!m)return;t.x=Math.max(0,ox+e.clientX-sx);t.y=Math.max(0,oy+e.clientY-sy);el.style.left=t.x+"px";el.style.top=t.y+"px";renderArrows()});
 window.addEventListener("mouseup",()=>{m=false;document.body.style.userSelect=""});
}
function render(){
 canvas.querySelectorAll(".entity").forEach(e=>e.remove());
 model.tables.forEach(t=>canvas.appendChild(entityHTML(t)));renderArrows();highlight();
}
function centerOf(table,col,side){
 const e=[...canvas.querySelectorAll(".entity")].find(x=>x.dataset.table===table);if(!e)return null;
 const row=[...e.querySelectorAll(".col")].find(x=>x.dataset.col===col);if(!row)return null;
 const p=row.querySelector(".port."+side),cr=canvas.getBoundingClientRect(),r=p.getBoundingClientRect();
 return {x:r.left-cr.left+r.width/2,y:r.top-cr.top+r.height/2};
}
function renderArrows(){
 arrows.querySelectorAll("line").forEach(x=>x.remove());
 model.relations=model.relations.filter(r=>tableByName(r.fromTable)&&tableByName(r.toTable)&&columnExists(r.fromTable,r.fromColumn)&&columnExists(r.toTable,r.toColumn));
 model.relations.forEach((r,i)=>{const a=centerOf(r.fromTable,r.fromColumn,"right"),b=centerOf(r.toTable,r.toColumn,"left");if(!a||!b)return;const l=document.createElementNS("http://www.w3.org/2000/svg","line");l.setAttribute("x1",a.x);l.setAttribute("y1",a.y);l.setAttribute("x2",b.x);l.setAttribute("y2",b.y);l.dataset.i=i;l.style.pointerEvents="stroke";l.onclick=()=>{selected={type:"relation",index:i};highlight()};arrows.appendChild(l)});
}
function highlight(){
 canvas.querySelectorAll(".entity").forEach(e=>e.style.outline="");
 arrows.querySelectorAll("line").forEach(l=>l.classList.remove("sel"));
 if(selected?.type==="table"){const e=[...canvas.querySelectorAll(".entity")].find(x=>x.dataset.table===selected.table);if(e)e.style.outline="3px solid #dc143c55"}
 if(selected?.type==="relation"){arrows.querySelector(`line[data-i="${selected.index}"]`)?.classList.add("sel")}
}
document.querySelector("#addEntity").onclick=()=>{let n="nueva_entidad",i=1;while(tableByName(n))n="nueva_entidad_"+(++i);model.tables.push({name:n,x:260,y:120,columns:[{name:"Identificador",type:"INTEGER",pk:true}]});render()};
document.querySelector("#fkMode").onclick=e=>{fkMode=!fkMode;firstPort=null;e.target.textContent=fkMode?"Cancelar FK":"→ Crear FK";mark(fkMode?"Selecciona el campo origen y después el campo destino":"Modo FK cancelado")};
document.querySelector("#deleteSelected").onclick=()=>{if(!selected)return;if(selected.type==="table"){model.tables=model.tables.filter(t=>t.name!==selected.table);model.relations=model.relations.filter(r=>r.fromTable!==selected.table&&r.toTable!==selected.table)}else if(selected.type==="relation"){model.relations.splice(selected.index,1)}selected=null;render()};
async function preview(){const r=await fetch("api/modelo.php?action=preview",{method:"POST",headers:{"Content-Type":"application/json"},body:JSON.stringify({model})});const d=await r.json();if(!d.ok)throw new Error(d.error);return d.sql}
document.querySelector("#sqlBtn").onclick=async()=>{try{document.querySelector("#sqlText").value=await preview();document.querySelector("#sqlModal").hidden=false}catch(e){alert(e.message)}};
document.querySelector("#closeSql").onclick=()=>document.querySelector("#sqlModal").hidden=true;
document.querySelector("#applyBtn").onclick=async()=>{try{const sql=await preview();if(!confirm("Se va a crear un backup y aplicar esta migración a crimson.sqlite.\\n\\n¿Continuar?"))return;const r=await fetch("api/modelo.php?action=apply",{method:"POST",headers:{"Content-Type":"application/json"},body:JSON.stringify({model})});const d=await r.json();if(!d.ok)throw new Error(d.error);alert(`Migración aplicada: ${d.migration}\\nBackup: ${d.backup}`);mark("Migración aplicada correctamente")}catch(e){alert(e.message)}};
load().catch(e=>mark("Error: "+e.message));
});