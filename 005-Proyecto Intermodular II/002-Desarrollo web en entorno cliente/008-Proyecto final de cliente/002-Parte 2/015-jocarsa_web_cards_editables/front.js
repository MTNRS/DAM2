// Fuente única de datos para catálogo, megamenú y orientador.
let PRODUCTS=[];
let activeCategory='Todas';
const grid=document.getElementById('catalog-grid');
const filters=document.getElementById('catalog-filters');
const search=document.getElementById('product-search');
const normalize=value=>String(value??'').normalize('NFD').replace(/[\u0300-\u036f]/g,'').toLocaleLowerCase('es').trim();
const slug=value=>normalize(value).replace(/^jocarsa\s*[|\-]\s*/,'').replace(/[^a-z0-9]+/g,'');
function renderCatalog() {
    const query = normalize(search.value);
    const found = PRODUCTS.filter(product => {
        const matchesCategory = activeCategory === 'Todas' || product.category === activeCategory;
        const searchableText = normalize([
            product.name,
            product.category,
            product.slogan
        ].join(' '));
        return matchesCategory && searchableText.includes(query);
    });

    grid.replaceChildren();

    for (const product of found) {
        const article = document.createElement('article');
        article.className = 'catalog-card';

        // Primera fila: cabecera independiente, con la categoría.
        const header = document.createElement('header');
        header.className = 'catalog-product-head';

        const category = document.createElement('span');
        category.className = 'tag';
        category.textContent = product.category;
        header.append(category);

        // Segunda fila: logo a la izquierda y textos a la derecha.
        const body = document.createElement('div');
        body.className = 'catalog-product-body';

        const logo = document.createElement('img');
        logo.className = 'catalog-product-logo';
        const color = String(product.color || 'white').trim();
        logo.src = 'https://static.jocarsa.com/logos/' + encodeURIComponent(color) + '.svg';
        logo.alt = 'Logotipo de jocarsa | ' + product.name;
        logo.loading = 'lazy';
        logo.onerror = () => {
            if (!logo.dataset.fallback) {
                logo.dataset.fallback = '1';
                logo.src = 'https://static.jocarsa.com/logos/white.svg';
            } else {
                logo.hidden = true;
            }
        };

        const content = document.createElement('div');
        content.className = 'catalog-product-info';

        const title = document.createElement('h3');
        title.textContent = 'jocarsa | ' + product.name;

        const slogan = document.createElement('p');
        slogan.textContent = product.slogan || 'Solución del ecosistema JOCARSA';

        content.append(title, slogan);
        body.append(logo, content);
        article.append(header, body);
        grid.append(article);
    }

    document.getElementById('product-count').textContent =
        found.length + ' productos de ' + PRODUCTS.length;

    filters.querySelectorAll('button').forEach(button => {
        button.setAttribute(
            'aria-pressed',
            String(button.dataset.category === activeCategory)
        );
    });
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

