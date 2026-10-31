// Carrusel horizontal real: las pantallas se mueven juntas dentro de un carril.
const contenedor = document.querySelector('#contenedor');
let vistaActual = null;
let pantallaActual = null;
let limpiezaActual = null;
let navegacion = 0;
let peticionActual = null;
let animando = false;

function indiceVista(nombre) {
  return [...document.querySelectorAll('.navegacion-principal [data-url]')]
    .findIndex(b => b.dataset.url === nombre);
}
function actualizarMenu(nombre) {
  document.querySelectorAll('.navegacion-principal [data-url]').forEach(b => {
    const activo = b.dataset.url === nombre;
    b.classList.toggle('activo', activo);
    if (activo) b.setAttribute('aria-current', 'page');
    else b.removeAttribute('aria-current');
  });
}
async function recurso(ruta, signal) {
  const separador = ruta.includes('?') ? '&' : '?';
  return fetch(`${ruta}${separador}t=${Date.now()}`, {cache:'no-store', signal});
}
function esperarTransicion(carril) {
  return new Promise(resolve => {
    if (matchMedia('(prefers-reduced-motion: reduce)').matches) return resolve();
    let terminado = false;
    const terminar = () => {
      if (terminado) return;
      terminado = true;
      carril.removeEventListener('transitionend', alTerminar);
      clearTimeout(respaldo);
      resolve();
    };
    const alTerminar = e => { if (e.target === carril && e.propertyName === 'transform') terminar(); };
    carril.addEventListener('transitionend', alTerminar);
    const respaldo = setTimeout(terminar, 650);
  });
}
async function cargarVista(nombre) {
  if (!/^[a-z0-9_-]+$/i.test(nombre)) return;
  if (animando || nombre === vistaActual) return;
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
    if (!modulo.ok && modulo.status !== 404) throw new Error('Error al cargar controlador');
    const codigo = modulo.ok ? await modulo.text() : null;
    if (actual !== navegacion) return;

    const anterior = pantallaActual;
    const haciaDerecha = anterior && indiceVista(nombre) > indiceVista(vistaActual);
    animando = true;
    const carril = document.createElement('div');
    carril.className = 'carril-pantallas';
    if (!anterior) {
      carril.style.width = '200%';
      carril.append(nueva);
      contenedor.replaceChildren(carril);
    } else {
      // El orden del DOM determina el desplazamiento físico de ambas pantallas.
      if (haciaDerecha) carril.append(anterior, nueva);
      else carril.append(nueva, anterior);
      contenedor.replaceChildren(carril);
      if (!haciaDerecha) carril.style.transform = 'translate3d(-50%,0,0)';
      // Dos fotogramas para garantizar que el navegador registre la posición inicial.
      await new Promise(resolve => requestAnimationFrame(() => requestAnimationFrame(resolve)));
      carril.classList.add('en-movimiento');
      carril.style.transform = haciaDerecha ? 'translate3d(-50%,0,0)' : 'translate3d(0,0,0)';
      await esperarTransicion(carril);
      if (limpiezaActual) {
        try { limpiezaActual(); } catch(e) { console.error(e); }
        limpiezaActual = null;
      }
      anterior.remove();
      carril.classList.remove('en-movimiento');
      carril.style.transform = 'translate3d(0,0,0)';
      carril.replaceChildren(nueva);
    }
    pantallaActual = nueva;
    vistaActual = nombre;
    actualizarMenu(nombre);
    if (codigo !== null) {
      try {
        const iniciar = new Function('contenedor', codigo);
        const limpiar = iniciar(nueva);
        if (typeof limpiar === 'function') limpiezaActual = limpiar;
      } catch(e) { console.error('Error inicializando controlador:', e); }
    }
  } catch(error) {
    if (error.name !== 'AbortError') console.error(error);
    if (actual === navegacion && !pantallaActual) contenedor.textContent = 'Error al cargar la vista';
  } finally {
    if (actual === navegacion) animando = false;
  }
}
document.addEventListener('click', e => {
  const boton = e.target.closest('.navegacion-principal [data-url]');
  if (!boton) return;
  e.preventDefault();
  cargarVista(boton.dataset.url);
});
cargarVista('inicio');
