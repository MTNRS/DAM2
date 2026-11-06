(()=>{
 const track=document.getElementById('client-track');if(!track)return;
 const safeURL=(value)=>{try{const u=new URL(String(value||''),location.href);return ['https:','http:'].includes(u.protocol)?u.href:null}catch{return null}};
 const rows=(payload)=>Array.isArray(payload)?payload:(payload?.data??payload?.items??payload?.datos??payload?.registros??[]);
 (async()=>{try{
  const payload=await window.JOCARSA_API.get('clientesweb');const items=rows(payload);
  if(!Array.isArray(items))throw Error('Formato de clientes no válido');
  const fragment=document.createDocumentFragment();
  for(const item of items){
   if(!item||typeof item!=='object')continue;
   const name=String(item.nombre??item.name??item.razon_social??'').trim();if(!name)continue;
   const logo=safeURL(item.logo??item.logo_url??item.url_logo??'');
   const web=safeURL(item.web??item.url_web??item.web_url??item.website??item.url??'');
   const tile=document.createElement(web?'a':'div');tile.className='brand-tile client-tile';
   if(web){tile.href=web;tile.target='_blank';tile.rel='noopener noreferrer';tile.title='Visitar '+name;}
   if(logo){const img=document.createElement('img');img.src=logo;img.alt='Logotipo de '+name;img.loading='lazy';img.decoding='async';img.onerror=()=>img.remove();tile.append(img);}
   const label=document.createElement('span');label.textContent=name;tile.append(label);fragment.append(tile);
  }
  track.replaceChildren();if(fragment.childNodes.length)track.append(fragment);
  else track.textContent='No hay clientes disponibles.';
 }catch(err){console.error('Clientes web:',err);track.textContent='No se pudieron cargar las marcas de clientes.';}})();
})();