(()=>{'use strict';
const prefix='jocarsa-erp-config-v1:',favPrefix='jocarsa-erp-fav-v1:',viewsPrefix='jocarsa-erp-views-v1:';
const sharedConfig=new Map(),sharedViews=new Map();
const endpoint='../backend/api.php';
async function request(url,body){const response=await fetch(url,body===undefined?{credentials:'same-origin'}:{method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/json'},body:JSON.stringify(body)});const data=await response.json();if(!response.ok||data.error)throw new Error(data.debug||data.error||'Error guardando personalización');return data;}
async function loadShared(t){const data=await request(endpoint+'?bloque=ui_config_get&tabla='+encodeURIComponent(t));sharedConfig.set(t,data.config||{});sharedViews.set(t,Array.isArray(data.views)?data.views:[]);}
async function persist(t,kind,value){await request(endpoint+'?bloque=ui_config_save',{tabla:t,tipo:kind,valor:value});if(kind==='config')sharedConfig.set(t,value);else sharedViews.set(t,value);}

const read=(k,def)=>{try{return JSON.parse(localStorage.getItem(k))??def}catch{return def}};
const write=(k,v)=>localStorage.setItem(k,JSON.stringify(v));
const defaults=cols=>({columns:cols.map(c=>c.name),sort:null,filter:{field:'',operator:'contains',value:''},rules:[],actions:[]});
const get=(table,cols)=>{const base=defaults(cols),stored=sharedConfig.get(table)||{},valid=base.columns;return {...base,...stored,columns:[...(stored.columns||valid).filter(n=>valid.includes(n)),...valid.filter(n=>!(stored.columns||valid).includes(n))].filter(n=>!(stored.hidden||[]).includes(n))}};
const save=(t,v)=>persist(t,'config',v);
const compare=(a,b)=>{const x=Number(a),y=Number(b);if(a!==''&&b!==''&&Number.isFinite(x)&&Number.isFinite(y))return x-y;return String(a??'').localeCompare(String(b??''),'es',{numeric:true,sensitivity:'base'})};
const matches=(row,rule)=>{if(!rule?.field||!rule.value&&rule.operator!=='empty'&&rule.operator!=='notempty')return true;const a=String(row[rule.field]??''),b=String(rule.value??'');switch(rule.operator){case 'eq':return a.toLowerCase()===b.toLowerCase();case 'neq':return a.toLowerCase()!==b.toLowerCase();case 'gt':return compare(a,b)>0;case 'lt':return compare(a,b)<0;case 'empty':return !a.trim();case 'notempty':return !!a.trim();default:return a.toLowerCase().includes(b.toLowerCase())}};
const filter=(rows,p)=>rows.filter(r=>matches(r,p.filter));
const sort=(rows,p)=>!p.sort?.field?rows:[...rows].sort((a,b)=>compare(a[p.sort.field],b[p.sort.field])*(p.sort.direction==='desc'?-1:1));
const rowClass=(row,p)=>{for(const rule of p.rules||[])if(rule.field&&matches(row,rule))return 'erp-row-'+(rule.style||'warning');return ''};
const favorites=t=>read(favPrefix+t,[]);
const isFavorite=(t,id)=>favorites(t).includes(String(id));
const toggleFavorite=(t,id)=>{const s=new Set(favorites(t));id=String(id);s.has(id)?s.delete(id):s.add(id);write(favPrefix+t,[...s])};
const toggleSort=(t,field)=>{const p={...(sharedConfig.get(t)||{})};p.sort=p.sort?.field===field?{field,direction:p.sort.direction==='asc'?'desc':'asc'}:{field,direction:'asc'};return save(t,p)};
const views=t=>sharedViews.get(t)||[];
const actions=(table,cols)=>get(table,cols).actions||[];
const visibleAction=(action,row)=>!action.condition?.field||String(row[action.condition.field]??'').toLowerCase()===String(action.condition.value??'').toLowerCase();
const saveView=async(t,render)=>{const name=prompt('Nombre de la vista:');if(!name?.trim())return;const list=views(t).filter(v=>v.name!==name.trim());list.push({name:name.trim(),config:sharedConfig.get(t)||{}});await persist(t,'views',list);render()};
const applyView=async(t,name,render)=>{const v=views(t).find(x=>x.name===name);if(v){await save(t,v.config);render()}};
function editor(t,cols,render){
 const old=document.getElementById('erp-personalizador');if(old)old.remove();
 const p=get(t,cols),original=sharedConfig.get(t)||{};
 const layer=document.createElement('div');layer.id='erp-personalizador';layer.className='erp-config-overlay';
 const panel=document.createElement('section');panel.className='erp-config-panel';panel.setAttribute('role','dialog');panel.setAttribute('aria-modal','true');panel.setAttribute('aria-label','Personalización de '+t);
 const header=document.createElement('header');header.className='erp-config-header';const title=document.createElement('h2');title.textContent='Personalizar · '+t;const close=document.createElement('button');close.textContent='×';close.type='button';close.onclick=()=>layer.remove();header.append(title,close);panel.append(header);
 const content=document.createElement('div');content.className='erp-config-content';
 const h=(txt)=>{const el=document.createElement('h3');el.textContent=txt;content.append(el)};
 const field=(parent,label,element)=>{const wrap=document.createElement('label');wrap.className='erp-config-field';const text=document.createElement('span');text.textContent=label;wrap.append(text,element);parent.append(wrap);return element};
 const select=(options,val)=>{const s=document.createElement('select');for(const [v,label] of options)s.add(new Option(label,v));s.value=val??'';return s};
 const colOptions=[['','Ninguno'],...cols.map(c=>[c.name,c.name])];
 h('Columnas visibles y orden');
 const list=document.createElement('div');list.className='erp-config-columns';const visible=new Set(p.columns);
 const buildCols=()=>{list.replaceChildren();for(const c of cols){const line=document.createElement('div');line.className='erp-config-col';const cb=document.createElement('input');cb.type='checkbox';cb.checked=visible.has(c.name);cb.onchange=()=>{cb.checked?visible.add(c.name):visible.delete(c.name);if(!visible.size){visible.add(c.name);cb.checked=true}};const name=document.createElement('span');name.textContent=c.name;const up=document.createElement('button');up.type='button';up.textContent='↑';up.title='Subir';const down=document.createElement('button');down.type='button';down.textContent='↓';down.title='Bajar';up.onclick=()=>{const i=cols.indexOf(c);if(i>0){[cols[i-1],cols[i]]=[cols[i],cols[i-1]];buildCols()}};down.onclick=()=>{const i=cols.indexOf(c);if(i<cols.length-1){[cols[i+1],cols[i]]=[cols[i],cols[i+1]];buildCols()}};line.append(cb,name,up,down);list.append(line)}};buildCols();content.append(list);
 h('Filtro de la vista');
 const filters=document.createElement('div');filters.className='erp-config-grid';
 const ff=field(filters,'Campo',select(colOptions,p.filter?.field));const fo=field(filters,'Operador',select([['contains','Contiene'],['eq','Igual'],['neq','Distinto'],['gt','Mayor que'],['lt','Menor que'],['empty','Vacío'],['notempty','No vacío']],p.filter?.operator));const fv=document.createElement('input');fv.value=p.filter?.value||'';field(filters,'Valor',fv);content.append(filters);
 h('Acciones personalizadas');
 const actionTypes=[['print','Imprimir ficha'],['duplicate','Duplicar registro'],['update','Cambiar campo / estado']];
 const actionList=(p.actions||[]).map(a=>({...a}));
 const actionsBox=document.createElement('div');actionsBox.className='erp-config-action-list';
 const paintActions=()=>{actionsBox.replaceChildren();actionList.forEach((a,i)=>{
  const line=document.createElement('div');line.className='erp-config-action';
  const label=document.createElement('input');label.placeholder='Nombre del botón';label.value=a.label||'';label.oninput=()=>a.label=label.value;
  const type=select(actionTypes,a.type||'print');type.onchange=()=>{a.type=type.value;paintActions()};
  const placement=select([['row','Por registro'],['bulk','Selección múltiple'],['both','Ambos']],a.placement||'row');placement.onchange=()=>a.placement=placement.value;
  const condition=select([['','Siempre visible'],...cols.map(c=>[c.name,c.name])],a.condition?.field||'');condition.onchange=()=>{a.condition={...(a.condition||{}),field:condition.value}};
  const conditionValue=document.createElement('input');conditionValue.placeholder='Valor para mostrar (igual)';conditionValue.value=a.condition?.value??'';conditionValue.oninput=()=>{a.condition={...(a.condition||{}),value:conditionValue.value}};
  const target=select(colOptions,a.field||'');target.onchange=()=>a.field=target.value;
  const value=document.createElement('input');value.placeholder='Nuevo valor';value.value=a.value??'';value.oninput=()=>a.value=value.value;
  const del=document.createElement('button');del.type='button';del.textContent='Eliminar';del.onclick=()=>{actionList.splice(i,1);paintActions()};
  line.append(field(line,'Etiqueta',label),field(line,'Operación',type),field(line,'Ubicación',placement),field(line,'Mostrar si campo =',condition),field(line,'Valor de condición',conditionValue));
  if(a.type==='update')line.append(field(line,'Campo a actualizar',target),field(line,'Nuevo valor',value));
  const params=document.createElement('textarea');params.rows=4;params.placeholder='Una línea por parámetro: nombre|Etiqueta|tipo|obligatorio\nEj.: motivo|Motivo|text|si';params.value=(a.parameters||[]).map(x=>[x.name,x.label,x.type,x.required?'si':'no'].join('|')).join('\n');params.oninput=()=>{a.parameters=params.value.split('\n').map(line=>{const [name,label,type,required]=line.split('|').map(x=>x.trim());return {name,label:label||name,type:['text','number','date','textarea','checkbox'].includes(type)?type:'text',required:/^(si|sí|true|1)$/i.test(required||'')}}).filter(x=>/^[a-zA-Z_][a-zA-Z0-9_]*$/.test(x.name||''))};line.append(field(line,'Parámetros (nombre|etiqueta|tipo|obligatorio)',params));
  if(a.type==='print'){
   const heading=document.createElement('input');heading.placeholder='Título del documento';heading.value=a.printTitle||'';heading.oninput=()=>a.printTitle=heading.value;
   const template=document.createElement('textarea');template.rows=5;template.placeholder='Ej.: Ficha de {{nombre}}\nPrecio: {{precio}}\nObservaciones: {{param.observaciones}}';template.value=a.printTemplate||'';template.oninput=()=>a.printTemplate=template.value;
   const format=select([['text','Texto (formato anterior)'],['html','HTML personalizado']],a.printFormat||'text');
   const templateField=field(line,'Plantilla de texto ({{campo}} y {{param.nombre}})',template);
   const formatField=field(line,'Formato del documento',format);
   const syncFormat=()=>{a.printFormat=format.value;templateField.querySelector('span').textContent=format.value==='html'?'Código HTML ({{campo}} y {{param.nombre}})':'Plantilla de texto ({{campo}} y {{param.nombre}})';template.rows=format.value==='html'?14:5;template.placeholder=format.value==='html'?'<h1>Factura {{numero}}</h1>\n<p>Cliente: {{cliente}}</p>\n<p>Total: {{total}} €</p>':'Ej.: Ficha de {{nombre}}\nPrecio: {{precio}}\nObservaciones: {{param.observaciones}}'};
   format.onchange=syncFormat;
   line.append(field(line,'Título de impresión',heading),formatField,templateField);
   syncFormat();
  }
  if(a.type==='update'){
   const dynamic=document.createElement('input');dynamic.type='checkbox';dynamic.checked=!!a.valueFromParameter;dynamic.onchange=()=>a.valueFromParameter=dynamic.checked;
   line.append(field(line,'Usar primer parámetro como valor del campo',dynamic));
  }
  line.append(del);actionsBox.append(line);
 })};paintActions();content.append(actionsBox);
 const addAction=document.createElement('button');addAction.type='button';addAction.textContent='+ Añadir acción';addAction.onclick=()=>{actionList.push({id:'action_'+Date.now()+'_'+actionList.length,label:'Nueva acción',type:'print',placement:'row',condition:{field:'',value:''}});paintActions()};content.append(addAction);
 h('Reglas de color por fila');
 const rules=document.createElement('div');const ruleList=[...(p.rules||[])];
 const paintRules=()=>{rules.replaceChildren();ruleList.forEach((r,i)=>{const line=document.createElement('div');line.className='erp-config-rule';const f=select(colOptions,r.field);const op=select([['eq','Igual'],['neq','Distinto'],['contains','Contiene'],['gt','Mayor que'],['lt','Menor que'],['empty','Vacío'],['notempty','No vacío']],r.operator);const v=document.createElement('input');v.placeholder='Valor';v.value=r.value||'';const style=select([['warning','Ámbar'],['danger','Rojo suave'],['success','Verde suave'],['info','Azul suave']],r.style);const del=document.createElement('button');del.type='button';del.textContent='×';del.onclick=()=>{ruleList.splice(i,1);paintRules()};f.onchange=()=>r.field=f.value;op.onchange=()=>r.operator=op.value;v.oninput=()=>r.value=v.value;style.onchange=()=>r.style=style.value;line.append(f,op,v,style,del);rules.append(line)})};paintRules();content.append(rules);
 const addRule=document.createElement('button');addRule.type='button';addRule.textContent='+ Añadir regla';addRule.onclick=()=>{ruleList.push({field:'',operator:'eq',value:'',style:'warning'});paintRules()};content.append(addRule);
 const footer=document.createElement('footer');footer.className='erp-config-footer';const reset=document.createElement('button');reset.type='button';reset.textContent='Restablecer';reset.onclick=async()=>{if(confirm('¿Restablecer la configuración global de esta tabla?')){try{await save(t,{});layer.remove();render()}catch(e){alert(e.message)}}};const cancel=document.createElement('button');cancel.type='button';cancel.textContent='Cancelar';cancel.onclick=()=>layer.remove();const apply=document.createElement('button');apply.type='button';apply.textContent='Guardar configuración';apply.onclick=async()=>{const hidden=cols.filter(c=>!visible.has(c.name)).map(c=>c.name);const config={...original,columns:cols.filter(c=>visible.has(c.name)).map(c=>c.name),hidden,filter:{field:ff.value,operator:fo.value,value:fv.value},rules:ruleList.filter(r=>r.field),actions:actionList.filter(a=>a.label?.trim()&&['print','duplicate','update'].includes(a.type)).map(a=>({...a,id:a.id||'action_'+Math.random().toString(36).slice(2)}))};try{await save(t,config);layer.remove();render()}catch(e){alert(e.message)}};footer.append(reset,cancel,apply);panel.append(content,footer);layer.append(panel);layer.onclick=e=>{if(e.target===layer)layer.remove()};document.body.append(layer);close.focus();
}
window.JocarsaPersonalizacion={get,filter,sort,rowClass,isFavorite,toggleFavorite,toggleSort,views,saveView,applyView,editor,actions,loadShared,visibleAction};
})();