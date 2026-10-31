// Configuración por endpoint: cada ruta tiene su propia clave.
window.JOCARSA_API = {
 async get(nombre){
  const config=window.JOCARSA_ENDPOINTS[nombre];
  if(!config) throw new Error('Endpoint desconocido: '+nombre);
  const headers={'Accept':'application/json'};
  if(config.apiKey) headers['X-API-Key']=config.apiKey;
  const respuesta=await fetch(config.url,{headers,cache:'no-cache'});
  if(!respuesta.ok) throw new Error(nombre+': HTTP '+respuesta.status);
  return respuesta.json();
 }
};
