/* Navegación progresiva: no sustituye los manejadores originales. */
(()=>{'use strict';
const init=()=>{
 const main=document.getElementById('contenido-principal');
 const skip=document.querySelector('.ju-skip-link');
 skip?.addEventListener('click',()=>{if(main)requestAnimationFrame(()=>main.focus({preventScroll:true}))});
 const header=document.querySelector('body.jocarsa-iu > header');
 const logo=header?.querySelector('.corporativo');
 if(logo&&main){logo.setAttribute('role','link');logo.setAttribute('tabindex','0');logo.setAttribute('aria-label','Ir al contenido principal');const go=()=>{main.scrollIntoView({block:'start'});main.focus({preventScroll:true})};logo.addEventListener('click',go);logo.addEventListener('keydown',e=>{if(e.key==='Enter'||e.key===' '){e.preventDefault();go()}})}
 // Hacer operables las tarjetas interactivas existentes sin cambiar su HTML ni eventos.
 const upgrade=()=>{document.querySelectorAll('#entidades article[draggable], #entidades .contenido-columna article[draggable]').forEach(el=>{if(!el.hasAttribute('tabindex'))el.tabIndex=0;if(!el.hasAttribute('role'))el.setAttribute('role','button');if(!el.hasAttribute('aria-label'))el.setAttribute('aria-label',el.textContent.trim()||'Abrir elemento')})};
 upgrade();const target=document.querySelector('#entidades');if(target)new MutationObserver(upgrade).observe(target,{childList:true,subtree:true});
 document.addEventListener('keydown',e=>{
  if(e.defaultPrevented||e.altKey||e.ctrlKey||e.metaKey)return;
  const el=e.target;
  if((e.key==='Enter'||e.key===' ')&&el.matches?.('#entidades article[role="button"]')){e.preventDefault();el.click();return}
  if(['ArrowDown','ArrowUp','ArrowLeft','ArrowRight','Home','End'].includes(e.key)){
   const group=el.closest?.('nav, #navegacion, #entidades, .ju-a11y-panel');
   if(!group||!el.matches?.('a, button, [role="button"]'))return;
   const items=[...group.querySelectorAll('a[href],button:not([disabled]),[role="button"][tabindex]')].filter(x=>x.getClientRects().length&&!x.closest('[hidden]'));
   const i=items.indexOf(el);if(i<0||items.length<2)return;
   const next=e.key==='Home'?0:e.key==='End'?items.length-1:(i+(['ArrowDown','ArrowRight'].includes(e.key)?1:-1)+items.length)%items.length;
   e.preventDefault();items[next].focus();
  }
  if(e.key==='Escape'){
   const open=document.querySelector('.ju-a11y-panel:not([hidden])');if(open){const b=document.querySelector('.ju-a11y-button');b?.click();b?.focus();return}
  }
 });
};
if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',init);else init();
})();
