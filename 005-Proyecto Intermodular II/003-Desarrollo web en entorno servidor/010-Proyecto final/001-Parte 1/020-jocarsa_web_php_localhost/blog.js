(async function(){
 const grid=document.getElementById('blog-grid'),status=document.getElementById('blog-status');
 const search=document.getElementById('blog-search'),pagination=document.getElementById('blog-pagination');
 if(!grid)return;
 let items=[],page=1;const perPage=6;
 function render(){
  const q=search.value.toLocaleLowerCase('es').trim();
  const found=items.filter(a=>(a.titulo+' '+a.contenido).toLocaleLowerCase('es').includes(q));
  const total=Math.max(1,Math.ceil(found.length/perPage));page=Math.min(page,total);
  grid.replaceChildren();pagination.replaceChildren();
  status.textContent=found.length+' artículo(s)';
  for(const a of found.slice((page-1)*perPage,page*perPage)){
   const card=document.createElement('article');card.className='card blog-card';
   const date=document.createElement('time');date.className='tag';date.textContent=a.fecha||'Sin fecha';
   if(a.fecha && /^\d{4}-\d{2}-\d{2}/.test(a.fecha))date.dateTime=a.fecha.slice(0,10);
   const title=document.createElement('h3');title.textContent=a.titulo;
   const excerpt=document.createElement('p');excerpt.textContent=a.contenido.length>240?a.contenido.slice(0,240)+'…':a.contenido;
   const button=document.createElement('button');button.type='button';button.textContent='Leer artículo';button.setAttribute('aria-expanded','false');
   button.addEventListener('click',()=>{const open=button.getAttribute('aria-expanded')==='true';excerpt.textContent=open?(a.contenido.length>240?a.contenido.slice(0,240)+'…':a.contenido):a.contenido;button.textContent=open?'Leer artículo':'Cerrar artículo';button.setAttribute('aria-expanded',String(!open))});
   card.append(date,title,excerpt,button);grid.append(card);
  }
  if(total>1){for(let n=1;n<=total;n++){const b=document.createElement('button');b.textContent=n;b.type='button';b.disabled=n===page;b.addEventListener('click',()=>{page=n;render()});pagination.append(b)}}
 }
 search.addEventListener('input',()=>{page=1;render()});
 try{
  const payload=await window.JOCARSA_API.get('articulosblog');
  const rows=Array.isArray(payload)?payload:(payload.data??payload.items??payload.registros??[]);
  if(!Array.isArray(rows))throw new Error('La API no devuelve una lista');
  items=rows.map(a=>({titulo:String(a.titulo??''),fecha:String(a.fecha??''),contenido:String(a.contenido??'')})).filter(a=>a.titulo);
  items.sort((a,b)=>b.fecha.localeCompare(a.fecha));render();
 }catch(e){console.error('Blog:',e);status.textContent='No se pudieron cargar los artículos. Revisa el endpoint, la clave y CORS.'}
})();
