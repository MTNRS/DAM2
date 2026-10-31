const panel=contenedor.querySelector('#offline-status');
const comprobar=contenedor.querySelector('#offline-comprobar');
const actualizar=contenedor.querySelector('#offline-actualizar');
const mensaje=contenedor.querySelector('#offline-mensaje');
const offline=window.JocarsaOffline;
function pintar(){const e=offline.estado();panel.textContent=`Red: ${e.online?'conectado':'sin conexión'} · Caché offline: ${e.activo?'activo':'pendiente'} · Versión: ${e.version}`;actualizar.hidden=!e.actualizacion;mensaje.textContent=!e.soportado?'Este navegador no admite Service Workers.':!window.isSecureContext?'Se necesita HTTPS o localhost.':e.actualizacion?'Nueva versión lista. Pulsa «Instalar nueva versión» para recargar.':e.activo?'La interfaz está preparada para funcionar sin conexión.':'Preparando el modo offline. En la primera instalación puede ser necesario recargar.';}
async function verificar(){comprobar.disabled=true;mensaje.textContent='Buscando actualizaciones…';try{const hay=await offline.comprobar();pintar();if(!hay)mensaje.textContent='No hay ninguna actualización lista para instalar.';}catch(e){mensaje.textContent='No se pudo comprobar: '+e.message;}finally{comprobar.disabled=false;}}
function instalar(){offline.actualizar();}
comprobar.addEventListener('click',verificar);actualizar.addEventListener('click',instalar);window.addEventListener('jocarsa:offline',pintar);pintar();
return ()=>{comprobar.removeEventListener('click',verificar);actualizar.removeEventListener('click',instalar);window.removeEventListener('jocarsa:offline',pintar);};
