const JocarsaToast=(()=>{
 let host;const iconos={success:'✓',error:'!',warning:'⚠',info:'i'};
 function mostrar(mensaje,{type='info',duration=4000,action=null}={}){
  if(!host){host=document.createElement('div');host.className='jocarsa-toast-host';host.setAttribute('aria-live','polite');document.body.append(host);}
  const item=document.createElement('div');item.className='jocarsa-toast '+(iconos[type]?type:'info');item.setAttribute('role',type==='error'?'alert':'status');
  const icon=document.createElement('strong');icon.textContent=iconos[type]||'i';const text=document.createElement('span');text.textContent=String(mensaje);const close=document.createElement('button');close.type='button';close.textContent='×';close.setAttribute('aria-label','Cerrar notificación');
  let timer;const cerrar=()=>{clearTimeout(timer);item.remove();};close.onclick=cerrar;item.append(icon,text);
  if(action?.label&&typeof action.onClick==='function'){const a=document.createElement('button');a.type='button';a.textContent=action.label;a.onclick=()=>{action.onClick();cerrar();};item.append(a);}
  item.append(close);host.append(item);if(duration>0)timer=setTimeout(cerrar,duration);return cerrar;
 }
 return {mostrar,success:(m,o)=>mostrar(m,{...o,type:'success'}),error:(m,o)=>mostrar(m,{...o,type:'error'}),warning:(m,o)=>mostrar(m,{...o,type:'warning'}),info:(m,o)=>mostrar(m,{...o,type:'info'})};
})();
