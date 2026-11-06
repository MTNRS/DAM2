/* Registro, estado de red y actualizaciones de la PWA. */
window.JocarsaOffline = (() => {
  let registro = null;
  let esperando = false;
  let actualizando = false;
  let version = '4.0';
  const status = () => ({online:navigator.onLine, soportado:'serviceWorker' in navigator, activo:!!navigator.serviceWorker?.controller, actualizacion:!!registro?.waiting, version});
  function avisar(){window.dispatchEvent(new CustomEvent('jocarsa:offline',{detail:status()}));}
  function avisoActualizacion(){
    avisar();
    if (registro?.waiting && !esperando) {
      esperando = true;
      window.JocarsaToast?.info?.('Hay una nueva versión disponible. Abre «Offline» para actualizar.');
    }
  }
  function red(){
    document.documentElement.classList.toggle('jocarsa-sin-red',!navigator.onLine);
    avisar();
    if(navigator.onLine) registro?.update().catch(()=>{});
  }
  window.addEventListener('online',red);
  window.addEventListener('offline',red);
  async function iniciar(){
    red();
    if (!('serviceWorker' in navigator) || !isSecureContext) return;
    try {
      registro=await navigator.serviceWorker.register('./sw.js',{scope:'./',updateViaCache:'none'});
      if(registro.waiting) avisoActualizacion();
      registro.addEventListener('updatefound',()=>{
        const worker=registro.installing;
        worker?.addEventListener('statechange',()=>{
          if(worker.state==='installed' && navigator.serviceWorker.controller) avisoActualizacion();
          avisar();
        });
      });
      navigator.serviceWorker.addEventListener('controllerchange',()=>{
        if(actualizando) location.reload();
        else avisar();
      });
      navigator.serviceWorker.addEventListener('message',e=>{if(e.data?.type==='VERSION'){version=e.data.version;avisar();}});
      await navigator.serviceWorker.ready;
      navigator.serviceWorker.controller?.postMessage({type:'VERSION'});
      avisar();
    }catch(e){console.warn('No se pudo registrar el Service Worker:',e);avisar();}
  }
  async function comprobar(){if(!registro) return false;await registro.update();avisar();return !!registro.waiting;}
  function actualizar(){if(!registro?.waiting)return false;actualizando=true;registro.waiting.postMessage({type:'SKIP_WAITING'});return true;}
  iniciar();
  return {estado:status,comprobar,actualizar};
})();
