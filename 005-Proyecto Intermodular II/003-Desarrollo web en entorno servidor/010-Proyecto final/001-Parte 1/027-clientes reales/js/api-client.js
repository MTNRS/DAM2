window.JOCARSA_API = {
 async get(nombre) {
  const endpoint=window.JOCARSA_ENDPOINTS[nombre];
  if(!endpoint) throw new Error('JSON desconocido: '+nombre);
  const response=await fetch(endpoint.url,{headers:{Accept:'application/json'},cache:'no-store'});
  if(!response.ok) throw new Error(nombre+': HTTP '+response.status);
  return response.json();
 }
};