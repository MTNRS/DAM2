/* jocarsa | móvil · API de dispositivo. Solicitar permisos únicamente por acción del usuario. */
const JocarsaDevice = (() => {
  const secure = () => window.isSecureContext;
  const soporta = () => ({seguro:secure(),camara:!!navigator.mediaDevices?.getUserMedia,microfono:!!navigator.mediaDevices?.getUserMedia,ubicacion:!!navigator.geolocation,archivos:typeof FileReader!=='undefined',compartir:!!navigator.share,portapapeles:!!navigator.clipboard?.writeText,orientacion:"DeviceOrientationEvent" in window,movimiento:"DeviceMotionEvent" in window,vibracion:!!navigator.vibrate,pantallaCompleta:!!document.documentElement.requestFullscreen,pantallaActiva:!!navigator.wakeLock});
  async function permisos(nombre) {
    if(!navigator.permissions?.query)return 'no disponible';
    try { return (await navigator.permissions.query({name:nombre})).state; }
    catch { return 'no consultable'; }
  }
  async function abrirCamara({facingMode='environment'}={}) {
    if(!navigator.mediaDevices?.getUserMedia)throw Error('Cámara no disponible: comprueba HTTPS y permisos.');
    return navigator.mediaDevices.getUserMedia({video:{facingMode:{ideal:facingMode}},audio:false});
  }
  async function abrirMicrofono() {
    if(!navigator.mediaDevices?.getUserMedia)throw Error('Micrófono no disponible: comprueba HTTPS y permisos.');
    return navigator.mediaDevices.getUserMedia({audio:true,video:false});
  }
  function ubicacion({enableHighAccuracy=false,timeout=12000,maximumAge=0}={}) {
    if(!navigator.geolocation)return Promise.reject(Error('Geolocalización no disponible.'));
    return new Promise((resolve,reject)=>navigator.geolocation.getCurrentPosition(resolve,reject,{enableHighAccuracy,timeout,maximumAge}));
  }
  function leerArchivo(file,{modo='text'}={}) {
    if(!(file instanceof File))return Promise.reject(Error('Selecciona un archivo.'));
    return modo==='arrayBuffer'?file.arrayBuffer():modo==='dataURL'?new Promise((resolve,reject)=>{const r=new FileReader();r.onload=()=>resolve(r.result);r.onerror=()=>reject(r.error);r.readAsDataURL(file);}):file.text();
  }
  async function compartir(data){if(!navigator.share)throw Error('Compartir no disponible en este navegador.');return navigator.share(data);}
  async function copiar(texto){if(!navigator.clipboard?.writeText)throw Error('Portapapeles no disponible.');await navigator.clipboard.writeText(texto);}
  function detener(stream){stream?.getTracks().forEach(t=>t.stop());}
  async function solicitarSensores() {
    // iOS exige una acción explícita del usuario para autorizar sensores.
    for (const API of [window.DeviceOrientationEvent,window.DeviceMotionEvent]) {
      if (typeof API?.requestPermission === 'function') {
        const resultado=await API.requestPermission();
        if(resultado!=='granted')throw Error('Permiso de sensores denegado.');
      }
    }
  }
  function observarOrientacion(callback){
    if(!('DeviceOrientationEvent' in window))throw Error('Orientación no compatible.');
    const fn=e=>callback({alpha:e.alpha,beta:e.beta,gamma:e.gamma,absolute:e.absolute});
    window.addEventListener('deviceorientation',fn);
    return ()=>window.removeEventListener('deviceorientation',fn);
  }
  function observarMovimiento(callback){
    if(!('DeviceMotionEvent' in window))throw Error('Movimiento no compatible.');
    const fn=e=>callback({aceleracion:e.acceleration?{x:e.acceleration.x,y:e.acceleration.y,z:e.acceleration.z}:null,gravedad:e.accelerationIncludingGravity?{x:e.accelerationIncludingGravity.x,y:e.accelerationIncludingGravity.y,z:e.accelerationIncludingGravity.z}:null,rotacion:e.rotationRate?{alpha:e.rotationRate.alpha,beta:e.rotationRate.beta,gamma:e.rotationRate.gamma}:null,intervalo:e.interval});
    window.addEventListener('devicemotion',fn);
    return ()=>window.removeEventListener('devicemotion',fn);
  }
  function vibrar(patron=150){if(!navigator.vibrate)throw Error('Vibración no compatible.');return navigator.vibrate(patron);}
  async function pantallaCompleta(elemento=document.documentElement){if(!elemento.requestFullscreen)throw Error('Pantalla completa no compatible.');await elemento.requestFullscreen();}
  async function salirPantallaCompleta(){if(document.fullscreenElement)await document.exitFullscreen();}
  async function mantenerPantallaActiva(){if(!navigator.wakeLock?.request)throw Error('Wake Lock no compatible.');return navigator.wakeLock.request('screen');}
  return {solicitarSensores,observarOrientacion,observarMovimiento,vibrar,pantallaCompleta,salirPantallaCompleta,mantenerPantallaActiva,soporta,permisos,abrirCamara,abrirMicrofono,ubicacion,leerArchivo,compartir,copiar,detener};
})();
