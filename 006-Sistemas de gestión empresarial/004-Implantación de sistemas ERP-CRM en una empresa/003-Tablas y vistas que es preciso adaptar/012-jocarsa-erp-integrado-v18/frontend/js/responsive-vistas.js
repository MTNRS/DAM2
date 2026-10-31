(()=>{'use strict';
const mq=matchMedia('(max-width:700px)');
function responsive(){const z=parseFloat(getComputedStyle(document.documentElement).getPropertyValue('--ju-content-scale'))||1;return mq.matches||innerWidth/z<=700;}
function setupMenus(){
 document.querySelectorAll('body.jocarsa-iu > main > nav, body.jocarsa-iu > main > section#entidades').forEach((menu,i)=>{
  if(menu.dataset.juAccordion)return;menu.dataset.juAccordion='1';menu.classList.add('ju-mobile-menu');
  const title=menu.querySelector(':scope > .titulo-columna');
  const toggle=document.createElement('button');toggle.type='button';toggle.className='ju-mobile-menu-toggle';toggle.setAttribute('aria-expanded','false');
  toggle.innerHTML='<span class="ju-hamburger" aria-hidden="true">☰</span><span></span><span aria-hidden="true">▾</span>';
  toggle.children[1].textContent=(title?.textContent|| (i?'Secciones':'Navegación')).trim();
  const id='ju-menu-body-'+i;const body=document.createElement('div');body.className='ju-mobile-menu-body';body.id=id;
  toggle.setAttribute('aria-controls',id);
  // Move nodes, never clone: preserves their listeners and behavior.
  [...menu.childNodes].forEach(n=>body.appendChild(n));menu.append(toggle,body);
  const set=open=>{menu.classList.toggle('ju-menu-open',open);toggle.setAttribute('aria-expanded',String(open));};
  toggle.addEventListener('click',()=>set(!menu.classList.contains('ju-menu-open')));
  menu.addEventListener('click',e=>{if(e.target.closest('a'))set(false)});
 });
}
const esc=s=>String(s??'').trim();
function setupTable(table){
 if(table.dataset.juViews||table.closest('.erp-screen'))return;table.dataset.juViews='1';
 const wrapper=document.createElement('div');wrapper.className='ju-table-adapter';table.before(wrapper);wrapper.append(table);
 const toolbar=document.createElement('div');toolbar.className='ju-table-views';toolbar.setAttribute('role','group');toolbar.setAttribute('aria-label','Vista de datos');
 const modes=[['table','Tabla'],['cards','Tarjetas'],['tree','Árbol']];const buttons={};
 for(const [mode,label] of modes){const b=document.createElement('button');b.type='button';b.textContent=label;b.setAttribute('aria-pressed','false');b.addEventListener('click',()=>{wrapper.dataset.manualView=mode;setMode(mode)});toolbar.append(b);buttons[mode]=b}
 const cards=document.createElement('div');cards.className='ju-table-cards';const tree=document.createElement('div');tree.className='ju-table-tree';wrapper.prepend(toolbar);wrapper.append(cards,tree);
 let active='table';
 function setMode(mode){active=mode;wrapper.dataset.view=mode;for(const [k,b] of Object.entries(buttons))b.setAttribute('aria-pressed',String(k===mode));if(mode!=='table')render();}
 function render(){
  const head=[...table.querySelectorAll('thead th')].map(e=>esc(e.textContent));
  const rows=[...table.querySelectorAll('tbody tr')];cards.replaceChildren();tree.replaceChildren();
  rows.forEach((tr,index)=>{
   const cells=[...tr.children].filter(e=>e.matches('td,th'));
   if(!cells.length)return;
   const card=document.createElement('article');card.className='ju-data-card';
   const summary=document.createElement('strong');summary.className='ju-data-card-title';summary.textContent=esc(cells[1]?.textContent)||esc(cells[0]?.textContent)||'Registro '+(index+1);card.append(summary);
   const dl=document.createElement('dl');
   cells.forEach((cell,j)=>{const dt=document.createElement('dt');dt.textContent=head[j]||cell.getAttribute('data-label')||'Campo '+(j+1);const dd=document.createElement('dd');
    // Preserve interactivity: controls are not moved or cloned. Provide a button that switches to the real table when a row has controls.
    if(cell.querySelector('button,input,select,textarea,a')){dd.textContent=esc(cell.textContent)||'Acción disponible en tabla';const jump=document.createElement('button');jump.type='button';jump.textContent='Abrir en tabla';jump.addEventListener('click',()=>{wrapper.dataset.manualView='table';setMode('table');tr.scrollIntoView({block:'nearest'})});dd.append(' ',jump)}else dd.textContent=esc(cell.textContent)||'—';dl.append(dt,dd)});
   card.append(dl);cards.append(card);
   const detail=document.createElement('details');detail.className='ju-tree-item';const title=document.createElement('summary');title.textContent=summary.textContent;detail.append(title);
   const copy=dl.cloneNode(true);copy.querySelectorAll('button').forEach(b=>b.remove());detail.append(copy);tree.append(detail);
  });
 }
 const auto=()=>{if(!wrapper.dataset.manualView)setMode(responsive()?'cards':'table');else if(responsive()&&wrapper.dataset.manualView==='table')setMode('cards');else setMode(wrapper.dataset.manualView)};
 auto();window.addEventListener('resize',auto,{passive:true});
 // Table rows may be rendered asynchronously. Observe only the original table.
 let queued=false;new MutationObserver(()=>{if(queued)return;queued=true;requestAnimationFrame(()=>{queued=false;if(active!=='table')render()})}).observe(table,{subtree:true,childList:true,characterData:true});
 document.addEventListener('ju-a11y-scale-change',auto);
}
function init(){setupMenus();document.querySelectorAll('table:not([data-ju-views])').forEach(setupTable);const root=document.querySelector('main')||document.body;new MutationObserver(()=>{setupMenus();root.querySelectorAll('table:not([data-ju-views])').forEach(setupTable)}).observe(root,{childList:true,subtree:true});}
if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',init);else init();
})();
