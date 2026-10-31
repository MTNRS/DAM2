const $ = id => contenedor.querySelector('#'+id);
const listeners=[];const on=(id,event,fn)=>{const el=$(id);el.addEventListener(event,fn);listeners.push(()=>el.removeEventListener(event,fn));};
let camera=null,mic=null,recorder=null,chunks=[],photoURL=null,audioURL=null;
const status=message=>{$('device-status').textContent=message;};
const error=e=>{status(e?.message||String(e));JocarsaToast.error(e?.message||String(e));};
const caps=JocarsaDevice.soporta();
$('device-context').textContent=caps.seguro?'Contexto seguro (HTTPS/localhost)':'Contexto NO seguro: algunas APIs estarán bloqueadas.';
$('device-capabilities').innerHTML=Object.entries(caps).map(([k,v])=>`<div><strong>${k}</strong><span>${v?'Disponible':'No disponible'}</span></div>`).join('');
const revoke=(url)=>{if(url)URL.revokeObjectURL(url);};
function stopCamera(){JocarsaDevice.detener(camera);camera=null;const video=$('device-camera-preview');video.pause();video.srcObject=null;video.hidden=true;}
function stopMic(){if(recorder&&recorder.state!=='inactive')recorder.stop();else{JocarsaDevice.detener(mic);mic=null;}}
on('device-camera-start','click',async()=>{try{stopCamera();camera=await JocarsaDevice.abrirCamara();const video=$('device-camera-preview');video.srcObject=camera;video.hidden=false;await video.play();status('Cámara activada');}catch(e){error(e);}});
on('device-camera-photo','click',()=>{try{const video=$('device-camera-preview');if(!camera||!video.videoWidth)throw Error('Abre primero la cámara.');const canvas=$('device-camera-canvas');canvas.width=video.videoWidth;canvas.height=video.videoHeight;canvas.getContext('2d').drawImage(video,0,0);canvas.toBlob(blob=>{if(!blob){error(Error('No se pudo capturar la fotografía.'));return;}revoke(photoURL);photoURL=URL.createObjectURL(blob);$('device-camera-result').src=photoURL;$('device-camera-result').hidden=false;$('device-camera-download').href=photoURL;$('device-camera-download').hidden=false;status('Foto capturada localmente');},'image/png');}catch(e){error(e);}});
on('device-camera-stop','click',()=>{stopCamera();status('Cámara detenida');});
on('device-mic-start','click',async()=>{try{if(recorder?.state==='recording')return;if(typeof MediaRecorder==='undefined')throw Error('MediaRecorder no está disponible.');mic=await JocarsaDevice.abrirMicrofono();chunks=[];recorder=new MediaRecorder(mic);recorder.ondataavailable=e=>{if(e.data.size)chunks.push(e.data);};recorder.onstop=()=>{const blob=new Blob(chunks,{type:recorder.mimeType||'audio/webm'});revoke(audioURL);audioURL=URL.createObjectURL(blob);$('device-mic-audio').src=audioURL;$('device-mic-audio').hidden=false;const link=$('device-mic-download');link.href=audioURL;link.download='jocarsa-audio.'+(blob.type.includes('mp4')?'mp4':blob.type.includes('ogg')?'ogg':'webm');link.hidden=false;JocarsaDevice.detener(mic);mic=null;$('device-mic-state').textContent='Grabación terminada';};recorder.start();$('device-mic-state').textContent='Grabando…';status('Micrófono activo');}catch(e){JocarsaDevice.detener(mic);mic=null;error(e);}});
on('device-mic-stop','click',()=>{stopMic();status('Grabación detenida');});
on('device-location','click',async()=>{try{$('device-location-result').textContent='Solicitando permiso…';const p=await JocarsaDevice.ubicacion();$('device-location-result').textContent=JSON.stringify({latitud:p.coords.latitude,longitud:p.coords.longitude,precision_m:p.coords.accuracy,fecha:new Date(p.timestamp).toLocaleString()},null,2);status('Ubicación recibida; no se envía al servidor.');}catch(e){$('device-location-result').textContent=e.message;error(e);}});
on('device-file','change',async e=>{try{const f=e.target.files?.[0];if(!f)return;const result={nombre:f.name,tipo:f.type||'desconocido',bytes:f.size,modificado:new Date(f.lastModified).toLocaleString()};if(f.size<=1024*1024 && (/^text\//.test(f.type)||/\.(txt|md|json|csv|html|js|css)$/i.test(f.name)))result.vistaPrevia=(await JocarsaDevice.leerArchivo(f)).slice(0,1500);$('device-file-result').textContent=JSON.stringify(result,null,2);status('Archivo leído localmente');}catch(e){error(e);}});
on('device-permissions','click',async()=>{const r={};for(const key of ['camera','microphone','geolocation'])r[key]=await JocarsaDevice.permisos(key);$('device-permissions-result').textContent=JSON.stringify(r,null,2);});
on('device-share','click',async()=>{try{await JocarsaDevice.compartir({title:document.title,url:location.href});status('Compartir finalizado');}catch(e){if(e.name!=='AbortError')error(e);}});
on('device-copy','click',async()=>{try{await JocarsaDevice.copiar(location.href);status('Enlace copiado');}catch(e){error(e);}});
let sensorStops=[],wakeLock=null,activeWake=false,lastOrientation=null,lastMotion=null;
const sensorResult=$('device-sensors-result');
const renderSensors=()=>{sensorResult.textContent=JSON.stringify({orientacion:lastOrientation,movimiento:lastMotion},null,2);};
function stopSensors(){sensorStops.forEach(fn=>fn());sensorStops=[];sensorResult.textContent='Sensores detenidos';}
on('device-sensors-start','click',async()=>{try{stopSensors();await JocarsaDevice.solicitarSensores();sensorStops=[JocarsaDevice.observarOrientacion(v=>{lastOrientation=v;renderSensors();}),JocarsaDevice.observarMovimiento(v=>{lastMotion=v;renderSensors();})];status('Sensores activados');}catch(e){stopSensors();error(e);}});
on('device-sensors-stop','click',()=>{stopSensors();status('Sensores detenidos');});
on('device-vibrate-short','click',()=>{try{JocarsaDevice.vibrar(150);}catch(e){error(e);}});
on('device-vibrate-pattern','click',()=>{try{JocarsaDevice.vibrar([120,90,120,90,250]);}catch(e){error(e);}});
on('device-vibrate-stop','click',()=>{try{JocarsaDevice.vibrar(0);}catch(e){error(e);}});
on('device-fullscreen-start','click',async()=>{try{await JocarsaDevice.pantallaCompleta();status('Pantalla completa activada');}catch(e){error(e);}});
on('device-fullscreen-stop','click',async()=>{try{await JocarsaDevice.salirPantallaCompleta();status('Pantalla completa desactivada');}catch(e){error(e);}});
async function stopWake(){activeWake=false;if(wakeLock){const lock=wakeLock;wakeLock=null;await lock.release().catch(()=>{});}$('device-wake-status').textContent='Desactivado';}
async function startWake(){if(!activeWake||document.visibilityState!=='visible'||wakeLock)return;try{const lock=await JocarsaDevice.mantenerPantallaActiva();if(!activeWake){await lock.release();return;}wakeLock=lock;$('device-wake-status').textContent='Pantalla activa';lock.addEventListener('release',()=>{if(wakeLock===lock){wakeLock=null;$('device-wake-status').textContent='Bloqueo liberado';}});}catch(e){$('device-wake-status').textContent=e.message;}}
on('device-wake-start','click',async()=>{activeWake=true;await startWake();});
on('device-wake-stop','click',()=>{stopWake();});
const visibilityHandler=()=>{if(document.visibilityState==='visible')startWake();};document.addEventListener('visibilitychange',visibilityHandler);
return ()=>{stopSensors();activeWake=false;stopWake();document.removeEventListener('visibilitychange',visibilityHandler);listeners.forEach(fn=>fn());stopCamera();if(recorder){recorder.onstop=null;if(recorder.state!=='inactive')recorder.stop();}JocarsaDevice.detener(mic);revoke(photoURL);revoke(audioURL);};
