// Navegación direccional con carga de vistas, templates y controladores sin caché.
const contenedor = document.querySelector('#contenedor');
let navegacion = 0;
let vistaActual = null;
let pantallaActual = null;
let limpiezaActual = null;
let peticionActual = null;

function indiceVista(nombre) {
  return [...document.querySelectorAll('.navegacion-principal [data-url]')]
    .findIndex(b => b.dataset.url === nombre);
}
function direccionEntre(origen, destino) {
  const a = indiceVista(origen), b = indiceVista(destino);
  return a >= 0 && b >= 0 && b < a ? 'izquierda' : 'derecha';
}
async function recurso(ruta, signal) {
  return fetch(`${ruta}?t=${Date.now()}`, {cache:'no-store', signal});
}
function actualizarMenu(nombre) {
  document.querySelectorAll('.navegacion-principal [data-url]').forEach(b => {
    const activo = b.dataset.url === nombre;
    b.classList.toggle('activo', activo);
    b.setAttribute('aria-current', activo ? 'page' : 'false');
  });
}
async function cargarVista(nombre) {
  if (!/^[a-z0-9_-]+$/i.test(nombre)) return;
  if (nombre === vistaActual && pantallaActual) return;
  const actual = ++navegacion;
  if (peticionActual) peticionActual.abort();
  const peticion = new AbortController();
  peticionActual = peticion;
  try {
    const respuesta = await recurso(`vistas/${nombre}.html`, peticion.signal);
    if (!respuesta.ok) throw new Error(`Vista no encontrada: ${nombre}`);
    const html = await respuesta.text();
    if (actual !== navegacion) return;

    const nueva = document.createElement('div');
    nueva.className = 'pantalla';
    nueva.innerHTML = html;
    await Componentes.renderizar(nueva);
    if (actual !== navegacion) return;

    const modulo = await recurso(`controladores/${nombre}.js`, peticion.signal);
    if (actual !== navegacion) return;
    if (!modulo.ok && modulo.status !== 404) throw new Error('Error cargando controlador');
    const codigo = modulo.ok ? await modulo.text() : null;
    if (actual !== navegacion) return;

    const anterior = pantallaActual;
    const direccion = anterior ? direccionEntre(vistaActual, nombre) : null;
    if (limpiezaActual) { limpiezaActual(); limpiezaActual = null; }

    // Eliminar restos de animaciones interrumpidas antes de iniciar la nueva.
    contenedor.querySelectorAll('.pantalla').forEach(p => {
      if (p !== anterior) p.remove();
    });
    if (anterior) {
      anterior.className = 'pantalla';
      anterior.style.cssText = '';
    }
    contenedor.appendChild(nueva);
    pantallaActual = nueva;
    vistaActual = nombre;
    actualizarMenu(nombre);

    if (codigo !== null) {
      try {
        const iniciar = new Function('contenedor', codigo);
        const limpiar = iniciar(nueva);
        if (typeof limpiar === 'function') limpiezaActual = limpiar;
      } catch (error) {
        console.error('Error inicializando controlador:', error);
      }
    }

    if (!anterior) return;
    anterior.classList.add('pantalla-saliente');
    anterior.style.pointerEvents = 'none';
    const salida = direccion === 'derecha' ? 'pantalla-sale-izquierda' : 'pantalla-sale-derecha';
    const entrada = direccion === 'derecha' ? 'pantalla-entra-derecha' : 'pantalla-entra-izquierda';
    anterior.classList.add(salida);
    nueva.classList.add(entrada);
    let terminado = false;
    const finalizar = () => {
      if (terminado) return;
      terminado = true;
      anterior.remove();
      nueva.classList.remove(entrada);
    };
    nueva.addEventListener('animationend', finalizar, {once:true});
    setTimeout(finalizar, 500);
  } catch (error) {
    if (error.name === 'AbortError') return;
    if (actual === navegacion) {
      console.error(error);
      if (!pantallaActual) contenedor.textContent = 'Error al cargar la vista';
    }
  }
}
document.addEventListener('click', evento => {
  const boton = evento.target.closest('.navegacion-principal [data-url]');
  if (!boton) return;
  evento.preventDefault();
  cargarVista(boton.dataset.url);
});
cargarVista('inicio');
