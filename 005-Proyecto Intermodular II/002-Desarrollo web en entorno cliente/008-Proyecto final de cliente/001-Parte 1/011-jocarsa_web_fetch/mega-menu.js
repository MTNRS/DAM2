(()=>{
 const nav=document.querySelector('.mega-nav');if(!nav)return;
 const topbar=nav.closest('.topbar'),groups=[...nav.querySelectorAll(':scope > details')];
 const desktop=()=>matchMedia('(hover:hover) and (min-width:741px)').matches;
 const close=()=>groups.forEach(g=>g.open=false);
 let closing;const cancel=()=>clearTimeout(closing);
 const deferClose=()=>{cancel();closing=setTimeout(close,260)};
 groups.forEach(g=>{
  g.addEventListener('toggle',()=>{if(g.open){cancel();groups.forEach(o=>{if(o!==g)o.open=false})}});
  g.addEventListener('pointerenter',()=>{if(desktop()){cancel();g.open=true}});
  g.querySelector('.nav-panel')?.addEventListener('pointerenter',cancel);
 });
 // El panel es descendiente de la cabecera, no necesariamente del rectángulo del nav.
 // Evitamos cerrar al atravesar el espacio entre la pestaña y el panel.
 topbar?.addEventListener('pointerleave',()=>{if(desktop())deferClose()});
 topbar?.addEventListener('pointerenter',cancel);
 nav.addEventListener('focusin',cancel);
 nav.addEventListener('focusout',e=>{if(!nav.contains(e.relatedTarget))deferClose()});
 document.addEventListener('keydown',e=>{if(e.key==='Escape'){cancel();close()}});
 document.addEventListener('click',e=>{if(!nav.contains(e.target)){cancel();close()}});
 nav.querySelectorAll('[data-category]').forEach(a=>a.addEventListener('click',()=>{
  const category=a.dataset.category,product=a.dataset.product;
  const search=document.getElementById('product-search');
  if(search){search.value=product;search.dispatchEvent(new Event('input',{bubbles:true}))}
  else{const filter=[...document.querySelectorAll('#catalog-filters button')].find(b=>b.textContent.toLowerCase().includes(category));filter?.click()}
  close();
 }));
})();