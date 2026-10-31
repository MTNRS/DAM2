/* jocarsa | móvil · Entrega 3 — offline (mismo origen, sin almacenar APIs externas). */
const VERSION = 'jocarsa-movil-v4-20261026-1';
const SHELL = VERSION + '-shell';
const RUNTIME = VERSION + '-runtime';
const PREFIX = 'jocarsa-movil-v4-';
const FILES = [
  "./api.js",
  "./almacenamiento.js",
  "./sincronizacion.js",
  "./controladores/sincronizacion.js",
  "./vistas/sincronizacion.html",
  "./componentes.js",
  "./controlador.js",
  "./controladores/avanzados.js",
  "./controladores/formularios.js",
  "./controladores/instalacion.js",
  "./controladores/offline.js",
  "./controladores/productos.js",
  "./controladores/productosjocarsa.js",
  "./controladores/servicios.js",
  "./controladores/widgets.js",
  "./estilo.css",
  "./icons/apple-touch-icon.png",
  "./icons/favicon-32.png",
  "./icons/favicon.svg",
  "./icons/icon-192.png",
  "./icons/icon-512.png",
  "./icons/maskable-512.png",
  "./index.html",
  "./instalador.js",
  "./jocarsa-componentes/jocarsa-extension.js",
  "./jocarsa-componentes/jocarsa-iu.css",
  "./jocarsa-componentes/jocarsa-ui.js",
  "./manifest.webmanifest",
  "./offline.js",
  "./splash.js",
  "./templates/acordeon.html",
  "./templates/archivo.html",
  "./templates/ayuda.html",
  "./templates/badge.html",
  "./templates/boton.html",
  "./templates/busqueda.html",
  "./templates/cabecera.html",
  "./templates/celda.html",
  "./templates/checkbox.html",
  "./templates/color.html",
  "./templates/datatable.html",
  "./templates/estado.html",
  "./templates/etiquetas.html",
  "./templates/fecha.html",
  "./templates/fieldset.html",
  "./templates/fila.html",
  "./templates/formulario.html",
  "./templates/grafica.html",
  "./templates/grupo.html",
  "./templates/hora.html",
  "./templates/input.html",
  "./templates/item.html",
  "./templates/kpi.html",
  "./templates/lista.html",
  "./templates/medidor.html",
  "./templates/menu.html",
  "./templates/pin.html",
  "./templates/productosjocarsa.html",
  "./templates/progreso.html",
  "./templates/radio.html",
  "./templates/rango.html",
  "./templates/segmentado.html",
  "./templates/select.html",
  "./templates/switch.html",
  "./templates/tabla.html",
  "./templates/tarjeta.html",
  "./templates/textarea.html",
  "./templates/timeline.html",
  "./templates/titulo.html",
  "./templates/treeview.html",
  "./templates/valoracion.html",
  "./toast.js",
  "./vistas/avanzados.html",
  "./vistas/formularios.html",
  "./vistas/inicio.html",
  "./vistas/instalacion.html",
  "./vistas/offline.html",
  "./vistas/productos.html",
  "./vistas/productosjocarsa.html",
  "./vistas/servicios.html",
  "./vistas/sobremi.html",
  "./vistas/widgets.html"
];
const scopeURL = new URL(self.registration.scope);
function localAsset(url) {
  if (url.origin !== scopeURL.origin || !url.pathname.startsWith(scopeURL.pathname)) return false;
  const relative = url.pathname.slice(scopeURL.pathname.length);
  if (!relative || relative === 'index.html') return true;
  if (relative.includes('..') || relative.startsWith('api/') || relative.startsWith('api.')) return false;
  return /^(?:vistas\/[a-z0-9_-]+\.html|templates\/[a-z0-9-]+\.html|controladores\/[a-z0-9_-]+\.js|jocarsa-componentes\/[a-z0-9_-]+\.(?:js|css)|icons\/[a-z0-9_-]+\.(?:png|svg)|[a-z0-9_-]+\.(?:js|css|webmanifest))$/i.test(relative);
}
function clean(request) { const url = new URL(request.url); url.search = ''; url.hash = ''; return url.href; }
self.addEventListener('install', event => {
  event.waitUntil((async () => {
    const cache = await caches.open(SHELL);
    // Requerimos que todo el código de la app se haya guardado para activar el modo offline.
    await cache.addAll(FILES);
    // No forzar skipWaiting: las pestañas existentes podrán elegir cuándo actualizar.
  })());
});
self.addEventListener('activate', event => {
  event.waitUntil((async () => {
    const keys = await caches.keys();
    await Promise.all(keys.filter(k => k.startsWith('jocarsa-movil-') && k !== SHELL && k !== RUNTIME).map(k => caches.delete(k)));
    await self.clients.claim();
  })());
});
self.addEventListener('message', event => {
  if (event.data?.type === 'SKIP_WAITING') self.skipWaiting();
  if (event.data?.type === 'VERSION') event.source?.postMessage({type:'VERSION',version:VERSION});
});
self.addEventListener('fetch', event => {
  const request = event.request;
  if (request.method !== 'GET') return;
  const url = new URL(request.url);
  if (!localAsset(url)) return; // Nunca interceptar ni almacenar endpoints externos o de API.
  event.respondWith((async () => {
    const key = clean(request);
    const shell = await caches.open(SHELL);
    const runtime = await caches.open(RUNTIME);
    // El precache garantiza un fallback. Online, buscar nuevas versiones en red.
    try {
      const response = await fetch(request);
      if (response.ok && response.type === 'basic') await runtime.put(key, response.clone());
      return response;
    } catch (err) {
      const cached = await runtime.match(key) || await shell.match(key);
      if (cached) return cached;
      if (request.mode === 'navigate') {
        const fallback = await shell.match(new URL('./index.html', scopeURL).href);
        if (fallback) return fallback;
      }
      return new Response('Recurso no disponible sin conexión', {status:503,headers:{'Content-Type':'text/plain; charset=utf-8'}});
    }
  })());
});
