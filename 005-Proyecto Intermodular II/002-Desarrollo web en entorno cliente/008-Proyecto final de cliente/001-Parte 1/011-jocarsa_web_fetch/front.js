let PRODUCTS=[];
let activeCategory='Todas';
const grid=document.getElementById('catalog-grid');
const filters=document.getElementById('catalog-filters');
const search=document.getElementById('product-search');
const normalize=value=>String(value||'').normalize('NFD').replace(/[\u0300-\u036f]/g,'').toLocaleLowerCase('es').trim();
const slug=value=>normalize(value).replace(/[^a-z0-9]+/g,'');
function renderCatalog(){
 const q=normalize(search.value);
 const found=PRODUCTS.filter(x=>(activeCategory==='Todas'||x.category===activeCategory)&&normalize(`${x.name} ${x.category} ${x.slogan}`).includes(q));
 grid.replaceChildren();
 for(const item of found){
  const article=document.createElement('article');article.className='catalog-card';
  article.style.setProperty('--product-color',item.color);
  const logo=document.createElement('img');logo.className='catalog-logo';
  logo.src=`https://static.jocarsa.com/logos/${encodeURIComponent(item.color)}.svg`;
  logo.alt=`Logotipo de jocarsa ${item.name}`;logo.loading='lazy';
  logo.onerror=()=>{logo.onerror=null;logo.src='https://static.jocarsa.com/logos/white.svg'};
  const tag=document.createElement('span');tag.className='tag';tag.textContent=item.category;
  const h=document.createElement('h3');h.textContent='jocarsa | '+item.name;
  const description=document.createElement('p');description.textContent=item.slogan||'Solución del ecosistema JOCARSA';
  article.append(logo,tag,h,description);grid.append(article);
 }
 document.getElementById('product-count').textContent=found.length+' productos de '+PRODUCTS.length;
 for(const b of filters.querySelectorAll('button'))b.setAttribute('aria-pressed',String(b.dataset.category===activeCategory));
}
function renderMenu(categorias){
 const menu=document.querySelector('.mega-products-grid');if(!menu)return;
 menu.replaceChildren();
 for(const categoria of categorias){
  const column=document.createElement('div');column.className='mega-column';
  const heading=document.createElement('h3');heading.textContent=categoria.nombre;column.append(heading);
  for(const product of categoria.productos){
   const a=document.createElement('a');a.href='#productos';a.textContent=product.nombre;
   a.title=`Ver jocarsa ${product.nombre}`;a.dataset.category=categoria.nombre;a.dataset.product=product.nombre;
   a.addEventListener('click',()=>{
    activeCategory=categoria.nombre;search.value=product.nombre;renderCatalog();
    document.querySelectorAll('.mega-nav details').forEach(d=>d.open=false);
    document.getElementById('nav')?.classList.remove('open');
   });column.append(a);
  }menu.append(column);
 }
}
async function loadCatalog(){
 try{
  const response=await fetch('./jocarsa_productos_por_categorias.json',{cache:'no-cache'});
  if(!response.ok)throw new Error(`HTTP ${response.status}`);
  const data=await response.json();
  if(!Array.isArray(data.categorias))throw new Error('El JSON no contiene categorias');
  PRODUCTS=data.categorias.flatMap(c=>c.productos.map(p=>({category:c.nombre,name:p.nombre,slogan:p.slogan,color:p.color||'white'})));
  filters.replaceChildren();
  for(const category of ['Todas',...data.categorias.map(c=>c.nombre)]){
   const button=document.createElement('button');button.type='button';button.textContent=category;button.dataset.category=category;
   button.addEventListener('click',()=>{activeCategory=category;search.value='';renderCatalog()});filters.append(button);
  }
  renderMenu(data.categorias);renderCatalog();
 }catch(error){console.error('No se pudo cargar el catálogo',error);
  document.getElementById('product-count').textContent='No se pudo cargar el catálogo. Comprueba el archivo JSON y el servidor.';
 }
}
search.addEventListener('input',renderCatalog);
loadCatalog();
const track=document.getElementById('brand-track');document.getElementById('brand-prev').onclick=()=>track.scrollBy({left:-330,behavior:'smooth'});document.getElementById('brand-next').onclick=()=>track.scrollBy({left:330,behavior:'smooth'});
const messages=document.getElementById('chat-messages'),input=document.getElementById('chat-input');function bubble(value,who){const d=document.createElement('div');d.className='bubble '+who;d.textContent=value;messages.append(d);messages.scrollTop=messages.scrollHeight}function answer(q){bubble(q,'user');const words=q.toLocaleLowerCase('es').split(/\W+/).filter(w=>w.length>3);const synonyms={ventas:['ventas','clientes','crm','comercial'],educación:['formacion','educacion','alumnos','aula','examen','asistencia'],IA:['inteligencia','privada','modelo','documento','rag','automatizar'],empresa:['factura','empresa','administracion','gestion']};const scored=PRODUCTS.map(p=>({p,s:words.filter(w=>(p.name+' '+p.category).toLocaleLowerCase('es').includes(w)).length+(Object.entries(synonyms).some(([k,terms])=>p.category.toLowerCase()===k.toLowerCase()&&terms.some(t=>q.toLowerCase().includes(t)))?2:0)})).sort((a,b)=>b.s-a.s).filter(x=>x.s>0).slice(0,4);bubble(scored.length?'Según tu consulta, puedes explorar: '+scored.map(x=>'jocarsa | '+x.p.name).join(', ')+'. Puedes encontrarlos en el catálogo y solicitar una propuesta.':'Podemos estudiar esa necesidad. Explora el catálogo por categorías o contacta para analizar un desarrollo a medida.','bot')}
document.getElementById('chat-form').addEventListener('submit',e=>{e.preventDefault();const q=input.value.trim();if(q)answer(q);input.value=''});document.querySelectorAll('[data-query]').forEach(b=>b.addEventListener('click',()=>answer(b.dataset.query)));

const clientTrack=document.getElementById('client-track');document.getElementById('client-prev').addEventListener('click',()=>clientTrack.scrollBy({left:-330,behavior:'smooth'}));document.getElementById('client-next').addEventListener('click',()=>clientTrack.scrollBy({left:330,behavior:'smooth'}));