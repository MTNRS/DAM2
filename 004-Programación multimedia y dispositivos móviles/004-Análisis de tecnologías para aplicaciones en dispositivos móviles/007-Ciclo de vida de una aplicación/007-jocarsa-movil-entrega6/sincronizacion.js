/* Cola opt-in de escrituras JSON. No intercepta automáticamente el cliente API. */
window.JocarsaSync=(()=>{
 const S=window.JocarsaStorage;let trabajando=false;const listeners=new Set();
 function avisar(){window.dispatchEvent(new Event('jocarsa:sync'));}
 const id=()=>crypto.randomUUID?.()||('j-'+Date.now()+'-'+Math.random().toString(36).slice(2));
 function validar(ruta,method){const url=new URL(ruta,location.href);if(url.origin!==location.origin)throw new Error('Por seguridad, la cola solo acepta endpoints del mismo origen');if(!['POST','PUT','PATCH','DELETE'].includes(method))throw new Error('Método no permitido');return url.href;}
 async function encolar(ruta,{method='POST',body=null,headers={},idempotencyKey=null}={}){
  method=method.toUpperCase();const url=validar(ruta,method);if(body!==null){try{JSON.stringify(body);}catch{throw new Error('Los datos deben ser JSON serializable');}}
  if(Object.keys(headers).some(k=>/authorization|cookie/i.test(k)))throw new Error('No se almacenan credenciales en la cola');
  const key=idempotencyKey||id();const item={id:id(),url,method,body,headers:{...headers},idempotencyKey:key,estado:'pendiente',intentos:0,creado:Date.now(),ultimoError:'',proximoIntento:0};await S.ponerCola(item);avisar();if(navigator.onLine)void procesar();return item;
 }
 async function procesar({forzar=false}={}){
  if(trabajando||!navigator.onLine)return {procesados:0};trabajando=true;let procesados=0;
  try{const items=(await S.listarCola()).filter(x=>x.estado==='pendiente'&&(forzar||x.proximoIntento<=Date.now())).sort((a,b)=>a.creado-b.creado);
   for(const item of items){if(!navigator.onLine)break;
    try{const h=new Headers(item.headers);h.set('Idempotency-Key',item.idempotencyKey);if(item.body!==null)h.set('Content-Type','application/json');
     const r=await fetch(item.url,{method:item.method,headers:h,body:item.body===null?undefined:JSON.stringify(item.body),credentials:'same-origin',cache:'no-store'});
     if(!r.ok){const error=new Error('HTTP '+r.status);error.status=r.status;throw error;}
     await S.quitarCola(item.id);procesados++;
    }catch(e){item.intentos++;item.ultimoError=e.message||'Error de red';const status=e.status||0;
     if(status>=400&&status<500&&status!==408&&status!==429){item.estado='error';item.proximoIntento=0;}
     else item.proximoIntento=Date.now()+Math.min(60000*2**Math.min(item.intentos-1,6),3600000);
     await S.ponerCola(item);if(!status)break;
    }avisar();
   }
  }finally{trabajando=false;avisar();}return {procesados};
 }
 async function reintentar(id){const item=await S.leerCola(id);if(!item)return;item.estado='pendiente';item.proximoIntento=0;item.ultimoError='';await S.ponerCola(item);avisar();return procesar({forzar:true});}
 async function descartar(id){await S.quitarCola(id);avisar();}
 function iniciar(){window.addEventListener('online',()=>{avisar();void procesar();});document.addEventListener('visibilitychange',()=>{if(!document.hidden)void procesar();});setTimeout(()=>void procesar(),1200);}
 iniciar();return {encolar,procesar,reintentar,descartar,listar:()=>S.listarCola(),estado:()=>({online:navigator.onLine,trabajando})};
})();
