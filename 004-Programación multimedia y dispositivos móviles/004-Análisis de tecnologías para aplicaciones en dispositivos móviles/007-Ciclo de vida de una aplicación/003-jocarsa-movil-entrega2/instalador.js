/* Gestión global de instalación PWA; el evento puede llegar antes de abrir la vista. */
window.JocarsaInstalador = (() => {
  let evento = null;
  let ultimoResultado = '';
  const esStandalone = () => window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
  const esIOS = () => /iPad|iPhone|iPod/.test(navigator.userAgent) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
  const navegador = () => {
    if (esIOS()) return 'ios';
    if (/Android/i.test(navigator.userAgent)) return 'android';
    return 'escritorio';
  };
  function estado() {return {instalada: esStandalone(), disponible: !!evento, plataforma: navegador(), resultado: ultimoResultado, seguro: window.isSecureContext};}
  function notificar(){window.dispatchEvent(new CustomEvent('jocarsa:instalacion', {detail:estado()}));}
  window.addEventListener('beforeinstallprompt', e => {e.preventDefault(); evento=e; ultimoResultado=''; notificar();});
  window.addEventListener('appinstalled', () => {evento=null; ultimoResultado='Aplicación instalada correctamente.'; notificar();});
  window.matchMedia('(display-mode: standalone)').addEventListener?.('change', notificar);
  async function instalar() {
    if(esStandalone()) return {estado:'instalada'};
    if(!evento) return {estado:'no-disponible'};
    const solicitud=evento; evento=null; notificar();
    try {
      await solicitud.prompt();
      const respuesta=await solicitud.userChoice;
      ultimoResultado=respuesta.outcome==='accepted'?'Solicitud de instalación aceptada.':'Instalación cancelada.';
      notificar();
      return {estado:respuesta.outcome};
    } catch(error) {ultimoResultado='No se pudo mostrar el instalador: '+error.message;notificar();return {estado:'error',error};}
  }
  return {estado,instalar};
})();
