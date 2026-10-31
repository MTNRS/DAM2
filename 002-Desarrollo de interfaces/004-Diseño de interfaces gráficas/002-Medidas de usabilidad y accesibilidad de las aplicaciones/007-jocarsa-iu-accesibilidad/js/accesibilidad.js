(()=>{
 const key='jocarsa-ui-high-contrast';
 const init=()=>{
  const header=document.querySelector('body.jocarsa-iu > header');if(!header)return;
  const wrap=document.createElement('div');wrap.className='ju-accessibility';
  wrap.innerHTML=`<button type="button" class="ju-a11y-button" aria-label="Accesibilidad" aria-expanded="false" aria-controls="ju-a11y-panel">♿ Accesibilidad</button><div class="ju-a11y-panel" id="ju-a11y-panel" hidden><strong>Accesibilidad</strong><label class="ju-a11y-option"><span>Alto contraste</span><input type="checkbox" role="switch" aria-label="Activar alto contraste"></label></div>`;
  header.append(wrap);const btn=wrap.querySelector('button'),panel=wrap.querySelector('.ju-a11y-panel'),sw=wrap.querySelector('input');
  const apply=()=>{document.documentElement.classList.toggle('ju-high-contrast',sw.checked);try{localStorage.setItem(key,sw.checked?'1':'0')}catch{}};
  try{sw.checked=localStorage.getItem(key)==='1'}catch{}apply();sw.addEventListener('change',apply);
  const close=()=>{panel.hidden=true;btn.setAttribute('aria-expanded','false')};
  btn.addEventListener('click',()=>{panel.hidden=!panel.hidden;btn.setAttribute('aria-expanded',String(!panel.hidden))});
  document.addEventListener('pointerdown',e=>{if(!wrap.contains(e.target))close()});
  document.addEventListener('keydown',e=>{if(e.key==='Escape')close()});
 };
 if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',init);else init();
})();
