/* Capa de accesibilidad incremental. No sustituye los eventos existentes. */
(()=>{'use strict';
const selector='a[href],button:not([disabled]),input:not([type="hidden"]):not([disabled]),select:not([disabled]),textarea:not([disabled]),summary,[role="button"],[role="option"],[role="treeitem"],[tabindex]:not([tabindex="-1"])';
const visible=el=>!!el.getClientRects().length&&!el.closest('[hidden],[inert],[aria-hidden="true"]');
const interactive=el=>el.matches(selector)&&visible(el);
function upgrade(){
 // Los artículos del menú y las tarjetas se generan dinámicamente.
 document.querySelectorAll('#entidades article, #navegacion article, #escenario article[draggable], .contenido-columna article').forEach(el=>{
  if(el.querySelector('a[href],button,[tabindex="0"]'))return;
  if(!el.hasAttribute('tabindex'))el.tabIndex=0;
  if(!el.hasAttribute('role'))el.setAttribute('role','button');
  if(!el.hasAttribute('aria-label'))el.setAttribute('aria-label',el.textContent.trim()||'Abrir elemento');
 });
 // Las filas no reemplazan a los controles de sus celdas; pueden recibir foco para lectura.
 document.querySelectorAll('table tbody tr').forEach(tr=>{
  if(!tr.hasAttribute('tabindex'))tr.tabIndex=0;
  if(!tr.hasAttribute('aria-label'))tr.setAttribute('aria-label',[...tr.cells].map(c=>c.innerText.trim()).join(' · '));
 });
 document.querySelectorAll('[role="treeitem"],[role="option"]').forEach(el=>{if(!el.hasAttribute('tabindex'))el.tabIndex=0});
}
function activate(el){if(el.matches('a[href],button,input,select,textarea,summary'))return false;el.click();return true}
function move(e,group){
 const all=[...group.querySelectorAll(selector)].filter(interactive);
 const i=all.indexOf(e.target);if(i<0||all.length<2)return;
 let n=i;
 if(e.key==='Home')n=0;else if(e.key==='End')n=all.length-1;
 else if(e.key==='ArrowDown'||e.key==='ArrowRight')n=(i+1)%all.length;
 else if(e.key==='ArrowUp'||e.key==='ArrowLeft')n=(i-1+all.length)%all.length;
 else return;
 e.preventDefault();all[n].focus();
}
function init(){
 const main=document.getElementById('contenido-principal');
 document.querySelector('.ju-skip-link')?.addEventListener('click',()=>requestAnimationFrame(()=>main?.focus({preventScroll:true})));
 upgrade();
 const observer=new MutationObserver(()=>{if(!document.documentElement.dataset.juUpgrading){document.documentElement.dataset.juUpgrading='1';upgrade();delete document.documentElement.dataset.juUpgrading}});
 observer.observe(document.body,{childList:true,subtree:true});
 document.addEventListener('keydown',e=>{
  if(e.defaultPrevented||e.altKey||e.ctrlKey||e.metaKey)return;
  const el=e.target;if(!(el instanceof Element))return;
  if((e.key==='Enter'||e.key===' ')&&el.matches('[role="button"],article[tabindex="0"],tr[tabindex="0"]')){
   if(el.matches('tr'))return; e.preventDefault();activate(el);return;
  }
  if(e.key==='Escape'){
   const panel=document.querySelector('.ju-a11y-panel:not([hidden])');if(panel){document.querySelector('.ju-a11y-button')?.click();document.querySelector('.ju-a11y-button')?.focus();return}
  }
  if(!['ArrowDown','ArrowUp','ArrowLeft','ArrowRight','Home','End'].includes(e.key))return;
  if(el.matches('input,textarea,select,[contenteditable="true"]'))return;
  // Flechas para navegar elementos de menús, filas y controles de formulario agrupados.
  const group=el.closest('#navegacion,#entidades,.ju-integrated-menu,[role="menu"],[role="listbox"],[role="tree"],.ju-a11y-panel,table,form,.jocarsa-demo section');
  if(group)move(e,group);
 });
}
if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',init);else init();
})();
