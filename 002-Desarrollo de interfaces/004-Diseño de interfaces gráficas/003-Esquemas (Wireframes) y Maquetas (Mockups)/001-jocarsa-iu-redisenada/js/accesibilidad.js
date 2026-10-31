(()=>{
 const key='jocarsa-ui-high-contrast';
 const textKey='jocarsa-ui-text-scale';
 const init=()=>{
  const header=document.querySelector('body.jocarsa-iu > header');if(!header)return;
  const wrap=document.createElement('div');wrap.className='ju-accessibility';
  wrap.innerHTML=`<button type="button" class="ju-a11y-button" aria-label="Accesibilidad" aria-expanded="false" aria-controls="ju-a11y-panel">Accesibilidad</button><div class="ju-a11y-panel" id="ju-a11y-panel" hidden><strong>Accesibilidad</strong><label class="ju-a11y-option"><span>Alto contraste</span><input type="checkbox" role="switch" aria-label="Activar alto contraste"></label><div class="ju-a11y-zoom"><label for="ju-a11y-text-range">Tamaño del contenido <output id="ju-a11y-text-value">100 %</output></label><input id="ju-a11y-text-range" type="range" min="100" max="200" step="10" value="100" aria-label="Ampliación del contenido de 100 a 200 por ciento"><button type="button" class="ju-a11y-reset">Restablecer tamaño</button></div></div>`;
  header.append(wrap);const btn=wrap.querySelector('button'),panel=wrap.querySelector('.ju-a11y-panel'),sw=wrap.querySelector('input');
  const apply=()=>{document.documentElement.classList.toggle('ju-high-contrast',sw.checked);try{localStorage.setItem(key,sw.checked?'1':'0')}catch{}};
  try{sw.checked=localStorage.getItem(key)==='1'}catch{}apply();sw.addEventListener('change',apply);
  const range=wrap.querySelector('#ju-a11y-text-range'),out=wrap.querySelector('#ju-a11y-text-value');
  const updateReflow=()=>{const scale=Number(document.documentElement.style.getPropertyValue('--ju-content-scale'))||1;document.documentElement.classList.toggle('ju-a11y-reflow',window.innerWidth/scale<=700)};
  window.addEventListener('resize',updateReflow,{passive:true});
  const setScale=(value,persist=true)=>{
   const n=Math.max(100,Math.min(200,Number(value)||100));range.value=String(n);out.textContent=n+' %';
   document.documentElement.style.setProperty('--ju-content-scale',String(n/100));
   document.documentElement.classList.toggle('ju-text-enlarged',n>100);updateReflow();document.dispatchEvent(new Event('ju-a11y-scale-change'));
   if(persist)try{localStorage.setItem(textKey,String(n))}catch{}
  };
  let stored=100;try{stored=Number(localStorage.getItem(textKey))||100}catch{}setScale(stored,false);
  range.addEventListener('input',()=>setScale(range.value));
  wrap.querySelector('.ju-a11y-reset').addEventListener('click',()=>setScale(100));
  const close=()=>{panel.hidden=true;btn.setAttribute('aria-expanded','false')};
  btn.addEventListener('click',()=>{panel.hidden=!panel.hidden;btn.setAttribute('aria-expanded',String(!panel.hidden))});
  document.addEventListener('pointerdown',e=>{if(!wrap.contains(e.target))close()});
  document.addEventListener('keydown',e=>{if(e.key==='Escape')close()});
 };
 if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',init);else init();
})();
