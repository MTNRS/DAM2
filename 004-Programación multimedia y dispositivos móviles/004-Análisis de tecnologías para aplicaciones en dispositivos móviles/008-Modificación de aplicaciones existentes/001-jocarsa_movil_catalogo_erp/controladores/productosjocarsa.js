const salida = contenedor.querySelector('#datosjocarsa');
const buscador = contenedor.querySelector('#buscar-erp');
const categorias = contenedor.querySelector('#categorias-erp');
const estado = contenedor.querySelector('#estado-erp');
const recargar = contenedor.querySelector('#recargar-erp');
const abort = new AbortController();
let productos = [], categoria = 'todas';
const normalizar = x => String(x ?? '').toLocaleLowerCase('es');
function pintar() {
  const q = normalizar(buscador.value.trim());
  const lista = productos.filter(p => (categoria === 'todas' || p.categoria === categoria) && normalizar(p.nombre+' '+p.descripcion).includes(q));
  salida.replaceChildren();
  estado.textContent = `${lista.length} de ${productos.length} productos`;
  if (!lista.length) { salida.textContent = 'No hay productos para estos filtros.'; return; }
  for (const p of lista) {
    const card = document.createElement('article'); card.className = 'erp-producto';
    if (/^#[0-9a-f]{3,8}$/i.test(p.color || '')) card.style.borderTopColor = p.color;
    const etiqueta = document.createElement('small'); etiqueta.textContent = 'Categoría '+p.categoria;
    const titulo = document.createElement('h3'); titulo.textContent = p.nombre;
    const descripcion = document.createElement('p'); descripcion.textContent = p.descripcion;
    card.append(etiqueta,titulo,descripcion); salida.append(card);
  }
}
async function cargar() {
  recargar.disabled = true; estado.textContent = 'Conectando con ERP…';
  try {
    const conf = window.JOCARSA_ENDPOINTS.productos;
    const respuesta = await JocarsaAPI.get(conf.url, {headers:{'X-API-Key':conf.apiKey},responseType:'json',signal:abort.signal,retries:1,timeout:15000});
    if (!Array.isArray(respuesta?.data)) throw new Error('La API no devolvió un array data');
    productos = respuesta.data.map(p => ({nombre:String(p.nombre??''),descripcion:String(p.descripcion??''),categoria:String(p.categoria??'Sin categoría'),color:p.color}));
    const valores = [...new Set(productos.map(p=>p.categoria))].sort((a,b)=>a.localeCompare(b,'es',{numeric:true}));
    categorias.replaceChildren(new Option('Todas las categorías','todas'),...valores.map(c=>new Option('Categoría '+c,c)));
    categoria='todas'; pintar();
  } catch(e) {
    if (!abort.signal.aborted) {estado.textContent = 'Error al cargar productos';salida.textContent = e.message+' — Comprueba CORS, la clave API y la dirección del servidor.';}
  } finally {recargar.disabled=false;}
}
const buscar = () => pintar();
const cambiar = () => {categoria=categorias.value;pintar();};
buscador.addEventListener('input',buscar);
categorias.addEventListener('change',cambiar);
recargar.addEventListener('click',cargar);
cargar();
return () => {abort.abort();buscador.removeEventListener('input',buscar);categorias.removeEventListener('change',cambiar);recargar.removeEventListener('click',cargar);};
