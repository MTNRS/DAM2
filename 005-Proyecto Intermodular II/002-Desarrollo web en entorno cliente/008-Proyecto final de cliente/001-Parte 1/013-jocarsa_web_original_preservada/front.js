// Fuente única de datos para catálogo, megamenú y orientador.
let PRODUCTS=[];
let activeCategory='Todas';
const grid=document.getElementById('catalog-grid');
const filters=document.getElementById('catalog-filters');
const search=document.getElementById('product-search');
const normalize=value=>String(value??'').normalize('NFD').replace(/[\u0300-\u036f]/g,'').toLocaleLowerCase('es').trim();
const slug=value=>normalize(value).replace(/^jocarsa\s*[|\-]\s*/,'').replace(/[^a-z0-9]+/g,'');
function renderCatalog(){
 const q=normalize(search.value);
 const found=PRODUCTS.filter(p=>(activeCategory==='Todas'||p.category===activeCategory)&&normalize([p.name,p.category,p.slogan].join(' ')).includes(q));
 grid.replaceChildren();
 for(const p of found){
  const article=document.createElement('article');article.className='catalog-card';
  const header=document.createElement('div');header.className='catalog-product-head';
  const img=document.createElement('img');img.className='catalog-product-logo';
  const color=String(p.color||'white').trim();
  img.src='https://static.jocarsa.com/logos/'+encodeURIComponent(color)+'.svg';
  img.alt='Logo de jocarsa | '+p.name;img.loading='lazy';
  img.onerror=()=>{if(!img.dataset.fallback){img.dataset.fallback='1';img.src='https://static.jocarsa.com/logos/white.svg'}else img.hidden=true};
  const tag=document.createElement('span');tag.className='tag';tag.textContent=p.category;
  header.append(img,tag);
  const title=document.createElement('h3');title.textContent='jocarsa | '+p.name;
  const desc=document.createElement('p');desc.textContent=p.slogan||'Solución del ecosistema JOCARSA';
  article.append(header,title,desc);grid.append(article);
 }
 document.getElementById('product-count').textContent=found.length+' productos de '+PRODUCTS.length;
 filters.querySelectorAll('button').forEach(b=>b.setAttribute('aria-pressed',String(b.dataset.category===activeCategory)));
}
function renderMegaMenu(categorias){
 const menu=document.getElementById('mega-products-grid');if(!menu)return;
 menu.replaceChildren();
 for(const cat of categorias){
  const column=document.createElement('div');column.className='mega-column';
  const heading=document.createElement('h3');heading.textContent=cat.nombre;column.append(heading);
  for(const p of cat.productos||[]){
   const a=document.createElement('a');a.href='#productos';a.textContent=p.nombre;
   a.dataset.category=cat.nombre;a.dataset.product=p.nombre;
   a.title='Ver jocarsa | '+p.nombre;column.append(a);
  }
  menu.append(column);
 }
}
async function loadProducts(){
 try{
  const response=await fetch('./jocarsa_productos_por_categorias.json',{cache:'no-cache'});
  if(!response.ok)throw new Error('HTTP '+response.status);
  const data=await response.json();
  if(!Array.isArray(data.categorias))throw new Error('Formato JSON no válido');
  PRODUCTS=data.categorias.flatMap(cat=>(cat.productos||[]).map(p=>({
   category:cat.nombre,name:p.nombre,slogan:p.slogan,color:p.color
  })));
  filters.replaceChildren();
  for(const category of ['Todas',...data.categorias.map(cat=>cat.nombre)]){
   const b=document.createElement('button');b.type='button';b.textContent=category;b.dataset.category=category;
   b.addEventListener('click',()=>{activeCategory=category;renderCatalog()});filters.append(b);
  }
  renderMegaMenu(data.categorias);renderCatalog();
 }catch(error){
  console.error('Error cargando catálogo:',error);
  document.getElementById('product-count').textContent='No se pudo cargar el catálogo. Comprueba el JSON y el servidor.';
 }
}
search.addEventListener('input',renderCatalog);
loadProducts();

