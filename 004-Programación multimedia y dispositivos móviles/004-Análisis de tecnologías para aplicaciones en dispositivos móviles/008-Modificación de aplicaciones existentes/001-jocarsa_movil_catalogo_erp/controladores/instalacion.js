const boton=contenedor.querySelector('#pwa-boton');
const estadoEl=contenedor.querySelector('#pwa-estado');
const instrucciones=contenedor.querySelector('#pwa-instrucciones');
const instalador=window.JocarsaInstalador;
function actualizar(){
  const estado=instalador.estado();
  boton.hidden=estado.instalada || !estado.disponible;
  boton.disabled=!estado.disponible;
  if(estado.instalada){estadoEl.textContent='✓ La aplicación está abierta en modo instalado.';instrucciones.innerHTML='<h2>Aplicación instalada</h2><p>Ya puedes utilizarla desde su icono en la pantalla de inicio o en el lanzador de aplicaciones.</p>';return;}
  if(!estado.seguro){estadoEl.textContent='Se necesita HTTPS o localhost para la instalación.';instrucciones.innerHTML='<p>Publica la aplicación en un origen seguro y vuelve a abrir esta sección.</p>';return;}
  if(estado.disponible){estadoEl.textContent='La instalación está disponible en este navegador.';instrucciones.innerHTML='<p>Pulsa «Instalar aplicación» y confirma la solicitud del navegador.</p>';return;}
  estadoEl.textContent=estado.resultado || 'El navegador no ofrece actualmente un instalador automático.';
  if(estado.plataforma==='ios'){
    instrucciones.innerHTML='<h2>Instalación en iPhone o iPad</h2><ol><li>Abre esta página en Safari. En versiones compatibles, otros navegadores también pueden ofrecer la opción.</li><li>Abre el menú Compartir o el menú de la página.</li><li>Selecciona «Añadir a pantalla de inicio».</li><li>Confirma con «Añadir».</li></ol>';
  } else if(estado.plataforma==='android'){
    instrucciones.innerHTML='<h2>Instalación en Android</h2><ol><li>Abre la página en Chrome u otro navegador compatible.</li><li>Abre el menú del navegador (⋮).</li><li>Selecciona «Instalar aplicación» o «Añadir a pantalla de inicio», si aparece.</li><li>Confirma la instalación.</li></ol><p>Si la opción no aparece, comprueba que el sitio sea HTTPS y que el navegador admita la instalación.</p>';
  } else {
    instrucciones.innerHTML='<h2>Instalación en ordenador</h2><p>Busca la opción «Instalar» en la barra de direcciones o en el menú del navegador compatible. Algunos navegadores no permiten instalar esta aplicación.</p>';
  }
}
async function instalar(){const resultado=await instalador.instalar();actualizar();if(resultado.estado==='error')JocarsaToast.error('No se pudo abrir el instalador');}
boton.addEventListener('click',instalar);
window.addEventListener('jocarsa:instalacion',actualizar);
actualizar();
return ()=>{boton.removeEventListener('click',instalar);window.removeEventListener('jocarsa:instalacion',actualizar);};
