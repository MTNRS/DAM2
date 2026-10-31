/* jocarsa | componentes · mejora progresiva sin sustituir controles nativos */
(function(){'use strict';
const types={text:'TXT',email:'@',password:'***',search:'⌕',tel:'TEL',url:'URL',number:'123',date:'FECHA',time:'HORA','datetime-local':'FECHA/H',month:'MES',week:'SEM',color:'COLOR',file:'ARCH',range:'RANGO'};
function emit(el){el.dispatchEvent(new Event('input',{bubbles:true}));el.dispatchEvent(new Event('change',{bubbles:true}));}
function enhance(root=document){
 root.querySelectorAll('input,textarea,select,button,output,progress,meter').forEach(el=>{
  if(el.dataset.jcReady||el.closest('.jc-control')||el.closest('.jc-segmented')||el.type==='hidden')return;
  el.dataset.jcReady='1';
  if(el.matches('input[type=radio],input[type=checkbox]')){const wrap=document.createElement('span');wrap.className='jc-choice';el.before(wrap);wrap.append(el);return;}
  if(el.matches('button,input[type=button],input[type=submit],input[type=reset],input[type=image]')){el.classList.add('jc-button');return;}
  if(el.matches('progress,meter,output')){el.classList.add('jc-indicator');return;}
  const wrap=document.createElement('span');wrap.className='jc-control';el.before(wrap);wrap.append(el);
  if(el.matches('select[multiple]')){makeMulti(el,wrap);return;}
  if(el.matches('select')){el.classList.add('jc-native-select');return;}
  if(el.matches('textarea'))return;
  if(el.type==='range'){const out=document.createElement('output');out.className='jc-range-value';out.textContent=el.value;wrap.append(out);el.addEventListener('input',()=>out.textContent=el.value);return;}
  if(el.type==='file'){const out=document.createElement('span');out.className='jc-file-label';out.textContent='Ningún archivo';wrap.append(out);el.addEventListener('change',()=>out.textContent=Array.from(el.files).map(f=>f.name).join(', ')||'Ningún archivo');return;}
  if(el.type==='password'){const eye=document.createElement('button');eye.type='button';eye.className='jc-addon jc-eye';eye.textContent='Mostrar';eye.setAttribute('aria-label','Mostrar contraseña');eye.onclick=()=>{const visible=el.type==='password';el.type=visible?'text':'password';eye.textContent=visible?'Ocultar':'Mostrar';eye.setAttribute('aria-label',eye.textContent+' contraseña');};wrap.append(eye);}
  if(['date','time','datetime-local','month','week'].includes(el.type)){const b=document.createElement('button');b.type='button';b.className='jc-addon';b.textContent='▦';b.title='Abrir selector';b.disabled=el.disabled||el.readOnly;b.onclick=()=>{try{el.showPicker()}catch(e){el.focus()}};wrap.append(b);}
  if(el.type==='color'){const out=document.createElement('span');out.className='jc-color-value';out.textContent=el.value;wrap.append(out);el.addEventListener('input',()=>out.textContent=el.value);}
  if(types[el.type]&&!['password','date','time','datetime-local','month','week','color'].includes(el.type)){const tag=document.createElement('span');tag.className='jc-type';tag.textContent=types[el.type];wrap.append(tag);}
 });
 root.querySelectorAll('[data-jc-segmented]').forEach(makeSegmented);
}
function makeMulti(el,wrap){
 const panel=document.createElement('div');panel.className='jc-multi';panel.tabIndex=-1;
 const tags=document.createElement('div');tags.className='jc-tags';const search=document.createElement('input');search.type='search';search.className='jc-multi-search';search.placeholder=el.dataset.placeholder||'Buscar opciones…';search.setAttribute('aria-label','Buscar opciones');const menu=document.createElement('div');menu.className='jc-multi-menu';menu.setAttribute('role','listbox');menu.setAttribute('aria-multiselectable','true');panel.append(tags,search,menu);wrap.append(panel);el.classList.add('jc-original-select');
 function draw(){tags.replaceChildren();for(const opt of el.selectedOptions){const chip=document.createElement('button');chip.type='button';chip.className='jc-chip';chip.textContent=opt.textContent+' ×';chip.disabled=el.disabled;chip.title='Quitar '+opt.textContent;chip.onclick=()=>{opt.selected=false;emit(el);draw();};tags.append(chip)}menu.replaceChildren();let count=0;const term=search.value.toLocaleLowerCase();for(const opt of el.options){if(opt.hidden||!opt.textContent.toLocaleLowerCase().includes(term))continue;count++;const row=document.createElement('label');row.className='jc-multi-option';const cb=document.createElement('input');cb.type='checkbox';cb.checked=opt.selected;cb.disabled=el.disabled||opt.disabled;cb.onchange=()=>{opt.selected=cb.checked;emit(el);draw();search.focus()};const text=document.createElement('span');text.textContent=(opt.parentElement.tagName==='OPTGROUP'?opt.parentElement.label+' · ':'')+opt.textContent;row.append(cb,text);menu.append(row)}if(!count){const empty=document.createElement('div');empty.className='jc-empty';empty.textContent='Sin resultados';menu.append(empty)}panel.classList.toggle('jc-disabled',el.disabled);search.disabled=el.disabled;}
 search.addEventListener('input',draw);el.addEventListener('change',draw);el.form?.addEventListener('reset',()=>setTimeout(draw,0));draw();
}
function makeSegmented(host){if(host.dataset.jcSegmentedReady)return;host.dataset.jcSegmentedReady='1';host.classList.add('jc-segmented');const buttons=Array.from(host.querySelectorAll('button'));if(!buttons.length)return;const indicator=document.createElement('span');indicator.className='jc-segment-indicator';indicator.setAttribute('aria-hidden','true');host.prepend(indicator);const name=host.dataset.name||'';const hidden=name?document.createElement('input'):null;if(hidden){hidden.type='hidden';hidden.name=name;host.append(hidden)}const key=host.dataset.storageKey;let initial=(key?localStorage.getItem(key):null)||host.dataset.value||buttons.find(b=>b.classList.contains('activo'))?.dataset.valor||buttons[0].dataset.valor;
 function select(btn,notify=true){if(!btn||btn.disabled)return;buttons.forEach(b=>{const active=b===btn;b.classList.toggle('activo',active);b.setAttribute('aria-pressed',String(active))});indicator.style.width=btn.offsetWidth+'px';indicator.style.transform='translateX('+btn.offsetLeft+'px)';if(hidden)hidden.value=btn.dataset.valor||btn.textContent.trim();if(key)try{localStorage.setItem(key,hidden?.value||btn.dataset.valor)}catch(e){}if(notify)host.dispatchEvent(new CustomEvent('jc:change',{bubbles:true,detail:{value:hidden?.value||btn.dataset.valor}}));}
 buttons.forEach((b,i)=>{b.type='button';b.onclick=()=>select(b);b.addEventListener('keydown',e=>{if(!['ArrowRight','ArrowLeft','Home','End'].includes(e.key))return;e.preventDefault();let idx=e.key==='Home'?0:e.key==='End'?buttons.length-1:(i+(e.key==='ArrowRight'?1:-1)+buttons.length)%buttons.length;buttons[idx].focus();select(buttons[idx]);});});select(buttons.find(b=>b.dataset.valor===initial)||buttons[0],false);if(typeof ResizeObserver!=='undefined')new ResizeObserver(()=>{const b=buttons.find(b=>b.classList.contains('activo'));if(b){indicator.style.width=b.offsetWidth+'px';indicator.style.transform='translateX('+b.offsetLeft+'px)'}}).observe(host);
}
window.JocarsaComponentes={init:enhance};if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',()=>enhance());else enhance();
})();
