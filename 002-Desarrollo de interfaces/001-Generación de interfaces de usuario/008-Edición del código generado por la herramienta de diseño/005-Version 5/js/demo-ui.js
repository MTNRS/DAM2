function juToast(tipo='info',titulo='Información',texto='Mensaje de ejemplo'){
 const stack=document.getElementById('toastStack'); if(!stack)return;
 const iconos={success:'✓',info:'i',warning:'!',danger:'×'};
 const t=document.createElement('article');t.className=`ju-toast is-${tipo}`;
 t.innerHTML=`<div class="ju-toast-icon">${iconos[tipo]||'i'}</div><div><p class="ju-toast-title">${titulo}</p><p class="ju-toast-text">${texto}</p></div><button class="ju-toast-close" type="button" aria-label="Cerrar">×</button>`;
 const cerrar=()=>{t.classList.add('is-leaving');setTimeout(()=>t.remove(),240)};
 t.querySelector('.ju-toast-close').addEventListener('click',cerrar);stack.appendChild(t);setTimeout(cerrar,5000);
}
document.addEventListener('click',e=>{const b=e.target.closest('[data-toast]');if(!b)return;const tipo=b.dataset.toast;const textos={success:['Guardado correctamente','La operación se ha completado.'],info:['Información','Este es un mensaje informativo.'],warning:['Revisión necesaria','Conviene revisar los datos antes de continuar.'],danger:['Operación fallida','No se ha podido completar la operación.']};juToast(tipo,...textos[tipo])});
document.getElementById('formDemo')?.addEventListener('submit',e=>{e.preventDefault();juToast('success','Formulario guardado','El evento submit se ha capturado correctamente.')});
