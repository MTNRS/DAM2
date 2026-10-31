/* Cliente HTTP reutilizable jocarsa | móvil */
const JocarsaAPI = (() => {
  let baseURL = '', tokenProvider = null;
  const configurar = ({base='', token=null}={}) => {baseURL=base;tokenProvider=token;};
  class APIError extends Error {constructor(message,{status=0,data=null,url='',cause=null}={}){super(message);this.name='APIError';Object.assign(this,{status,data,url,cause});}}
  const dormir = (ms,signal) => new Promise((resolve,reject)=>{const id=setTimeout(()=>{signal?.removeEventListener('abort',abortar);resolve();},ms);function abortar(){clearTimeout(id);reject(signal.reason||new DOMException('Cancelado','AbortError'));}if(signal?.aborted)abortar();else signal?.addEventListener('abort',abortar,{once:true});});
  async function solicitar(ruta,{method='GET',params,body,headers={},timeout=12000,retries=1,signal,responseType='auto',credentials='same-origin',retryUnsafe=false,...extra}={}){
    const url=new URL(ruta,/^https?:\/\//i.test(ruta)?undefined:(baseURL||location.href));
    if(params)Object.entries(params).forEach(([k,v])=>{if(v!=null)url.searchParams.set(k,String(v));});
    const verbo=method.toUpperCase();const h=new Headers(headers);h.set('Accept',responseType==='text'?'text/plain, */*':'application/json, text/plain, */*');
    if(tokenProvider){const token=typeof tokenProvider==='function'?await tokenProvider():tokenProvider;if(token)h.set('Authorization','Bearer '+token);}
    let payload=body;
    if(body!=null && !(body instanceof FormData) && !(body instanceof Blob) && !(body instanceof URLSearchParams) && typeof body!=='string') {payload=JSON.stringify(body);if(!h.has('Content-Type'))h.set('Content-Type','application/json');}
    for(let intento=0;intento<=retries;intento++){
      const controller=new AbortController();let expired=false;const timer=setTimeout(()=>{expired=true;controller.abort();},timeout);
      const cancel=()=>controller.abort();signal?.addEventListener('abort',cancel,{once:true});if(signal?.aborted)controller.abort();
      try{
        const res=await fetch(url,{method:verbo,headers:h,body:payload,signal:controller.signal,credentials,...extra});
        if(!res.ok){let data=null;try{data=await res.clone().json();}catch{data=await res.text().catch(()=>null);}
          const err=new APIError(`HTTP ${res.status}: ${res.statusText}`,{status:res.status,data,url:url.href});
          if(intento<retries&&(retryUnsafe||['GET','HEAD','OPTIONS'].includes(verbo))&&[408,429,500,502,503,504].includes(res.status)){const wait=Number(res.headers.get('Retry-After'));await dormir(Number.isFinite(wait)&&wait>0?Math.min(wait*1000,30000):400*2**intento,signal);continue;}throw err;
        }
        if(res.status===204)return null;
        if(responseType==='raw')return res;
        if(responseType==='blob')return res.blob();
        if(responseType==='text')return res.text();
        if(responseType==='json')return res.json();
        return (res.headers.get('content-type')||'').includes('json')?res.json():res.text();
      }catch(err){if(signal?.aborted)throw signal.reason||new DOMException('Cancelado','AbortError');if(expired)throw new APIError('Tiempo de espera agotado',{url:url.href,cause:err});
        if(err instanceof APIError)throw err;
        if(intento>=retries||(!retryUnsafe&&!['GET','HEAD','OPTIONS'].includes(verbo)))throw new APIError('Error de conexión o CORS',{url:url.href,cause:err});
        await dormir(400*2**intento,signal);
      }finally{clearTimeout(timer);signal?.removeEventListener('abort',cancel);}
    }
  }
  return {configurar,solicitar,get:(u,o)=>solicitar(u,{...o,method:'GET'}),post:(u,body,o)=>solicitar(u,{...o,method:'POST',body}),put:(u,body,o)=>solicitar(u,{...o,method:'PUT',body}),patch:(u,body,o)=>solicitar(u,{...o,method:'PATCH',body}),delete:(u,o)=>solicitar(u,{...o,method:'DELETE'}),APIError};
})();
