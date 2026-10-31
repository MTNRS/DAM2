// Secciones de contenido cargadas desde rutas configurables.
(async function cargarSecciones(){
 const specs = [
  ['modalidades','#modalidades','.modality-card'],
  ['casos-exito','#casos','.case-card'],
  ['testimonios','#testimonios','.testimonial'],
  ['clientes','#clientes','.brand-tile'],
  ['metodo','#metodo','.steps > div'],
  ['preguntas-frecuentes','#faq','details'],
  ['tecnologias','#tecnologias','.brand-tile'],
  ['servicios','#servicios','.card']
 ];
 const texto=(n,s,v)=>{const el=n.querySelector(s);if(el && v!==undefined)el.textContent=v};
 await Promise.all(specs.map(async ([key,section,selector])=>{
  const root=document.querySelector(section),config=window.JOCARSA_ENDPOINTS[key];
  if(!root||!config)return;
  const originals=[...root.querySelectorAll(selector)];if(!originals.length)return;
  const parent=originals[0].parentElement,template=originals[0].cloneNode(true);
  try{
   const payload=await window.JOCARSA_API.get(key);
   if(!Array.isArray(payload.items))throw new Error('Se esperaba items[]');
   const fragment=document.createDocumentFragment();
   for(const item of payload.items){
    const node=template.cloneNode(true);
    if(key==='modalidades'||key==='servicios'){texto(node,'h3',item.titulo);texto(node,'p',item.descripcion)}
    if(key==='casos-exito'){
     texto(node,'.case-art span',item.sector);texto(node,'.case-art strong',item.destacado);
     texto(node,'h3',item.titulo);texto(node,'p',item.descripcion);
     node.querySelector('.case-art').className='case-art '+(item.clase_visual||'');
    }
    if(key==='testimonios'){texto(node,'blockquote',item.cita);texto(node,'figcaption strong',item.autor);texto(node,'figcaption span',item.cargo)}
    if(key==='metodo'){texto(node,'b',item.numero);texto(node,'h3',item.titulo);texto(node,'p',item.descripcion)}
    if(key==='preguntas-frecuentes'){texto(node,'summary',item.pregunta);texto(node,'p',item.respuesta)}
    if(key==='clientes'||key==='tecnologias'){
     const img=node.querySelector('img');
     if(img){if(item.logo){img.src=item.logo;img.alt=item.nombre}else img.remove()}
     const label=node.querySelector('.brand-name,span:not(.client-monogram)');
     if(label)label.textContent=item.nombre;
     else if(!img||!item.logo)node.append(document.createTextNode(item.nombre));
    }
    fragment.append(node);
   }
   originals.forEach(n=>n.remove());parent.append(fragment);
  }catch(error){console.error('No se pudo cargar '+key,error)}
 }));
})();
