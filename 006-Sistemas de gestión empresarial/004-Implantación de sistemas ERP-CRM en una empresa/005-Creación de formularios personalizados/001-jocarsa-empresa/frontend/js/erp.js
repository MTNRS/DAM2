(()=>{'use strict';
const API='../backend/api/api.php',AUTH='../backend/auth.php';
const $=s=>document.querySelector(s), nav=$('#navegacion .contenido-columna'), entities=$('#entidades .contenido-columna'),stage=$('#escenario');
let activeModule='',activeTable='',model=null,records=[],search='',viewMode='tabla';let erpSettings={themeColor:'#8b4513',rowsPerPage:25},canEditSettings=false,currentPage=1;const selectedIds=new Set();let columnFilters={},filterRequest=0,filterTimer=null;
const personalization=window.JocarsaPersonalizacion;
const escape=s=>String(s??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
async function req(url,data){const o=data===undefined?{credentials:'same-origin'}:{method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/json'},body:JSON.stringify(data)};const r=await fetch(url,o);let j;try{j=await r.json()}catch{throw Error('El servidor no ha devuelto JSON válido')};if(!r.ok||j.error||j.ok===false)throw Error(j.error||'Error HTTP '+r.status);return j}
function applyTheme(color){
 if(!/^#[0-9a-f]{6}$/i.test(color))return;
 const root=document.documentElement;
 root.style.setProperty('--color',color);
 root.style.setProperty('--erp-base',color);
 const rgb=[1,3,5].map(i=>parseInt(color.slice(i,i+2),16));
 const mix=(target,ratio)=>'#'+rgb.map((v,i)=>Math.round(v*(1-ratio)+target[i]*ratio).toString(16).padStart(2,'0')).join('');
 root.style.setProperty('--color0',mix([0,0,0],.56));
 root.style.setProperty('--color2',mix([255,255,255],.28));
 root.style.setProperty('--color3',mix([255,255,255],.63));
 root.style.setProperty('--color4',mix([255,255,255],.88));
 root.style.setProperty('--ju-borde',mix([255,255,255],.5));
 root.style.setProperty('--ju-tinta',mix([0,0,0],.75));
 root.style.setProperty('--ju-papel',mix([255,255,255],.96));
 root.style.setProperty('--ju-foco',mix([255,255,255],.4));
 root.style.setProperty('--ju-primary',color);root.style.setProperty('--ju-accent',color);root.style.setProperty('--ju-focus',mix([255,255,255],.4));
}
async function loadSettings(){try{const response=await req(API+'?bloque=erp_settings_get');erpSettings={...erpSettings,...response.settings};canEditSettings=!!response.canEdit;applyTheme(erpSettings.themeColor)}catch(e){console.warn('Configuración del ERP:',e)}}
function showSettings(){
 if(!canEditSettings)return;
 const overlay=document.createElement('div');overlay.className='erp-settings-overlay';
 const panel=document.createElement('form');panel.className='erp-settings-panel';
 panel.innerHTML='<h2>Configuración general</h2><p>Estos ajustes se comparten con todos los usuarios.</p><label>Color corporativo <input name="themeColor" type="color" value="'+escape(erpSettings.themeColor)+'"></label><label>Registros por página <input name="rowsPerPage" type="number" min="5" max="500" step="1" required value="'+Number(erpSettings.rowsPerPage)+'"></label><div class="erp-settings-actions"><button type="button" data-cancel>Cancelar</button><button type="submit">Guardar configuración</button></div>';
 panel.insertAdjacentHTML('beforeend','<section class="api-settings-link"><h3>API endpoints</h3><p>Gestionar publicaciones, claves y campos compartidos.</p><button type="button" data-api-list>Ver endpoints</button></section>');overlay.append(panel);document.body.append(overlay);panel.querySelector('[data-api-list]').onclick=()=>{overlay.remove();applyTheme(erpSettings.themeColor);window.CrimsonEndpoints.open()};
 const color=panel.elements.themeColor;const original=erpSettings.themeColor;
 color.oninput=()=>applyTheme(color.value);
 const close=()=>{overlay.remove();applyTheme(erpSettings.themeColor)};
 panel.querySelector('[data-cancel]').onclick=close;
 overlay.addEventListener('click',e=>{if(e.target===overlay)close()});
 panel.onsubmit=async e=>{e.preventDefault();const rowsPerPage=Number(panel.elements.rowsPerPage.value);try{const response=await req(API+'?bloque=erp_settings_save',{themeColor:color.value,rowsPerPage});erpSettings=response.settings;currentPage=1;overlay.remove();applyTheme(erpSettings.themeColor);render()}catch(err){alert(err.message)}};
}
function error(e){stage.innerHTML='<div class="erp-empty"><h2>Error</h2><p>'+escape(e.message)+'</p></div>'}
const badgePalette=['#b68b29','#347db1','#bf2f56','#2ab66d','#a82aa4','#afbf29','#9a612a','#247f86'];
function badgeColor(label){let hash=0;for(const ch of String(label))hash=(hash*31+ch.charCodeAt(0))>>>0;return badgePalette[hash%badgePalette.length]}
function item(label,selected,action){const a=document.createElement('article');a.tabIndex=0;a.setAttribute('role','button');a.className='erp-menu-item'+(selected?' activo':'');a.style.setProperty('--erp-ficha-color',badgeColor(label));a.innerHTML='<span class="ficha" aria-hidden="true">'+escape(label.charAt(0).toUpperCase())+'</span><span class="etiqueta">'+escape(label)+'</span>';a.addEventListener('click',e=>{e.preventDefault();action()});a.addEventListener('keydown',e=>{if(e.key==='Enter'||e.key===' '){e.preventDefault();action()}});return a}
let erpTree={}, erpPath=[], erpDynamicColumns=[];
function erpClearColumns(from=0){
  erpDynamicColumns.splice(from).forEach(x=>{x.separator.remove();x.panel.remove()});
}
function erpNode(name){return erpTree[name]||{parent:null,tables:[]}}
function erpChildren(parent){return Object.keys(erpTree).filter(k=>(erpTree[k].parent??null)===parent).sort((a,b)=>a.localeCompare(b,'es'))}
function erpAddColumn(title,depth){
  const separator=document.createElement('div');separator.className='separador erp-nested-separator';
  const panel=document.createElement('section');panel.className='erp-nested-column';panel.setAttribute('aria-label','Colección '+title);panel.dataset.depth=String(depth);
  const content=document.createElement('div');content.className='contenido-columna';
  panel.append(content);stage.before(separator,panel);
  erpDynamicColumns.push({panel,separator,content});
  return content;
}
function erpDescendantCollections(parent){
  const descendants=new Set(),pending=[parent];
  while(pending.length){const current=pending.pop();for(const child of erpChildren(current)){if(descendants.has(child))continue;descendants.add(child);pending.push(child)}}
  return descendants;
}
function erpVisibleTables(parent){
  if(parent===null)return [];
  const nestedTables=new Set();
  for(const child of erpDescendantCollections(parent))for(const t of (erpNode(child).tables||[]))nestedTables.add(t);
  return [...new Set(erpNode(parent).tables||[])].filter(t=>!nestedTables.has(t)).sort((a,b)=>a.localeCompare(b,'es'));
}
function erpDrawItems(host,parent,depth){
  host.replaceChildren();
  const header=document.createElement('div');header.className='titulo-columna';header.textContent=parent===null?'Colecciones':parent;host.append(header);
  const folders=erpChildren(parent),tables=erpVisibleTables(parent);
  for(const folder of folders){
    const a=item(folder,erpPath[depth]===folder,()=>erpChooseFolder(folder,depth));
    a.classList.add('erp-nested-folder');a.title='Subcolección · '+folder;
    a.querySelector('.etiqueta').prepend(document.createTextNode('▸ '));host.append(a);
  }
  for(const table of tables){
    const a=item(table,activeTable===table,()=>loadTable(table));
    a.classList.add('erp-nested-entity');a.title='Entidad · '+table;
    a.querySelector('.etiqueta').prepend(document.createTextNode('▦ '));host.append(a);
  }
  if(!folders.length&&!tables.length){const empty=document.createElement('p');empty.className='erp-nested-empty';empty.textContent='Colección vacía';host.append(empty)}
}
function erpChooseFolder(folder,depth){
  erpPath=erpPath.slice(0,depth);erpPath.push(folder);activeModule=erpPath[0]||'';
  erpClearColumns(0);
  erpDrawItems(nav,null,0);
  const parent=erpPath[0];
  erpDrawItems(entities,parent,1);
  for(let i=1;i<erpPath.length;i++){
    const content=erpAddColumn(erpPath[i],i+1);
    erpDrawItems(content,erpPath[i],i+1);
  }
  document.querySelector('#herramientas')?.replaceChildren();stage.innerHTML='<div class="erp-empty"><h2>'+escape(folder)+'</h2><p>Selecciona una entidad o una subcolección.</p></div>';
}
async function loadModules(){
  const result=await req(API+'?bloque=jerarquia');erpTree=result.collections||{};
  erpClearColumns();erpPath=[];nav.replaceChildren();entities.replaceChildren();
  erpDrawItems(nav,null,0);
  const roots=erpChildren(null);
  if(roots.length)erpChooseFolder(roots[0],0);
  else stage.innerHTML='<div class="erp-empty">No hay colecciones accesibles.</div>';
}
async function loadTables(mod){erpChooseFolder(mod,0)}
async function loadTable(t){if(activeTable!==t){columnFilters={};currentPage=1;}selectedIds.clear();activeTable=t;viewMode='tabla';model=await req(API+'?bloque=tabla&tabla='+encodeURIComponent(t));records=model.contenido||[];await personalization.loadShared(t);document.querySelectorAll('.erp-nested-column .erp-nested-entity, #entidades .erp-nested-entity').forEach(a=>a.classList.toggle('activo',a.querySelector('.etiqueta')?.textContent.trim().replace(/^▦\s*/, '')===t));render()}
function columns(){return (model.estructura||[]).map(c=>typeof c==='string'?{name:c}:c).filter(c=>c.name&&!c.name.startsWith('__'))}
function erpHeaderActions(){
 const header=document.querySelector('body.jocarsa-iu > header #herramientas');
 if(!header)return;
 const toolbar=stage.querySelector('.erp-toolbar');
 if(!toolbar){header.replaceChildren();return;}
 const actions=toolbar.querySelector(':scope > div:nth-child(2)');
 if(!actions)return;
 // Move actual controls rather than cloning them: retain their event handlers.
 header.replaceChildren();
 header.classList.add('erp-header-actions');
 header.append(...Array.from(actions.children));
 actions.remove();
}
function render(){if(!model)return;const allCols=columns();const pref=personalization.get(activeTable,allCols);const configuredActions=personalization.actions(activeTable,allCols);const workflow=pref.workflow||{};const cols=allCols.filter(c=>pref.columns.includes(c.name)).sort((a,b)=>pref.columns.indexOf(a.name)-pref.columns.indexOf(b.name));let filtered=records.filter(r=>Object.values(r).some(v=>String(v??'').toLowerCase().includes(search.toLowerCase())));filtered=personalization.filter(filtered,pref);filtered=personalization.sort(filtered,pref);const pageSize=Math.max(5,Math.min(500,Number(erpSettings.rowsPerPage)||25));const totalPages=Math.max(1,Math.ceil(filtered.length/pageSize));currentPage=Math.min(Math.max(1,currentPage),totalPages);const pageRows=filtered.slice((currentPage-1)*pageSize,currentPage*pageSize);stage.innerHTML='<section class="erp-screen"><div class="erp-toolbar"><div><small>'+escape(activeModule)+'</small><h2>'+escape(activeTable)+'</h2><span>'+filtered.length+' registros</span></div><div><button id="erp-refresh">Actualizar</button> '+(canEditSettings?'<button id="erp-settings" type="button">Configuración</button> ':'')+'<button id="erp-api" type="button">API endpoint</button> <button id="erp-config" type="button" title="Configurar columnas, filtros y colores">Personalizar</button> <select id="erp-views" aria-label="Vistas guardadas"><option value="">Vistas guardadas…</option>'+personalization.views(activeTable).map(v=>'<option value="'+escape(v.name)+'">'+escape(v.name)+'</option>').join('')+'</select> <button id="erp-save-view" type="button">Guardar vista</button> <button id="erp-csv-export" type="button">Exportar CSV</button> <button id="erp-csv-template" type="button">Plantilla CSV</button> '+(model.permisos?.insertar?'<button id="erp-csv-import" type="button">Importar CSV</button>':'')+' '+(model.permisos?.insertar?'<button id="erp-add">+ Nuevo registro</button>':'')+'</div></div><div class="erp-view-tabs" role="tablist" aria-label="Presentación de registros"><button type="button" data-view="tabla" class="'+(viewMode==='tabla'?'activo':'')+'">Tabla</button><button type="button" data-view="tarjetas" class="'+(viewMode==='tarjetas'?'activo':'')+'">Tarjetas</button><button type="button" data-view="arbol" class="'+(viewMode==='arbol'?'activo':'')+'">Árbol</button></div><div class="erp-table-wrap" style="display:'+(viewMode==='tabla'?'block':'none')+'"><table class="erp-table"><thead><tr>'+cols.map(c=>'<th data-sort="'+escape(c.name)+'" title="Ordenar por '+escape(c.name)+'">'+escape(c.name)+(pref.sort?.field===c.name?(pref.sort.direction==='asc'?' ↑':' ↓'):'')+'</th>').join('')+'<th>Acciones</th></tr><tr class="erp-filter-row">'+cols.map(c=>'<th><input class="erp-column-filter" data-filter-field="'+escape(c.name)+'" value="'+escape(columnFilters[c.name]||'')+'" placeholder="Filtrar…" aria-label="Filtrar '+escape(c.name)+'"></th>').join('')+'<th><button type="button" id="erp-clear-filters">Limpiar</button></th></tr></thead><tbody>'+pageRows.map((r,i)=>'<tr class="'+personalization.rowClass(r,pref)+'">'+cols.map(c=>'<td data-inline-field="'+escape(c.name)+'" data-inline-id="'+escape(r.Identificador)+'" title="Doble clic para editar">'+escape(r['__fk__'+c.name]??r[c.name])+'</td>').join('')+'<td><div class="erp-actions"><button class="erp-star '+(personalization.isFavorite(activeTable,r.Identificador)?'is-favorite':'')+'" data-star="'+escape(r.Identificador)+'" title="Favorito" aria-label="Alternar favorito">'+(personalization.isFavorite(activeTable,r.Identificador)?'★':'☆')+'</button>'+configuredActions.filter(a=>a.placement!=='bulk'&&personalization.visibleAction(a,r)).map(a=>'<button type="button" data-custom-action="'+escape(a.id)+'" data-record-id="'+escape(r.Identificador)+'">'+escape(a.label)+'</button>').join('')+(model.permisos?.actualizar?'<button data-edit="'+escape(r.Identificador)+'">Editar</button>':'')+(model.permisos?.eliminar?'<button data-del="'+escape(r.Identificador)+'">Eliminar</button>':'')+'</div></td></tr>').join('')+'</tbody></table></div><nav class="erp-pagination" aria-label="Paginación"><button type="button" id="erp-page-prev" '+(currentPage<=1?'disabled':'')+'>Anterior</button><span>Página '+currentPage+' de '+totalPages+' · '+filtered.length+' registros · '+pageSize+' por página</span><button type="button" id="erp-page-next" '+(currentPage>=totalPages?'disabled':'')+'>Siguiente</button></nav><div class="erp-alt-view" id="erp-alt-view"></div></section>';
const bulkActions=configuredActions.filter(a=>a.placement==='bulk'||a.placement==='both');
if(bulkActions.length){const toolbar=stage.querySelector('.erp-toolbar');const bulk=document.createElement('div');bulk.className='erp-bulk-toolbar';bulk.innerHTML='<label><input type="checkbox" id="erp-select-all"> Seleccionar todos</label> <span id="erp-selected-count">0 seleccionados</span> '+bulkActions.map(a=>'<button type="button" data-bulk-action="'+escape(a.id)+'">'+escape(a.label)+'</button>').join('');toolbar.after(bulk);
 const table=stage.querySelector('.erp-table');if(table){table.querySelector('thead tr').insertAdjacentHTML('afterbegin','<th>✓</th>');table.querySelectorAll('tbody tr').forEach((tr,i)=>{const id=String(pageRows[i].Identificador);tr.insertAdjacentHTML('afterbegin','<td><input type="checkbox" class="erp-select-record" data-select-id="'+escape(id)+'" '+(selectedIds.has(id)?'checked':'')+'></td>')})}
 const updateCount=()=>{const el=stage.querySelector('#erp-selected-count');if(el)el.textContent=selectedIds.size+' seleccionados'};updateCount();
 stage.querySelector('#erp-select-all').onchange=e=>{for(const r of pageRows){const id=String(r.Identificador);e.target.checked?selectedIds.add(id):selectedIds.delete(id)}stage.querySelectorAll('[data-select-id]').forEach(x=>x.checked=e.target.checked);updateCount()};
 stage.querySelectorAll('[data-select-id]').forEach(x=>x.onchange=()=>{x.checked?selectedIds.add(x.dataset.selectId):selectedIds.delete(x.dataset.selectId);updateCount()});
 stage.querySelectorAll('[data-bulk-action]').forEach(b=>b.onclick=()=>executeCustom(configuredActions.find(a=>a.id===b.dataset.bulkAction),records.filter(r=>selectedIds.has(String(r.Identificador)))));
}
stage.querySelectorAll('.erp-column-filter').forEach(input=>{
 input.addEventListener('click',e=>e.stopPropagation());
 input.addEventListener('input',()=>{columnFilters[input.dataset.filterField]=input.value;clearTimeout(filterTimer);filterTimer=setTimeout(()=>applyColumnFilters(input.dataset.filterField),320)});
});
stage.querySelector('#erp-clear-filters')?.addEventListener('click',()=>{columnFilters={};applyColumnFilters()});
stage.querySelectorAll('[data-inline-field]').forEach(cell=>cell.addEventListener('dblclick',()=>editInlineCell(cell)));
stage.querySelectorAll('[data-workflow]').forEach(b=>b.onclick=async()=>{if(!confirm('¿Cambiar el estado a '+b.dataset.workflow+'?'))return;try{await req(API+'?bloque=actualizar',{tabla:activeTable,Identificador:Number(b.dataset.workflowId),[workflow.field]:b.dataset.workflow});await loadTable(activeTable)}catch(e){alert(e.message)}});
stage.querySelectorAll('[data-custom-action]').forEach(b=>b.onclick=()=>executeCustom(configuredActions.find(a=>a.id===b.dataset.customAction),records.filter(r=>String(r.Identificador)===b.dataset.recordId)));
const alt=$('#erp-alt-view');
if(viewMode==='tarjetas'){
 alt.className='erp-alt-view erp-card-grid';
 alt.innerHTML=pageRows.map(r=>'<article class="erp-record-card"><header class="erp-record-card-title">'+escape(r.nombre||r.Nombre||r.Identificador||'Registro')+'</header><dl>'+cols.map(c=>'<div><dt>'+escape(c.name)+'</dt><dd>'+escape(r['__fk__'+c.name]??r[c.name])+'</dd></div>').join('')+'</dl><div class="erp-actions">'+(model.permisos?.actualizar?'<button data-edit="'+escape(r.Identificador)+'">Editar</button>':'')+(model.permisos?.eliminar?'<button data-del="'+escape(r.Identificador)+'">Eliminar</button>':'')+'</div></article>').join('')||'<p>No hay registros.</p>';
}else if(viewMode==='arbol'){
 alt.className='erp-alt-view erp-tree-view';
 const groupField=cols.find(c=>/categoria|grupo|estado|tipo|departamento/i.test(c.name))?.name;
 const groups=new Map();for(const r of pageRows){const key=groupField?String(r['__fk__'+groupField]??r[groupField]??'Sin asignar'):'Registros';if(!groups.has(key))groups.set(key,[]);groups.get(key).push(r)}
 alt.innerHTML=[...groups].map(([key,rows])=>'<details open><summary>'+escape(key)+' ('+rows.length+')</summary><ul>'+rows.map(r=>'<li><span>'+escape(r.nombre||r.Nombre||r.Identificador||'Registro')+'</span> <span class="erp-actions">'+(model.permisos?.actualizar?'<button data-edit="'+escape(r.Identificador)+'">Editar</button>':'')+(model.permisos?.eliminar?'<button data-del="'+escape(r.Identificador)+'">Eliminar</button>':'')+'</span></li>').join('')+'</ul></details>').join('')||'<p>No hay registros.</p>';
}else alt.innerHTML='';
stage.querySelector('#erp-page-prev')?.addEventListener('click',()=>{currentPage--;render()});stage.querySelector('#erp-page-next')?.addEventListener('click',()=>{currentPage++;render()});stage.querySelector('#erp-settings')?.addEventListener('click',showSettings);stage.querySelector('#erp-api')?.addEventListener('click',()=>window.CrimsonEndpoints.open(activeTable));
stage.querySelectorAll('[data-view]').forEach(b=>b.onclick=()=>{viewMode=b.dataset.view;render()});
$('#erp-csv-export').onclick=()=>exportCSV(filtered);$('#erp-csv-template').onclick=()=>templateCSV();$('#erp-csv-import')?.addEventListener('click',importCSV);$('#erp-refresh').onclick=()=>loadTable(activeTable).catch(error);$('#erp-config').onclick=()=>personalization.editor(activeTable,allCols,render);$('#erp-save-view').onclick=()=>personalization.saveView(activeTable,render).catch(e=>alert(e.message));$('#erp-views').onchange=e=>{if(e.target.value)personalization.applyView(activeTable,e.target.value,render).catch(err=>alert(err.message))};stage.querySelectorAll('[data-sort]').forEach(th=>th.onclick=async()=>{try{await personalization.toggleSort(activeTable,th.dataset.sort);render()}catch(e){alert(e.message)}});stage.querySelectorAll('[data-star]').forEach(b=>b.onclick=()=>{personalization.toggleFavorite(activeTable,b.dataset.star);render()});$('#erp-add')?.addEventListener('click',()=>form(null));stage.querySelectorAll('[data-edit]').forEach(b=>b.onclick=()=>form(records.find(r=>String(r.Identificador)===b.dataset.edit)));stage.querySelectorAll('[data-del]').forEach(b=>b.onclick=()=>remove(b.dataset.del));erpHeaderActions();}
async function applyColumnFilters(focusField){
 const table=activeTable,request=++filterRequest;
 try{
  const params=new URLSearchParams({bloque:'tabla_filtrar',tabla:table,filtros:JSON.stringify(columnFilters)});
  const result=await req(API+'?'+params.toString());
  if(request!==filterRequest||table!==activeTable)return;
  records=result.contenido||[];currentPage=1;render();
  if(focusField){const field=[...stage.querySelectorAll('[data-filter-field]')].find(x=>x.dataset.filterField===focusField);if(field){field.focus();field.setSelectionRange(field.value.length,field.value.length)}}
 }catch(e){alert('Error al filtrar: '+e.message)}
}
async function editInlineCell(cell){
 if(!model?.permisos?.actualizar||cell.querySelector('input,textarea'))return;
 const field=cell.dataset.inlineField,id=cell.dataset.inlineId,col=columns().find(c=>c.name===field);
 if(!col||col.pk||field==='Identificador')return;
 const record=records.find(r=>String(r.Identificador)===id);if(!record)return;
 const original=record[field]??'',fk=col.fk;
 const input=document.createElement('input');input.className='erp-inline-input';input.value=String(original);input.setAttribute('aria-label','Editar '+field);
 let list=null,choices=[];
 if(fk){
  try{const data=await req(API+'?bloque=fk_opciones&tabla='+encodeURIComponent(activeTable)+'&campo='+encodeURIComponent(field));choices=data.opciones||[]}
  catch(e){alert('No se pudieron cargar las claves foráneas: '+e.message);return}
  list=document.createElement('datalist');list.id='erp-fk-'+Math.random().toString(36).slice(2);
  choices.forEach(o=>{const option=document.createElement('option');option.value=String(o.texto);list.append(option)});
  input.setAttribute('list',list.id);input.value=choices.find(o=>String(o.id)===String(original))?.texto||String(original);
 }else if(/INT|REAL|NUMERIC|DECIMAL|FLOAT|DOUBLE/i.test(col.type||''))input.type='number';
 const oldHTML=cell.innerHTML;cell.replaceChildren(input);if(list)cell.append(list);input.focus();input.select();
 let finished=false;
 const cancel=()=>{if(finished)return;finished=true;cell.innerHTML=oldHTML};
 const save=async()=>{
  if(finished)return;
  let value=input.value;
  if(fk){const selected=choices.find(o=>String(o.texto)===value||String(o.id)===value);if(!selected){alert('Selecciona un valor válido de la lista vinculada');input.focus();return}value=selected.id}
  if(String(value)===String(original)){cancel();return}
  finished=true;input.disabled=true;
  try{await req(API+'?bloque=actualizar',{tabla:activeTable,Identificador:Number(id),[field]:value});
   if(Object.values(columnFilters).some(v=>String(v).trim()))await applyColumnFilters();else await loadTable(activeTable);
  }catch(e){finished=false;input.disabled=false;alert('No se pudo guardar: '+e.message);input.focus()}
 };
 input.addEventListener('keydown',e=>{if(e.key==='Enter'){e.preventDefault();save()}if(e.key==='Escape'){e.preventDefault();cancel()}});
 input.addEventListener('blur',()=>setTimeout(()=>{if(!finished&&document.activeElement!==input)cancel()},180));
}
// CSV UTF-8 con BOM para compatibilidad con Excel y LibreOffice.
const csvEscape=value=>{let v=String(value??'');if(/^[\s]*[=+@\-\t\r]/.test(v))v="'"+v;return '"'+v.replace(/"/g,'""')+'"'};
function csvFile(rows,filename){const csv='\uFEFF'+rows.map(row=>row.map(csvEscape).join(';')).join('\r\n')+'\r\n';const blob=new Blob([csv],{type:'text/csv;charset=utf-8'});const url=URL.createObjectURL(blob);const a=document.createElement('a');a.href=url;a.download=filename;a.click();setTimeout(()=>URL.revokeObjectURL(url),2000)}
function csvColumns(){return columns().filter(c=>c.name!=='Identificador')}
function exportCSV(rows){const cols=columns();csvFile([cols.map(c=>c.name),...rows.map(r=>cols.map(c=>r[c.name]??''))],activeTable+'-exportacion.csv')}
function templateCSV(){csvFile([csvColumns().map(c=>c.name)],activeTable+'-plantilla.csv')}
function parseCSV(text){text=String(text).replace(/^\uFEFF/,'');const first=text.split(/\r?\n/,1)[0];const delimiter=(first.match(/;/g)||[]).length>=(first.match(/,/g)||[]).length?';':',';let rows=[],row=[],value='',quoted=false;for(let i=0;i<text.length;i++){const c=text[i];if(quoted){if(c==='"'&&text[i+1]==='"'){value+='"';i++}else if(c==='"')quoted=false;else value+=c}else if(c==='"'){if(value==='')quoted=true;else value+=c}else if(c===delimiter){row.push(value);value=''}else if(c==='\n'||c==='\r'){if(c==='\r'&&text[i+1]==='\n')i++;row.push(value);if(row.some(x=>x!==''))rows.push(row);row=[];value=''}else value+=c}if(quoted)throw Error('CSV inválido: comillas sin cerrar');row.push(value);if(row.some(x=>x!==''))rows.push(row);return rows}
async function importCSV(){if(!model?.permisos?.insertar)return;const table=activeTable;const input=document.createElement('input');input.type='file';input.accept='.csv,text/csv,text/plain';input.onchange=async()=>{const file=input.files?.[0];if(!file)return;try{const data=parseCSV(await file.text());if(data.length<2)throw Error('El archivo no contiene registros');const header=data.shift().map(v=>v.trim());const valid=new Set(csvColumns().map(c=>c.name));if(new Set(header).size!==header.length)throw Error('Hay columnas duplicadas');const invalid=header.filter(k=>!valid.has(k));if(invalid.length)throw Error('Columnas desconocidas o no importables: '+invalid.join(', '));if(!header.length)throw Error('Faltan columnas');const items=data.map((row,i)=>{if(row.length!==header.length)throw Error('Número de columnas incorrecto en fila '+(i+2));return Object.fromEntries(header.map((h,j)=>[h,row[j]]))});if(!confirm('¿Importar '+items.length+' registros en «'+table+'»? Se crearán registros nuevos y no se sobrescribirán los existentes.'))return;let done=0;const errors=[];for(let i=0;i<items.length;i++){try{await req(API+'?bloque=insertar',{tabla:table,...items[i]});done++}catch(e){errors.push('Fila '+(i+2)+': '+e.message);if(errors.length>=20)break}}if(activeTable===table)await loadTable(table);alert('Importados: '+done+' de '+items.length+'.'+(errors.length?'\nErrores:\n'+errors.join('\n'):'')+'\nLa importación no es transaccional.')}catch(e){alert('Error de CSV: '+e.message)}};input.click()}
async function collectActionParameters(action){
 const definitions=Array.isArray(action.parameters)?action.parameters:[];
 if(!definitions.length)return {};
 return new Promise(resolve=>{
  const overlay=document.createElement('div');overlay.className='erp-config-overlay erp-action-overlay';
  const panel=document.createElement('section');panel.className='erp-config-panel erp-action-dialog';panel.setAttribute('role','dialog');panel.setAttribute('aria-modal','true');
  const head=document.createElement('header');head.className='erp-config-header';const title=document.createElement('h2');title.textContent=action.label||'Acción';head.append(title);
  const form=document.createElement('form');form.className='erp-action-form';const content=document.createElement('div');content.className='erp-config-content';
  for(const def of definitions){if(!/^[a-zA-Z_][a-zA-Z0-9_]*$/.test(def.name||''))continue;const label=document.createElement('label');label.className='erp-config-field';const span=document.createElement('span');span.textContent=def.label||def.name;let input;if(def.type==='textarea')input=document.createElement('textarea');else{input=document.createElement('input');input.type=['number','date','checkbox'].includes(def.type)?def.type:'text'}input.name=def.name;if(def.required)input.required=true;label.append(span,input);content.append(label)}
  const footer=document.createElement('footer');footer.className='erp-config-footer';const cancel=document.createElement('button');cancel.type='button';cancel.textContent='Cancelar';const submit=document.createElement('button');submit.type='submit';submit.textContent='Continuar';cancel.onclick=()=>{overlay.remove();resolve(null)};footer.append(cancel,submit);form.append(content,footer);panel.append(head,form);overlay.append(panel);overlay.onclick=e=>{if(e.target===overlay)cancel.click()};form.onsubmit=e=>{e.preventDefault();const result={};for(const input of form.querySelectorAll('[name]'))result[input.name]=input.type==='checkbox'?input.checked:input.value;overlay.remove();resolve(result)};document.body.append(overlay);form.querySelector('input,textarea')?.focus();
 });
}
function printCustom(action,rows,params){
 const cols=columns(),title=action.printTitle||activeTable;
 const replaceTokens=(template,row)=>String(template).replace(/\{\{\s*(param\.)?([a-zA-Z_][a-zA-Z0-9_]*)\s*\}\}/g,(_,isParam,key)=>escape(isParam?(params[key]??''):(row['__fk__'+key]??row[key]??'')));
 const custom=String(action.printTemplate||'').trim();
 // El HTML se interpreta como plantilla, pero se eliminan scripts, manejadores y elementos activos.
 const sanitizeHTML=html=>{const doc=new DOMParser().parseFromString('<div id="print-root">'+html+'</div>','text/html');const root=doc.getElementById('print-root');const banned=new Set(['SCRIPT','IFRAME','OBJECT','EMBED','FORM','INPUT','BUTTON','META','BASE','LINK','FRAME','FRAMESET','SVG','MATH','VIDEO','AUDIO']);for(const el of [...root.querySelectorAll('*')]){if(banned.has(el.tagName)){el.remove();continue}for(const attr of [...el.attributes]){const name=attr.name.toLowerCase(),val=attr.value.trim();if(name.startsWith('on')||name==='srcdoc'||name==='style'&&/(?:url\s*\(|expression\s*\(|@import)/i.test(val)||['href','src','xlink:href','action','formaction'].includes(name)&&/^(?:javascript|data|vbscript):/i.test(val)){el.removeAttribute(attr.name)}}}return root.innerHTML};
 const htmlMode=action.printFormat==='html';
 const body=custom?rows.map(row=>'<section class="record">'+(htmlMode?sanitizeHTML(replaceTokens(custom,row)):replaceTokens(custom,row).split('\n').join('<br>'))+'</section>').join(''):('<table><thead><tr>'+cols.map(c=>'<th>'+escape(c.name)+'</th>').join('')+'</tr></thead><tbody>'+rows.map(r=>'<tr>'+cols.map(c=>'<td data-inline-field="'+escape(c.name)+'" data-inline-id="'+escape(r.Identificador)+'" title="Doble clic para editar">'+escape(r['__fk__'+c.name]??r[c.name])+'</td>').join('')+'</tr>').join('')+'</tbody></table>');
 const w=window.open('','_blank');if(!w){alert('Permite las ventanas emergentes para imprimir.');return}
 w.document.open();w.document.write('<!doctype html><html lang="es"><head><meta charset="utf-8"><meta http-equiv="Content-Security-Policy" content="object-src \'none\'; base-uri \'none\'"><title>'+escape(title)+'</title><style>body{font:14px sans-serif;margin:32px;color:#43250f}h1{color:#8b4513}table{border-collapse:collapse;width:100%;margin:20px 0}th,td{border:1px solid #bbb;padding:9px;text-align:left}th{background:#f2e7dd}.record{padding:16px 0;border-bottom:1px solid #ddd;white-space:normal}@media print{button{display:none}}</style></head><body><h1>'+escape(title)+'</h1><button onclick="print()">Imprimir</button>'+body+'</body></html>');w.document.close();
}
async function executeCustom(action,rows){
 if(!action||!rows.length){alert('Selecciona al menos un registro.');return}
 rows=rows.filter(r=>personalization.visibleAction(action,r));if(!rows.length){alert('Ningún registro cumple la condición de esta acción.');return}
 const params=await collectActionParameters(action);if(params===null)return;
 if(action.type==='print'){printCustom(action,rows,params);return}
 if(action.type==='duplicate'&&!model.permisos?.insertar){alert('No tienes permiso para crear registros.');return}
 if(action.type==='update'&&!model.permisos?.actualizar){alert('No tienes permiso para modificar registros.');return}
 if(action.type==='update'&&(!columns().some(c=>c.name===action.field)||action.field==='Identificador')){alert('Selecciona un campo válido en la configuración de la acción.');return}
 if(!confirm('¿Ejecutar «'+action.label+'» sobre '+rows.length+' registro(s)?'))return;
 let completed=0;try{for(const r of rows){let payload={tabla:activeTable};if(action.type==='duplicate'){for(const c of columns())if(c.name!=='Identificador')payload[c.name]=r[c.name];await req(API+'?bloque=insertar',payload)}else if(action.type==='update'){payload.Identificador=r.Identificador;payload[action.field]=action.valueFromParameter&&action.parameters?.length?params[action.parameters[0].name]:action.value;await req(API+'?bloque=actualizar',payload)}completed++}await loadTable(activeTable);alert('Acción completada: '+completed+' registro(s).')}catch(e){alert('Completados: '+completed+'. Error: '+e.message);await loadTable(activeTable)}
}
async function form(record){const edit=!!record;const cols=columns().filter(c=>c.name!=='Identificador');const config=personalization.get(activeTable,columns());const customFields=config.customFields||[];const order=config.formLayout||[];cols.sort((a,b)=>{const ia=order.indexOf(a.name),ib=order.indexOf(b.name);return (ia<0?9999:ia)-(ib<0?9999:ib)});const overlay=$('#juDrawerOverlay'),drawer=$('#juDrawer'),body=$('#juDrawerBody');$('#juDrawerMode').textContent=edit?'EDITAR':'CREAR';$('#juDrawerTitle').textContent=(edit?'Editar ':'Nuevo registro: ')+activeTable;body.replaceChildren();const f=document.createElement('form');f.className='ju-form-grid erp-form';let lastSection='';for(const c of cols){const definition=customFields.find(x=>x.name===c.name);const section=definition?.section||'General';if(section!==lastSection){const heading=document.createElement('h3');heading.textContent=section;heading.className='ju-form-full';f.append(heading);lastSection=section}const label=document.createElement('label');const caption=document.createElement('span');caption.textContent=definition?.label||c.name;label.append(caption);const type=String(c.type||'').toLowerCase();let input;const foreign=String(c.name).toLowerCase().endsWith('_id')||c.fk||c.foreign_key;if(definition?.type==='select'){input=document.createElement('select');input.add(new Option('Seleccionar…',''));for(const v of (definition.options||'').split(',').map(x=>x.trim()).filter(Boolean))input.add(new Option(v,v))}else if(definition?.type==='textarea'){input=document.createElement('textarea');input.rows=3}else if(definition?.type==='checkbox'){input=document.createElement('input');input.type='checkbox'}else if(foreign){input=document.createElement('select');input.add(new Option('Seleccionar…',''));try{const opts=await req(API+'?bloque=fk_opciones&tabla='+encodeURIComponent(activeTable)+'&campo='+encodeURIComponent(c.name));for(const o of opts.opciones||[])input.add(new Option(o.texto,o.id))}catch{input=document.createElement('input');input.type='number'}}else{input=document.createElement('input');input.type=/int|real|float|decimal|numeric|double/.test(type)?'number':/date/.test(type)?'date':'text';if(input.type==='number'&&!/int/.test(type))input.step='any'}input.name=c.name;if(input.type==='checkbox')input.checked=String(record?.[c.name]??'')==='1';else input.value=record?.[c.name]??'';if(c.notnull||c.required||definition?.required)input.required=true;label.append(input);f.append(label)}const actions=document.createElement('div');actions.className='ju-form-full ju-button-row';const save=document.createElement('button');save.type='submit';save.className='ju-btn';save.textContent=edit?'Guardar cambios':'Crear registro';const cancel=document.createElement('button');cancel.type='button';cancel.className='ju-btn secondary';cancel.textContent='Cancelar';cancel.onclick=close;actions.append(save,cancel);f.append(actions);const card=document.createElement('article');card.className='ju-card';card.append(f);body.append(card);drawer.classList.add('visible','abierto');overlay.classList.add('visible','abierto');drawer.setAttribute('aria-hidden','false');overlay.setAttribute('aria-hidden','false');f.onsubmit=async e=>{e.preventDefault();const data=Object.fromEntries(new FormData(f));for(const cb of f.querySelectorAll('input[type=checkbox][name]'))data[cb.name]=cb.checked?'1':'0';data.tabla=activeTable;if(edit)data.Identificador=record.Identificador;try{await req(API+'?bloque='+(edit?'actualizar':'insertar'),data);close();await loadTable(activeTable)}catch(err){alert(err.message)}}}
function close(){for(const id of ['#juDrawer','#juDrawerOverlay']){$(id).classList.remove('visible','abierto');$(id).setAttribute('aria-hidden','true')}}
async function remove(id){if(!confirm('¿Eliminar este registro?'))return;try{await req(API+'?bloque=eliminar',{tabla:activeTable,Identificador:Number(id)});await loadTable(activeTable)}catch(e){alert(e.message)}}

function setupColumns(){
 for(const column of [$('#navegacion'),$('#entidades')]){
  const btn=column.querySelector('.minimizar');if(!btn)continue;
  const key='jocarsa-erp-width-'+column.id;const stored=Number(localStorage.getItem(key));
  if(stored>=100&&stored<=600){column.style.width=stored+'px';column.style.flexBasis=stored+'px'}
  btn.addEventListener('click',()=>{
   if(column.classList.contains('compacto')){column.classList.remove('compacto');const w=Number(column.dataset.savedWidth)||Number(localStorage.getItem(key))||240;column.style.width=w+'px';column.style.flexBasis=w+'px';btn.textContent='◀';btn.setAttribute('aria-label','Minimizar columna')}
   else{column.dataset.savedWidth=column.getBoundingClientRect().width;column.classList.add('compacto');column.style.width='52px';column.style.flexBasis='52px';btn.textContent='▶';btn.setAttribute('aria-label','Expandir columna')}
  });
 }
 for(const splitter of document.querySelectorAll('main > .separador')){
  splitter.setAttribute('role','separator');splitter.setAttribute('aria-orientation','vertical');splitter.tabIndex=0;
  const column=splitter.previousElementSibling,key='jocarsa-erp-width-'+column.id;
  const setWidth=w=>{const value=Math.max(110,Math.min(600,w));column.style.width=value+'px';column.style.flexBasis=value+'px';column.dataset.savedWidth=value;localStorage.setItem(key,String(value))};
  splitter.addEventListener('pointerdown',e=>{
   if(e.button!==0)return;e.preventDefault();if(column.classList.contains('compacto'))return;
   const start=e.clientX,width=column.getBoundingClientRect().width;splitter.setPointerCapture(e.pointerId);column.style.transition='none';document.body.classList.add('redimensionando');
   const move=ev=>setWidth(width+ev.clientX-start);
   const end=()=>{splitter.removeEventListener('pointermove',move);splitter.removeEventListener('pointerup',end);splitter.removeEventListener('pointercancel',end);column.style.transition='';document.body.classList.remove('redimensionando')};
   splitter.addEventListener('pointermove',move);splitter.addEventListener('pointerup',end);splitter.addEventListener('pointercancel',end);
  });
  splitter.addEventListener('keydown',e=>{if(e.key==='ArrowLeft'||e.key==='ArrowRight'){e.preventDefault();setWidth(column.getBoundingClientRect().width+(e.key==='ArrowRight'?16:-16))}});
 }
}
setupColumns();

$('#juDrawerClose').onclick=close;$('#juDrawerOverlay').onclick=close;document.querySelector('#buscador')?.remove();document.querySelector('#nuevo')?.remove();$('#cerrar-sesion').onclick=async e=>{e.preventDefault();await req(AUTH+'?action=logout',{});location.href='login.html'};
(async()=>{try{const state=await req(AUTH+'?action=status');if(!state.installed){stage.innerHTML='<div class="erp-empty"><h2>Crimson no está instalado</h2><a href="../backend/instalacion/">Abrir instalación</a></div>';return}if(!state.authenticated){location.href='login.html';return}$('#usuario p').textContent=state.user?.nombre||state.user?.username||'Usuario';await loadSettings();await loadModules()}catch(e){error(e)}})();
})();
