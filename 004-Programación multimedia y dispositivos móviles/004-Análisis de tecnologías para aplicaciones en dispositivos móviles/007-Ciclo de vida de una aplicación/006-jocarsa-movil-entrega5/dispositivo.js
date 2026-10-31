/* jocarsa | móvil · API de dispositivo. Solicitar permisos únicamente por acción del usuario. */
const JocarsaDevice = (() => {
  const secure = () => window.isSecureContext;
  const soporta = () => ({seguro:secure(),camara:!!navigator.mediaDevices?.getUserMedia,microfono:!!navigator.mediaDevices?.getUserMedia,ubicacion:!!navigator.geolocation,archivos:typeof FileReader!=='undefined',compartir:!!navigator.share,portapapeles:!!navigator.clipboard?.writeText});
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
  return {soporta,permisos,abrirCamara,abrirMicrofono,ubicacion,leerArchivo,compartir,copiar,detener};
})();
