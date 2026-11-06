(()=>{'use strict';
const KEY='entity-universe-v2',OLD='entity-universe-v1';
const categoriesLayer=document.querySelector('#categoriesLayer'),viewport=document.querySelector('#viewport'),world=document.querySelector('#world'),nodes=document.querySelector('#nodes'),svg=document.querySelector('#wires'),dialog=document.querySelector('#editor');
const uid=()=>globalThis.crypto?.randomUUID?.()||`${Date.now()}-${Math.random()}`;
const empty=()=>({entities:[],links:[]});
const PROJECT_KEY='entity-universe-projects-v7';
let data=empty(),selected=null,editing=null,drag=null,view={x:Math.round(innerWidth/2-130),y:110,z:1};
let store={activeId:null,projects:[]};
// Filtros de presentación: no se envían al motor ni modifican las tablas.
let searchText='',inactiveOpacity=.18;
const categoryVisibility=new Map();
const categoryKey=v=>String(v).trim().toLocaleLowerCase();
const searchNormalize=v=>String(v??'').normalize('NFD').replace(/[\u0300-\u036f]/g,'').toLocaleLowerCase();
function entityCategories(n){return normalizeCategories(n.categories).map(categoryKey);}
function entityEnabled(n){const cats=entityCategories(n);return !cats.length||cats.some(k=>categoryVisibility.get(k)!==false);}
function entityMatches(n){return !searchText||searchNormalize([n.title,...(n.properties||[]).map(p=>p.text),...normalizeCategories(n.categories)].join(' ')).includes(searchNormalize(searchText));}
let nestedHierarchy={},nestedSelected=null;
const nestedCollapsed=new Set();
function collectionCounts(){const counts=new Map();for(const n of data.entities)for(const c of normalizeCategories(n.categories)){const k=categoryKey(c);if(!counts.has(k))counts.set(k,{name:c,count:0});counts.get(k).count++;}return counts;}
function hierarchyNodes(){const entries=Object.entries(nestedHierarchy||{});const names=new Set(entries.map(([name])=>name));const children=new Map();for(const [name,meta] of entries){const parent=meta&&typeof meta==='object'?meta.parent:null;const key=parent&&names.has(parent)&&parent!==name?parent:null;if(!children.has(key))children.set(key,[]);children.get(key).push(name);}for(const arr of children.values())arr.sort((a,b)=>a.localeCompare(b,'es'));return children;}
function refreshCategorySwitches(){
 const counts=collectionCounts();for(const key of [...categoryVisibility.keys()])if(!counts.has(key))categoryVisibility.delete(key);
 const host=document.querySelector('#categorySwitches');host.replaceChildren();
 const hierarchy=hierarchyNodes(),visited=new Set();
 const render=(name,depth)=>{
  if(visited.has(name))return;visited.add(name);
  const key=categoryKey(name),info=counts.get(key),kids=hierarchy.get(name)||[];
  const row=document.createElement('div');row.className='collection-tree-row'+(nestedSelected===name?' selected':'');row.style.setProperty('--depth',depth);
  const toggle=document.createElement('button');toggle.type='button';toggle.className='collection-expander';toggle.textContent=kids.length?(nestedCollapsed.has(name)?'▸':'▾'):'·';toggle.disabled=!kids.length;toggle.title=kids.length?'Expandir o contraer':'';
  toggle.onclick=()=>{nestedCollapsed.has(name)?nestedCollapsed.delete(name):nestedCollapsed.add(name);refreshCategorySwitches();};
  const input=document.createElement('input');input.type='checkbox';input.checked=categoryVisibility.get(key)!==false;input.title='Mostrar u ocultar entidades de '+name;
  input.onchange=()=>{categoryVisibility.set(key,input.checked);applyVisualFilters();refreshCategorySwitches();};
  const label=document.createElement('button');label.type='button';label.className='collection-tree-name';label.textContent=name;label.title='Seleccionar colección '+name;
  label.onclick=()=>{nestedSelected=name;refreshCategorySwitches();};
  const count=document.createElement('span');count.className='category-count';count.textContent=String(info?.count??0);count.title='Entidades directamente asignadas';
  row.append(toggle,input,label,count);host.append(row);
  if(!nestedCollapsed.has(name))for(const child of kids)render(child,depth+1);
 };
 for(const name of hierarchy.get(null)||[])render(name,0);
 for(const name of [...counts.values()].map(v=>v.name).sort((a,b)=>a.localeCompare(b,'es')))if(!visited.has(name))render(name,0);
 for(const name of Object.keys(nestedHierarchy))if(!visited.has(name))render(name,0);
 if(!host.children.length){const msg=document.createElement('small');msg.textContent='Sin colecciones';host.append(msg);}
 const path=[];let cur=nestedSelected;const seen=new Set();while(cur&&!seen.has(cur)){seen.add(cur);path.unshift(cur);cur=nestedHierarchy[cur]?.parent||null;}
 document.querySelector('#collectionBreadcrumb').textContent=path.length?path.join(' / '):'Selecciona una colección';
 document.querySelector('#nestedCreate').disabled=!nestedSelected;
 document.querySelector('#nestedMove').disabled=!nestedSelected;
}
function applyVisualFilters(){
 for(const el of nodes.children){const n=find(el.dataset.id);if(!n)continue;const enabled=entityEnabled(n),match=entityMatches(n);const opacity=enabled&&(!searchText||match)?1:inactiveOpacity;
  el.style.opacity=String(opacity);el.classList.toggle('is-dimmed',opacity<1);el.classList.toggle('search-match',!!searchText&&match&&enabled);
  el.style.pointerEvents=opacity<=.01?'none':'';
 }
 for(const shape of categoriesLayer.children){const key=shape.dataset.category;shape.style.opacity=String(categoryVisibility.get(key)===false?inactiveOpacity:1);}
 for(const path of svg.children){if(path.dataset.from){const a=find(path.dataset.from),b=find(path.dataset.to);path.style.opacity=String(a&&b&&entityEnabled(a)&&entityEnabled(b)&&(!searchText||(entityMatches(a)&&entityMatches(b)))?1:inactiveOpacity);}}
}
document.querySelector('#entitySearch').addEventListener('input',e=>{searchText=e.target.value.trim();applyVisualFilters();});
document.querySelector('#inactiveOpacity').addEventListener('input',e=>{inactiveOpacity=Number(e.target.value)/100;document.querySelector('#opacityValue').textContent=e.target.value+'%';applyVisualFilters();});
for(const [id,visible] of [['allCategories',true],['noCategories',false]]){
 document.querySelector('#'+id).addEventListener('click',()=>{
  for(const n of data.entities)for(const c of normalizeCategories(n.categories))categoryVisibility.set(categoryKey(c),visible);
  refreshCategorySwitches();applyVisualFilters();
 });
}
 document.querySelector('#resetFilters').addEventListener('click',()=>{searchText='';document.querySelector('#entitySearch').value='';categoryVisibility.clear();refreshCategorySwitches();applyVisualFilters();});

const activeProject=()=>store.projects.find(p=>p.id===store.activeId);
function loadProject(){const p=activeProject();if(!p)return;data=p.data;view={...p.view};selected=null;drag=null;lastTap=null;transform();render();refreshProjectSelect();refreshNestedTree();}
let saveTimer=null, busy=false, pendingDeletion=false;
async function api(action,payload={}){const r=await fetch('api.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action,...payload})});const j=await r.json();if(!r.ok||!j.ok)throw Error(j.error||'Error de servidor');return j.result;}
function persist(){const p=activeProject();if(!p)return;p.data=data;p.view={...view};clearTimeout(saveTimer);
 saveTimer=setTimeout(async()=>{
  const confirmDelete=pendingDeletion;pendingDeletion=false;
  try{await api('save',{project:p.id,data:p.data,view:p.view,confirm_delete:confirmDelete});}
  catch(e){alert('No se guardó: '+e.message);try{await refresh();}catch(err){console.error(err);}}
 },450);
}
async function refresh(){const result=await api('state');store=result;loadProject();}
// The native PHP function returns a JSON string with {name:{parent,tables}}.
async function refreshNestedTree(){
 if(!store.activeId)return;
 try{
  let tree=await api('nested_tree',{project:store.activeId});
  if(typeof tree==='string')tree=JSON.parse(tree);
  if(!tree||typeof tree!=='object'||Array.isArray(tree))throw Error('Formato de árbol inesperado');
  nestedHierarchy=tree;
  if(nestedSelected&&!Object.hasOwn(tree,nestedSelected))nestedSelected=null;
  refreshCategorySwitches();drawWires();
 }catch(e){document.querySelector('#collectionBreadcrumb').textContent='Jerarquía no disponible: '+e.message;}
}
document.querySelector('#nestedRefresh').onclick=refreshNestedTree;
document.querySelector('#sidebarCollapse').onclick=()=>{document.querySelector('#designerLayout').classList.toggle('sidebar-closed');document.querySelector('#sidebarCollapse').textContent=document.querySelector('#designerLayout').classList.contains('sidebar-closed')?'›':'‹';drawWires();};
document.querySelector('#nestedCreate').onclick=async()=>{
 const parent=nestedSelected;if(!parent)return alert('Selecciona primero la colección padre');
 const name=prompt('Nueva subcolección dentro de '+parent);if(!name?.trim())return;
 try{await api('nested_create',{project:store.activeId,parent,name:name.trim()});nestedCollapsed.delete(parent);nestedSelected=name.trim();await refresh();}catch(e){alert(e.message);}
};
document.querySelector('#nestedMove').onclick=async()=>{
 const name=nestedSelected;if(!name)return alert('Selecciona la colección que quieres mover');
 const parent=prompt('Nuevo padre para '+name+' (deja vacío para raíz)');if(parent===null)return;
 if(!parent.trim())return alert('Selecciona una colección padre existente');
 if(name===parent.trim())return alert('Una colección no puede ser su propio padre');
 try{await api('nested_move',{project:store.activeId,name,parent:parent.trim()});nestedCollapsed.delete(parent.trim());await refreshNestedTree();}catch(e){alert(e.message);}
};
async function loadRows(id){const n=find(id);const rows=await api('rows',{project:store.activeId,entity:id});n.rows=rows;renderRecords();}
async function updateCell(entity,row,prop,value){await api('cell',{project:store.activeId,entity,row,prop,value});}

function refreshProjectSelect(){const select=document.querySelector('#projectSelect');select.replaceChildren();for(const p of store.projects){const option=document.createElement('option');option.value=p.id;option.textContent=p.name;select.append(option);}select.value=store.activeId;document.querySelector('#deleteProject').disabled=store.projects.length===1;}
function normalizeProps(props){return (props||[]).map(p=>typeof p==='string'?{id:uid(),text:p}:p).filter(p=>p&&typeof p.id==='string'&&typeof p.text==='string');}
data.entities.forEach(n=>{n.properties=normalizeProps(n.properties);n.categories=normalizeCategories(n.categories);});
function normalizeCategories(value){return [...new Set((Array.isArray(value)?value.join(','):String(value||'')).split(',').map(x=>x.trim()).filter(Boolean))];}
const save=()=>persist();
const esc=s=>String(s).replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
const find=id=>data.entities.find(n=>n.id===id);
function transform(){world.style.transform=`translate(${view.x}px,${view.y}px) scale(${view.z})`;viewport.style.backgroundPosition=`${view.x}px ${view.y}px`;viewport.style.backgroundSize=`${28*view.z}px ${28*view.z}px`;}
function pos(e){const r=viewport.getBoundingClientRect();return{x:(e.clientX-r.left-view.x)/view.z,y:(e.clientY-r.top-view.y)/view.z};}
function endpoint(entityId,propId,side){const slot=Array.from(nodes.querySelectorAll('.slot')).find(s=>s.dataset.entity===entityId&&s.dataset.prop===propId&&s.dataset.side===side);if(!slot)return null;const sr=slot.getBoundingClientRect(),vr=viewport.getBoundingClientRect();return{x:(sr.left+sr.width/2-vr.left-view.x)/view.z,y:(sr.top+sr.height/2-vr.top-view.y)/view.z};}
function curve(a,b){const d=Math.max(55,Math.abs(b.x-a.x)*.48);return`M${a.x},${a.y} C${a.x+d},${a.y} ${b.x-d},${b.y} ${b.x},${b.y}`;}
function wire(a,b,color,dashed=false){const p=document.createElementNS('http://www.w3.org/2000/svg','path');p.setAttribute('d',curve(a,b));p.setAttribute('fill','none');p.setAttribute('stroke',color);p.setAttribute('stroke-width','3');p.setAttribute('stroke-linecap','round');if(dashed)p.setAttribute('stroke-dasharray','7 6');svg.append(p);}
// Contornos orgánicos: envolvente convexa de muestras elípticas y suavizado Chaikin.
function convexHull(points){
 const cross=(o,a,b)=>(a.x-o.x)*(b.y-o.y)-(a.y-o.y)*(b.x-o.x);
 const sorted=points.sort((a,b)=>a.x-b.x||a.y-b.y),lower=[],upper=[];
 for(const p of sorted){while(lower.length>1&&cross(lower[lower.length-2],lower[lower.length-1],p)<=0)lower.pop();lower.push(p);}
 for(const p of [...sorted].reverse()){while(upper.length>1&&cross(upper[upper.length-2],upper[upper.length-1],p)<=0)upper.pop();upper.push(p);}
 lower.pop();upper.pop();return lower.concat(upper);
}
function soften(points,rounds=3){
 for(let k=0;k<rounds;k++){const next=[];for(let i=0;i<points.length;i++){const a=points[i],b=points[(i+1)%points.length];next.push({x:a.x*.75+b.x*.25,y:a.y*.75+b.y*.25},{x:a.x*.25+b.x*.75,y:a.y*.25+b.y*.75});}points=next;}return points;
}
function drawCategories(){
 categoriesLayer.replaceChildren();
 const groups=new Map();
 for(const n of data.entities)for(const name of normalizeCategories(n.categories)){
  const key=categoryKey(name);if(!groups.has(key))groups.set(key,{name,entities:[]});groups.get(key).entities.push(n);
 }
 const hierarchy=nestedHierarchy||{};
 const nodeById=new Map(Array.from(nodes.children).map(el=>[el.dataset.id,el]));
 const children=new Map();
 for(const [name,meta] of Object.entries(hierarchy)){
  const parent=meta?.parent&&hierarchy[meta.parent]?meta.parent:null;
  if(!children.has(parent))children.set(parent,[]);children.get(parent).push(name);
 }
 const depthMemo=new Map();
 function depth(name,seen=new Set()){
  if(depthMemo.has(name))return depthMemo.get(name);
  if(seen.has(name))return 0;seen.add(name);
  const parent=hierarchy[name]?.parent;
  const d=parent&&hierarchy[parent]?Math.min(30,1+depth(parent,seen)):0;
  depthMemo.set(name,d);return d;
 }
 const descendantMemo=new Map();
 function members(name,seen=new Set()){
  if(descendantMemo.has(name))return descendantMemo.get(name);
  if(seen.has(name))return [];
  seen.add(name);
  const own=groups.get(categoryKey(name))?.entities||[];
  const all=new Map(own.map(n=>[n.id,n]));
  for(const child of children.get(name)||[])for(const n of members(child,new Set(seen)))all.set(n.id,n);
  const result=[...all.values()];descendantMemo.set(name,result);return result;
 }
 const names=[...new Set([...Object.keys(hierarchy),...[...groups.values()].map(g=>g.name)])];
 names.sort((a,b)=>depth(a)-depth(b)||a.localeCompare(b,'es'));
 const shapes=[];
 for(let index=0;index<names.length;index++){
  const name=names[index],dlevel=depth(name),entities=members(name);
  if(!entities.length)continue;
  const boxes=entities.map(n=>{const el=nodeById.get(n.id);return{x:n.x,y:n.y,w:el?.offsetWidth||260,h:el?.offsetHeight||110};});
  const pad=35+Math.max(0,5-dlevel)*22,samples=[];
  for(const b of boxes){const cx=b.x+b.w/2,cy=b.y+b.h/2,rx=b.w/2+pad,ry=b.h/2+pad;
   for(let i=0;i<32;i++){const theta=2*Math.PI*i/32;samples.push({x:cx+rx*Math.cos(theta),y:cy+ry*Math.sin(theta)});}
  }
  // A parent includes all descendants; the extra nesting offset keeps its boundary outside the children.
  const contour=soften(convexHull(samples));if(contour.length<3)continue;
  const d='M'+contour.map(pt=>`${pt.x.toFixed(2)},${pt.y.toFixed(2)}`).join(' L')+' Z';
  const hue=(index*71+210)%360,color=`hsl(${hue} 75% 65%)`;
  const path=document.createElementNS('http://www.w3.org/2000/svg','path');
  for(const [k,v] of Object.entries({d,fill:color,'fill-opacity':String(Math.max(.025,.065-dlevel*.007)),stroke:color,'stroke-width':String(Math.max(1.5,2.8-dlevel*.18)),'stroke-dasharray':dlevel?'6 5':'10 7','stroke-opacity':'.85','stroke-linejoin':'round'}))path.setAttribute(k,v);
  path.dataset.category=categoryKey(name);
  const top=contour.reduce((a,b)=>b.y<a.y?b:a);
  const label=document.createElementNS('http://www.w3.org/2000/svg','text');
  for(const [k,v] of Object.entries({x:top.x,y:top.y-10,fill:color,'font-size':'15','font-weight':'700','text-anchor':'middle'}))label.setAttribute(k,v);
  label.textContent=name;label.dataset.category=categoryKey(name);
  label.classList.add('group-drag-handle');label.style.pointerEvents='auto';label.style.cursor='move';
  shapes.push({path,label,depth:dlevel});
 }
 // Draw parents first, then children above them. No entity coordinates are changed.
 shapes.sort((a,b)=>a.depth-b.depth);
 for(const shape of shapes)categoriesLayer.append(shape.path,shape.label);
}

function drawWires(){drawCategories();svg.replaceChildren();for(const l of data.links){const a=endpoint(l.from,l.fromProp,'out'),b=endpoint(l.to,l.toProp,'in');if(a&&b){wire(a,b,'#91a1ff');const path=svg.lastElementChild;path.dataset.from=l.from;path.dataset.to=l.to;}}if(drag?.type==='link'){const a=endpoint(drag.from,drag.fromProp,drag.side);if(a)wire(drag.side==='out'?a:drag.to,drag.side==='out'?drag.to:a,'#69e7b8',true);}applyVisualFilters();}
function render(){nodes.replaceChildren();refreshCategorySwitches();for(const n of data.entities){const el=document.createElement('div');el.className='node'+(selected===n.id?' selected':'');el.dataset.id=n.id;el.style.left=n.x+'px';el.style.top=n.y+'px';el.innerHTML=`<div class="node-head"><span>${esc(n.title)}</span><button type="button" class="records-btn" data-records="${esc(n.id)}" title="Ver y editar datos de ${esc(n.title)}" aria-label="Abrir registros de ${esc(n.title)}">▦ <span>${(n.rows||[]).length}</span></button></div><div class="node-props">${n.properties.map(p=>{const i=p.text.indexOf(':');return`<div class="prop" data-prop="${esc(p.id)}"><span class="slot in" data-entity="${esc(n.id)}" data-prop="${esc(p.id)}" data-side="in" title="Entrada: ${esc(p.text)}"></span><span class="prop-name">${esc(i<0?p.text:p.text.slice(0,i))}</span><span class="prop-type">${esc(i<0?'':p.text.slice(i+1).trim())}</span><span class="slot out" data-entity="${esc(n.id)}" data-prop="${esc(p.id)}" data-side="out" title="Salida: ${esc(p.text)}"></span></div>`}).join('')||'<div class="no-props">Añade propiedades para conectar entidades</div>'}</div>`;nodes.append(el);}drawWires();document.querySelector('.hint').textContent=data.entities.length?'Arrastra entre conectores de propiedades para relacionarlas · Arrastra el título para mover · Doble clic para editar · Rueda para ampliar':'El espacio está vacío. Pulsa + Entidad para crear la primera.';}
function renderEntityMemberships(existing=[]){
 const host=document.querySelector('#entityMemberships');host.replaceChildren();
 const counts=collectionCounts(),children=hierarchyNodes(),seen=new Set();
 const selected=new Set(normalizeCategories(existing).map(categoryKey));
 const draw=(name,depth)=>{
  if(seen.has(name))return;seen.add(name);
  const row=document.createElement('label');row.className='entity-membership-row';row.style.setProperty('--depth',depth);
  const check=document.createElement('input');check.type='checkbox';check.value=name;check.checked=selected.has(categoryKey(name));
  const text=document.createElement('span');text.textContent=name;
  const count=document.createElement('small');count.textContent=String(counts.get(categoryKey(name))?.count??0);
  row.append(check,text,count);host.append(row);
  for(const child of children.get(name)||[])draw(child,depth+1);
 };
 for(const root of children.get(null)||[])draw(root,0);
 for(const info of counts.values())if(!seen.has(info.name))draw(info.name,0);
 for(const name of Object.keys(nestedHierarchy))if(!seen.has(name))draw(name,0);
 if(!host.children.length){const msg=document.createElement('small');msg.textContent='No hay colecciones disponibles';host.append(msg);}
}
function selectedEntityMemberships(){return [...document.querySelectorAll('#entityMemberships input:checked')].map(el=>el.value);}
document.querySelector('#membershipAll').onclick=()=>document.querySelectorAll('#entityMemberships input').forEach(el=>el.checked=true);
document.querySelector('#membershipNone').onclick=()=>document.querySelectorAll('#entityMemberships input').forEach(el=>el.checked=false);
const PROPERTY_TYPES=['texto','entero','real','decimal','booleano','fecha'];
function appendPropertyRow(property={id:uid(),text:'',name:'',type:'texto'}){
 const host=document.querySelector('#propertyRows');const tr=document.createElement('tr');tr.dataset.id=property.id||uid();
 const i=String(property.text||'').indexOf(':');const name=property.name??(i<0?String(property.text||''):property.text.slice(0,i).trim());const type=property.type??(i<0?'texto':property.text.slice(i+1).trim());
 const tdName=document.createElement('td'),input=document.createElement('input');input.className='property-name';input.placeholder='nombre';input.value=name;input.required=true;tdName.append(input);
 const tdType=document.createElement('td'),select=document.createElement('select');select.className='property-type';const known=PROPERTY_TYPES.some(t=>t.toLowerCase()===String(type).toLowerCase());
 for(const t of (known?PROPERTY_TYPES:[...PROPERTY_TYPES,type])){const opt=document.createElement('option');opt.value=t;opt.textContent=t.toUpperCase();select.append(opt);}select.value=known?(PROPERTY_TYPES.find(t=>t.toLowerCase()===String(type).toLowerCase())):type;tdType.append(select);
 const tdActions=document.createElement('td');tdActions.className='property-actions';for(const [label,action,title] of [['↑','up','Subir'],['↓','down','Bajar'],['×','remove','Eliminar propiedad']]){const b=document.createElement('button');b.type='button';b.textContent=label;b.title=title;b.dataset.action=action;tdActions.append(b);}tr.append(tdName,tdType,tdActions);host.append(tr);
}
document.querySelector('#addProperty').onclick=()=>appendPropertyRow();
document.querySelector('#propertyRows').onclick=e=>{const btn=e.target.closest('button[data-action]');if(!btn)return;const row=btn.closest('tr'),host=row.parentNode;if(btn.dataset.action==='remove')row.remove();else if(btn.dataset.action==='up'&&row.previousElementSibling)host.insertBefore(row,row.previousElementSibling);else if(btn.dataset.action==='down'&&row.nextElementSibling)host.insertBefore(row.nextElementSibling,row);};
function collectProperties(){const rows=[...document.querySelectorAll('#propertyRows tr')];const names=new Set();return rows.map(row=>{const name=row.querySelector('.property-name').value.trim(),type=row.querySelector('.property-type').value;if(!/^[a-zA-Z_\u00c0-\u024f][\w\u00c0-\u024f]*$/.test(name))throw Error('Nombre de propiedad no válido: '+name);if(names.has(name.toLowerCase()))throw Error('Propiedad duplicada: '+name);names.add(name.toLowerCase());return{id:row.dataset.id,text:name+': '+type};});}
function edit(id){editing=id;const n=find(id);document.querySelector('#formTitle').textContent=n?'Editar entidad':'Nueva entidad';document.querySelector('#title').value=n?.title||'';renderEntityMemberships(n?.categories||[]);document.querySelector('#propertyRows').replaceChildren();(n?.properties||[]).forEach(appendPropertyRow);document.querySelector('#deleteEntity').hidden=!n;dialog.showModal();document.querySelector('#title').focus();}
document.querySelector('#add').onclick=()=>edit(null);document.querySelector('#cancel').onclick=()=>dialog.close();
document.querySelector('#entityForm').onsubmit=e=>{e.preventDefault();const title=document.querySelector('#title').value.trim();if(!title)return;let properties;try{properties=collectProperties();}catch(err){alert(err.message);return;}if(editing){const n=find(editing);const old=n.properties;const used=new Set();n.title=title;n.categories=selectedEntityMemberships();n.properties=properties;const valid=new Set(n.properties.map(p=>p.id));data.links=data.links.filter(l=>(l.from!==n.id||valid.has(l.fromProp))&&(l.to!==n.id||valid.has(l.toProp)));}else{const r=viewport.getBoundingClientRect(),p=pos({clientX:r.left+r.width/2,clientY:r.top+r.height/2});const n={id:uid(),title,categories:selectedEntityMemberships(),properties,x:p.x-130,y:p.y-65};data.entities.push(n);selected=n.id;}save();render();dialog.close();};
document.querySelector('#deleteEntity').onclick=()=>{if(!editing)return;if(!confirm('¿Eliminar definitivamente esta tabla y TODOS sus registros de Jocarsa BBDD?'))return;pendingDeletion=true;data.entities=data.entities.filter(n=>n.id!==editing);data.links=data.links.filter(l=>l.from!==editing&&l.to!==editing);selected=null;save();render();dialog.close();};

nodes.addEventListener('click',e=>{const b=e.target.closest('.records-btn');if(b){e.stopPropagation();openRecords(b.dataset.records);}});
viewport.addEventListener('pointerdown',e=>{if(e.target.closest('.records-btn'))return;if(e.button!==0&&e.button!==1)return;const groupLabel=e.target.closest('.group-drag-handle');
 if(groupLabel&&e.button===0){
  const key=groupLabel.dataset.category;
  const members=data.entities.filter(n=>entityCategories(n).includes(key));
  drag={type:'group',start:pos(e),members:members.map(n=>({id:n.id,x:n.x,y:n.y}))};
  e.preventDefault();
 }else{
 const slot=e.target.closest('.slot'),node=e.target.closest('.node');if(slot&&e.button===0){drag={type:'link',from:slot.dataset.entity,fromProp:slot.dataset.prop,side:slot.dataset.side,to:pos(e)};e.preventDefault();}else if(node&&e.button===0){selected=node.dataset.id;const n=find(selected);drag={type:'node',id:n.id,start:pos(e),x:n.x,y:n.y,clientX:e.clientX,clientY:e.clientY};nodes.querySelectorAll('.node').forEach(el=>el.classList.toggle('selected',el.dataset.id===selected));}else{drag={type:'pan',x:e.clientX,y:e.clientY,vx:view.x,vy:view.y};viewport.classList.add('panning');}}viewport.setPointerCapture(e.pointerId);});
viewport.addEventListener('pointermove',e=>{if(!drag)return;if(drag.type==='pan'){view.x=drag.vx+e.clientX-drag.x;view.y=drag.vy+e.clientY-drag.y;transform();}else if(drag.type==='group'){
  const p=pos(e),dx=p.x-drag.start.x,dy=p.y-drag.start.y;
  for(const member of drag.members){const n=find(member.id);if(!n)continue;n.x=member.x+dx;n.y=member.y+dy;
   const el=Array.from(nodes.children).find(el=>el.dataset.id===n.id);
   if(el){el.style.left=n.x+'px';el.style.top=n.y+'px';}
  }
  drawWires();
 }else if(drag.type==='node'){const n=find(drag.id),p=pos(e);n.x=drag.x+p.x-drag.start.x;n.y=drag.y+p.y-drag.start.y;const el=Array.from(nodes.children).find(el=>el.dataset.id===n.id);if(el){el.style.left=n.x+'px';el.style.top=n.y+'px';}drawWires();}else{drag.to=pos(e);drawWires();}});
let lastTap=null;
function end(e){
 if(!drag)return;
 const action=drag;
 if(viewport.hasPointerCapture(e.pointerId))viewport.releasePointerCapture(e.pointerId);
 if(action.type==='link'&&e.type==='pointerup'){
   const target=document.elementsFromPoint(e.clientX,e.clientY).map(el=>el.closest?.('.slot')).find(Boolean);
   if(target&&target.dataset.entity!==action.from&&target.dataset.side!==action.side){
     const from=action.side==='out'?{id:action.from,prop:action.fromProp}:{id:target.dataset.entity,prop:target.dataset.prop};
     const to=action.side==='out'?{id:target.dataset.entity,prop:target.dataset.prop}:{id:action.from,prop:action.fromProp};
     if(!data.links.some(l=>l.from===from.id&&l.fromProp===from.prop&&l.to===to.id&&l.toProp===to.prop))data.links.push({id:uid(),from:from.id,fromProp:from.prop,to:to.id,toProp:to.prop});
   }
 }
 if(action.type==='node'&&e.type==='pointerup'){
   const distance=Math.hypot(e.clientX-action.clientX,e.clientY-action.clientY);
   if(distance<6){
     const now=Date.now();
     if(lastTap&&lastTap.id===action.id&&now-lastTap.time<450){lastTap=null;drag=null;edit(action.id);return;}
     lastTap={id:action.id,time:now};
   }else lastTap=null;
 }
 drag=null;viewport.classList.remove('panning');
 if(action.type==='node'||action.type==='link'||action.type==='group')save();
 // Do not recreate nodes on pointerup: that prevents native double-click.
 drawWires();
}
viewport.addEventListener('pointerup',end);viewport.addEventListener('pointercancel',end);
viewport.addEventListener('wheel',e=>{e.preventDefault();const r=viewport.getBoundingClientRect(),x=e.clientX-r.left,y=e.clientY-r.top,old=view.z;view.z=Math.max(.3,Math.min(2.5,view.z*Math.exp(-e.deltaY*.001)));view.x=x-(x-view.x)*view.z/old;view.y=y-(y-view.y)*view.z/old;transform();drawWires();},{passive:false});
document.querySelector('#fit').onclick=()=>{if(!data.entities.length){view={x:viewport.clientWidth/2-130,y:100,z:1};transform();save();return;}const minX=Math.min(...data.entities.map(n=>n.x)),maxX=Math.max(...data.entities.map(n=>n.x+260)),minY=Math.min(...data.entities.map(n=>n.y)),maxY=Math.max(...data.entities.map(n=>n.y+75+n.properties.length*36));view.z=Math.min(1.2,Math.max(.3,Math.min((viewport.clientWidth-90)/(maxX-minX),(viewport.clientHeight-90)/(maxY-minY))));view.x=(viewport.clientWidth-(minX+maxX)*view.z)/2;view.y=(viewport.clientHeight-(minY+maxY)*view.z)/2;transform();drawWires();save();};
document.querySelector('#export').onclick=async()=>{try{const backup=await api('export_backup',{project:store.activeId});downloadText(JSON.stringify(backup,null,2),(activeProject().name.replace(/[^a-z0-9_-]+/gi,'-')||'proyecto')+'-backup.json','application/json');}catch(err){alert('Error al exportar: '+err.message);}};
// Exportación SQL: respeta los tipos y longitudes escritos en cada propiedad.
function sqlIdentifier(name,engine){const q=engine==='mysql'?'`':'"';return q+String(name).replaceAll(q,q+q)+q;}
function sqlType(raw,engine){
 const t=String(raw||'').trim();if(!t)return engine==='mysql'?'TEXT':'TEXT';
 const match=t.match(/^([\p{L}_][\p{L}\p{N}_ ]*)(?:\s*\(\s*(\d+)\s*(?:,\s*(\d+)\s*)?\))?(.*)$/u);
 if(!match)throw Error('Tipo no válido: '+t);
 const base=match[1].trim().toUpperCase().replace(/\s+/g,' '),length=match[2],scale=match[3],suffix=match[4].trim();
 if(suffix&&!/^(NOT NULL|NULL|UNIQUE|DEFAULT\s+(?:NULL|CURRENT_TIMESTAMP|[-+]?\d+(?:\.\d+)?|'(?:[^']|'')*')|UNSIGNED)(?:\s+(?:NOT NULL|NULL|UNIQUE|DEFAULT\s+(?:NULL|CURRENT_TIMESTAMP|[-+]?\d+(?:\.\d+)?|'(?:[^']|'')*')|UNSIGNED))*$/i.test(suffix))throw Error('Modificador de tipo no admitido: '+suffix);
 const aliases={ENTERO:'INTEGER',ENTIER:'INTEGER',INT:'INTEGER',TEXTO:'TEXT',CADENA:'VARCHAR',STRING:'VARCHAR',BOOLEANO:'BOOLEAN',BOOL:'BOOLEAN',REAL:'REAL',DECIMAL:'DECIMAL',FECHA:'DATE',FECHAHORA:'DATETIME',NUMERO:'NUMERIC'};
 const b=aliases[base]||base;
 const allowed=new Set(['INTEGER','INT','BIGINT','SMALLINT','TINYINT','VARCHAR','CHAR','TEXT','LONGTEXT','MEDIUMTEXT','TINYTEXT','DECIMAL','NUMERIC','FLOAT','DOUBLE','REAL','BOOLEAN','DATE','DATETIME','TIMESTAMP','TIME','BLOB','JSON','BINARY','VARBINARY']);
 if(!allowed.has(b))throw Error('Tipo SQL no reconocido: '+base);
 if(length&&(!Number.isSafeInteger(Number(length))||Number(length)<1||Number(length)>65535))throw Error('Longitud fuera de rango: '+length);
 if(scale&&(Number(scale)>Number(length)))throw Error('Escala decimal mayor que precisión');
 let type=b;
 if(engine==='mysql'){
   if(b==='BOOLEAN')type='BOOLEAN';
   else if(b==='REAL')type='DOUBLE';
   else if(b==='VARCHAR'&&!length)type='VARCHAR(255)';
   else if(b==='TEXT'&&length)type='VARCHAR';
 }else{
   if(['INT','TINYINT','SMALLINT','BIGINT','BOOLEAN'].includes(b))type='INTEGER';
   else if(['VARCHAR','CHAR','TEXT','LONGTEXT','MEDIUMTEXT','TINYTEXT','JSON'].includes(b))type='TEXT';
   else if(['DECIMAL','NUMERIC','FLOAT','DOUBLE','REAL'].includes(b))type='REAL';
   else if(['DATE','DATETIME','TIMESTAMP','TIME'].includes(b))type='TEXT';
   else if(['BINARY','VARBINARY','BLOB'].includes(b))type='BLOB';
 }
 const dimensions=length?(scale?`(${length},${scale})`:`(${length})`):'';
 return (engine==='sqlite'?type:(type==='VARCHAR'&&b==='TEXT'&&length?type:type+dimensions))+(suffix?' '+(engine==='sqlite'?suffix.replace(/\bUNSIGNED\b/gi,'').trim():suffix):'');
}
function parseProperty(prop){const text=String(prop.text||'').trim();const i=text.indexOf(':');if(i<1)throw Error('La propiedad «'+text+'» debe tener formato nombre: tipo');const name=text.slice(0,i).trim(),type=text.slice(i+1).trim();if(!name||!type)throw Error('Propiedad incompleta: '+text);return {id:prop.id,name,type};}
function generateSQL(engine){
 const q=s=>sqlIdentifier(s,engine),lines=[`-- Universo de entidades · ${engine==='mysql'?'MySQL':'SQLite'}`,`-- Proyecto: ${activeProject().name.replace(/[\r\n]/g,' ')}`,''];
 if(engine==='sqlite')lines.push('PRAGMA foreign_keys = ON;','');
 const schemas=data.entities.map(n=>({entity:n,cols:n.properties.map(parseProperty)}));
 const tables=new Set();for(const s of schemas){if(tables.has(s.entity.title.toLowerCase()))throw Error('Nombre de entidad duplicado: '+s.entity.title);tables.add(s.entity.title.toLowerCase());}
 const foreign=[];
 for(const s of schemas){
  if(!s.cols.length)throw Error('La entidad «'+s.entity.title+'» no tiene propiedades');
  const names=new Set(),cols=[];
  for(const c of s.cols){if(names.has(c.name.toLowerCase()))throw Error('Propiedad duplicada en '+s.entity.title+': '+c.name);names.add(c.name.toLowerCase());cols.push('  '+q(c.name)+' '+sqlType(c.type,engine));}
  const pk=s.cols.find(c=>c.name.toLowerCase()==='id');if(pk)cols.push('  PRIMARY KEY ('+q(pk.name)+')');
  if(engine==='sqlite')for(const link of data.links){const a=schemas.find(x=>x.entity.id===link.from);if(!a||link.to!==s.entity.id)continue;const ac=a.cols.find(c=>c.id===link.fromProp),bc=s.cols.find(c=>c.id===link.toProp);if(ac?.name.toLowerCase()==='id'&&bc)cols.push('  FOREIGN KEY ('+q(bc.name)+') REFERENCES '+q(a.entity.title)+' ('+q(ac.name)+')');}
  lines.push('CREATE TABLE '+(engine==='mysql'?'IF NOT EXISTS ':'IF NOT EXISTS ')+q(s.entity.title)+' (',cols.join(',\n'),')'+(engine==='mysql'?' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;':';'),'');
 }
 // Enlaces entre propiedades se exportan como claves foráneas cuando el origen apunta a una columna id.
 for(const link of data.links){const a=schemas.find(s=>s.entity.id===link.from),b=schemas.find(s=>s.entity.id===link.to);if(!a||!b)continue;const ac=a.cols.find(c=>c.id===link.fromProp),bc=b.cols.find(c=>c.id===link.toProp);if(!ac||!bc||ac.name.toLowerCase()!=='id')continue;
  foreign.push({table:b.entity.title,col:bc.name,ref:a.entity.title,refCol:ac.name});
 }
 if(foreign.length){lines.push('-- Relaciones detectadas (id → propiedad vinculada)');for(const f of foreign){if(engine==='mysql')lines.push(`ALTER TABLE ${q(f.table)} ADD FOREIGN KEY (${q(f.col)}) REFERENCES ${q(f.ref)} (${q(f.refCol)});`);else lines.push(`-- ${q(f.table)}.${q(f.col)} → ${q(f.ref)}.${q(f.refCol)} (incluida en CREATE TABLE)`);}lines.push('');}
 return lines.join('\n');
}
function downloadText(content,filename,mime){const url=URL.createObjectURL(new Blob([content],{type:mime}));const a=document.createElement('a');a.href=url;a.download=filename;a.click();setTimeout(()=>URL.revokeObjectURL(url),1500);}
document.querySelector('#exportSql').onclick=()=>{if(!data.entities.length){alert('Crea alguna entidad antes de exportar SQL.');return;}const engine=prompt('Motor SQL: escribe mysql o sqlite','mysql')?.trim().toLowerCase();if(!engine)return;if(!['mysql','sqlite'].includes(engine)){alert('Selecciona mysql o sqlite.');return;}try{const sql=generateSQL(engine),name=activeProject().name.replace(/[^a-z0-9_-]+/gi,'-')||'proyecto';downloadText(sql,`${name}-${engine}.sql`,'application/sql;charset=utf-8');}catch(err){alert('No se pudo exportar SQL: '+err.message);}};
document.querySelector('#import').onchange=async e=>{
 const file=e.target.files?.[0];if(!file)return;
 try{
  const backup=JSON.parse(await file.text()),diagram=backup.format==='jocarsa-bbdd-backup'?backup.data:backup;
  if(!Array.isArray(diagram?.entities)||!Array.isArray(diagram?.links))throw Error('Formato inválido');
  const count=backup.tables?Object.values(backup.tables).reduce((n,rows)=>n+(Array.isArray(rows)?rows.length:0),0):0;
  const answer=prompt(`Importar ${diagram.entities.length} entidades y ${count} registros.\n\nEscribe una opción:\n1 = Reemplazar registros\n2 = Fusionar registros por ID\n3 = Solo estructura`,'3');
  if(answer===null)return;
  const mode=({'1':'replace','2':'merge','3':'structure'})[answer.trim()];if(!mode)throw Error('Selecciona 1, 2 o 3');
  if(mode!=='structure'&&!backup.tables)throw Error('El archivo no contiene registros; usa Solo estructura');
  if(mode==='replace'&&!confirm('Se eliminarán los registros actuales de las tablas importadas. ¿Continuar?'))return;
  // Download an independent safety copy before modifying the database.
  const safety=await api('export_backup',{project:store.activeId});
  downloadText(JSON.stringify(safety,null,2),'copia-seguridad-antes-importar-'+Date.now()+'.json','application/json');
  const stats=await api('import_backup',{project:store.activeId,backup,mode});
  clearTimeout(saveTimer);await refresh();alert(`Importación finalizada. Insertados: ${stats.inserted}, actualizados: ${stats.updated}, eliminados: ${stats.deleted}.`);
 }catch(err){alert('No se pudo importar: '+err.message+'\nSi hubo cambios parciales, utiliza la copia de seguridad descargada.');}
 finally{e.target.value='';}
};
document.querySelector('#clear').onclick=()=>{if(confirm('¿Eliminar DEFINITIVAMENTE todas las tablas y sus registros de esta base de datos?')){pendingDeletion=true;data=empty();selected=null;save();render();}};
document.addEventListener('keydown',e=>{if(e.key==='Escape'&&drag?.type==='link'){drag=null;drawWires();}if((e.key==='Delete'||e.key==='Backspace')&&!document.querySelector('#dataDialog').open&&!dialog.open&&selected&&!['INPUT','TEXTAREA'].includes(document.activeElement?.tagName)){if(!confirm('¿Eliminar definitivamente esta tabla y sus registros?'))return;pendingDeletion=true;data.entities=data.entities.filter(n=>n.id!==selected);data.links=data.links.filter(l=>l.from!==selected&&l.to!==selected);selected=null;save();render();}});
document.querySelector('#projectSelect').addEventListener('change',async e=>{clearTimeout(saveTimer);store.activeId=e.target.value;loadProject();try{await api('activate',{project:store.activeId});}catch(err){alert(err.message);}});
document.querySelector('#newProject').onclick=async()=>{const name=prompt('Nombre del nuevo proyecto:','Nuevo proyecto');if(!name?.trim())return;persist();try{await api('create_project',{name:name.trim()});await refresh();}catch(e){alert(e.message);}};
document.querySelector('#renameProject').onclick=async()=>{const p=activeProject();const name=prompt('Nuevo nombre del proyecto:',p.name);if(!name?.trim())return;try{await api('rename_project',{project:p.id,name:name.trim()});await refresh();}catch(e){alert(e.message);}};
document.querySelector('#deleteProject').onclick=async()=>{if(store.projects.length===1)return;const p=activeProject();if(!confirm(`¿Eliminar el proyecto «${p.name}» y todas sus entidades y conexiones? Esta acción no se puede deshacer.`))return;try{await api('delete_project',{project:p.id});await refresh();}catch(e){alert(e.message);}};

// Registros por entidad. Las claves de cada fila son los identificadores estables de las propiedades.
const dataDialog=document.querySelector('#dataDialog');
let recordsEntityId=null;
const colName=p=>p.text.split(':')[0].trim();
// Las conexiones entre propiedades identifican referencias entre tablas.
// La columna vinculada a un identificador de otra entidad se edita con un desplegable.
function foreignReference(entityId,propId){
 for(const link of data.links){
  let sourceId,sourceProp;
  if(link.to===entityId&&link.toProp===propId){sourceId=link.from;sourceProp=link.fromProp;}
  else if(link.from===entityId&&link.fromProp===propId){sourceId=link.to;sourceProp=link.toProp;}
  else continue;
  const entity=find(sourceId),property=entity?.properties.find(p=>p.id===sourceProp);
  if(entity&&property&&colName(property).toLowerCase()==='id')return {entity,property};
 }
 return null;
}
function foreignLabel(entity,row,property){
 const value=String(row[property.id]??'');
 const descriptive=entity.properties.filter(p=>p.id!==property.id).map(p=>String(row[p.id]??'').trim()).find(Boolean);
 return descriptive?`${value} — ${descriptive}`:value;
}
async function openRecords(id){recordsEntityId=id;dataDialog.showModal();try{await loadRows(id);}catch(e){alert(e.message);}}
function renderRecords(){
 const n=find(recordsEntityId);if(!n)return;
 n.rows ||= [];
 document.querySelector('#dataTitle').textContent='Datos · '+n.title;
 document.querySelector('#dataCount').textContent=n.rows.length+' registros · '+n.properties.length+' columnas';
 const head=document.querySelector('#dataTable thead'),body=document.querySelector('#dataTable tbody');
 head.replaceChildren();body.replaceChildren();
 const hr=document.createElement('tr');for(const prop of n.properties){const th=document.createElement('th');th.textContent=colName(prop);th.title=prop.text;hr.append(th);}const actions=document.createElement('th');actions.textContent='Acciones';hr.append(actions);head.append(hr);
 n.rows.forEach((row,index)=>{const tr=document.createElement('tr');for(const prop of n.properties){const td=document.createElement('td'),ref=foreignReference(n.id,prop.id);
 let input;
 if(ref){
  input=document.createElement('select');input.className='foreign-select';input.dataset.fkTable=ref.entity.title;
  const blank=document.createElement('option');blank.value='';blank.textContent='— Sin seleccionar —';input.append(blank);
  const current=String(row[prop.id]??'');let matched=current==='';
  for(const foreignRow of (ref.entity.rows||[])){
   const value=String(foreignRow[ref.property.id]??'').trim();if(!value)continue;
   const option=document.createElement('option');option.value=value;option.textContent=foreignLabel(ref.entity,foreignRow,ref.property);
   if(value===current)matched=true;input.append(option);
  }
  if(!matched){const missing=document.createElement('option');missing.value=current;missing.textContent=current+' (registro no encontrado)';input.append(missing);input.classList.add('fk-missing');input.title='Referencia no encontrada en '+ref.entity.title+'; se conserva el valor original.';}
  input.value=current;if(matched)input.title='Clave foránea → '+ref.entity.title+'.'+colName(ref.property);
 }else{
  input=document.createElement('input');input.type='text';input.value=row[prop.id]??'';
  input.placeholder=prop.text.includes(':')?prop.text.split(':').slice(1).join(':').trim():'';
 }
 input.dataset.row=String(index);input.dataset.prop=prop.id;
 input.setAttribute('aria-label',colName(prop)+' · fila '+(index+1));td.append(input);tr.append(td);}const td=document.createElement('td'),del=document.createElement('button');del.type='button';del.className='delete-row';del.textContent='Eliminar';del.dataset.deleteRow=String(index);td.append(del);tr.append(td);body.append(tr);});
 if(!n.rows.length){const tr=document.createElement('tr'),td=document.createElement('td');td.colSpan=n.properties.length+1;td.className='empty-data';td.textContent='Todavía no hay registros. Pulsa «Añadir registro».';tr.append(td);body.append(tr);}
}
document.querySelector('#addRow').onclick=async()=>{try{await api('add_row',{project:store.activeId,entity:recordsEntityId});await loadRows(recordsEntityId);}catch(e){alert(e.message);}};
document.querySelector('#closeData').onclick=()=>dataDialog.close();
document.querySelector('#dataTable').addEventListener('change',async e=>{const input=e.target.closest('input[data-row], select[data-row]');if(!input)return;const n=find(recordsEntityId),row=n?.rows?.[Number(input.dataset.row)];if(!row)return;try{await updateCell(recordsEntityId,row.__id,input.dataset.prop,input.value);row[input.dataset.prop]=input.value;input.classList.remove('fk-missing');}catch(err){alert(err.message);await loadRows(recordsEntityId);}});
document.querySelector('#dataTable').addEventListener('click',async e=>{const btn=e.target.closest('[data-delete-row]');if(!btn)return;const n=find(recordsEntityId);if(!n)return;try{await api('delete_row',{project:store.activeId,entity:recordsEntityId,row:n.rows[Number(btn.dataset.deleteRow)].__id});await loadRows(recordsEntityId);}catch(err){alert(err.message);}});
dataDialog.addEventListener('close',()=>{recordsEntityId=null;render();});

const loginOverlay=document.querySelector('#loginOverlay');
 const loginError=document.querySelector('#loginError');
 document.querySelector('#loginForm').addEventListener('submit',async e=>{
  e.preventDefault();loginError.textContent='';
  try{await api('login',{username:document.querySelector('#loginUser').value,password:document.querySelector('#loginPassword').value});
   await refresh();loginOverlay.hidden=true;document.querySelector('#loginPassword').value='';
  }catch(err){loginError.textContent=err.message;}
 });
 document.querySelector('#logout').addEventListener('click',async()=>{
  try{await api('logout');}finally{loginOverlay.hidden=false;store={activeId:null,projects:[]};data=empty();nodes.replaceChildren();categoriesLayer.replaceChildren();svg.replaceChildren();}
 });
 window.addEventListener('resize',drawWires);
 api('auth_status').then(async authenticated=>{if(authenticated){await refresh();loginOverlay.hidden=true;}else loginOverlay.hidden=false;}).catch(e=>{loginError.textContent=e.message;});
})();
