/* =========================================================
   Prompt IA flotante · Ollama local · v2
   ========================================================= */
(() => {
  const iniciarPromptIA = () => {
    const prompt = document.querySelector('#juAiPrompt');
    const drag = document.querySelector('#juAiDrag');
    const input = document.querySelector('#juAiInput');
    const mic = document.querySelector('#juAiMic');
    const send = document.querySelector('#juAiSend');
    const status = document.querySelector('#juAiStatus');
    const escenario = document.querySelector('#escenario');
    const minimizar = document.querySelector('#juAiMinimize');
    const resizer = document.querySelector('#juAiResizer');
    const speakToggle = document.querySelector('#juAiSpeak');
    if (!prompt || !drag || !input || !mic || !send || !status || !escenario || !minimizar || !resizer || !speakToggle) return;

    let enviando=false, reconocimiento=null, escuchando=false;
    const escapeHTML = t => String(t).replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    const inline = t => escapeHTML(t)
      .replace(/`([^`]+)`/g,'<code>$1</code>')
      .replace(/\*\*([^*]+)\*\*/g,'<strong>$1</strong>')
      .replace(/\*([^*]+)\*/g,'<em>$1</em>');

    // Markdown ligero, incluyendo tablas, sin dependencias externas.
    const markdown = md => {
      md=String(md||'').replace(/\r/g,'').replace(/\u00a0/g,' ').replace(/[\u200B-\u200D\uFEFF]/g,'').trim();
      // Los LLM envuelven a veces TODO el Markdown en ```markdown ... ```.
      // Lo desenvolvemos antes de proteger los bloques de código reales.
      md=md.replace(/```(?:markdown|md)\s*\n([\s\S]*?)```/gi,(_,body)=>body.trim());
      const normalizeFenceTable = body => String(body||'')
        .replace(/[｜¦│]/g,'|').replace(/[‐‑‒–—−]/g,'-')
        .replace(/\u00a0/g,' ').replace(/[\u200B-\u200D\uFEFF]/g,'').trim();
      const fenceLooksLikeTable = body => {
        const ls=normalizeFenceTable(body).split('\n').filter(x=>x.trim());
        if(ls.length<2 || !ls[0].includes('|')) return false;
        const cells=x=>x.trim().replace(/^\s*\|/,'').replace(/\|\s*$/,'').split('|').map(v=>v.trim());
        const sep=cells(ls[1]);
        return cells(ls[0]).length>=2 && sep.length>=2 && sep.every(v=>/^:?-{2,}:?$/.test(v.replace(/\s+/g,'')));
      };
      // Algunos modelos meten una tabla Markdown dentro de un fence ``` sin lenguaje.
      // Si el contenido tiene estructura de tabla, lo sacamos del fence; el código real se conserva.
      md=md.replace(/```([^\n]*)\n([\s\S]*?)```/g,(all,lang,body)=>{
        if((!lang.trim() || /^(text|txt)$/i.test(lang.trim())) && fenceLooksLikeTable(body)) return normalizeFenceTable(body);
        return all;
      });
      const code=[];
      md=md.replace(/```([^\n]*)\n([\s\S]*?)```/g,(_,lang,body)=>`@@CODE${code.push({lang,body})-1}@@`);
      const lines=md.split('\n'), out=[];
      for(let i=0;i<lines.length;){
        const line=lines[i];
        // Tabla Markdown GFM. No exigimos que las filas terminen en |, porque
        // los LLM suelen omitir esa barra final. La segunda fila debe estar
        // formada exclusivamente por separadores --- / :--- / ---: / :---:.
        const normalizaTabla = x => String(x||'')
          .replace(/[｜¦│]/g,'|')
          .replace(/[‐‑‒–—−]/g,'-')
          .replace(/\u00a0/g,' ')
          .replace(/[\u200B-\u200D\uFEFF]/g,'');
        const tableCells = x => normalizaTabla(x).trim().replace(/^\s*\|/, '').replace(/\|\s*$/, '').split('|').map(v=>v.trim());
        const isTableRow = x => normalizaTabla(x).includes('|') && tableCells(x).length >= 2;
        const isTableSeparator = x => {
          if(!isTableRow(x)) return false;
          const c=tableCells(x);
          return c.length>=2 && c.every(v=>/^:?-+:?$/.test(v.replace(/\s+/g,'')));
        };
        if(isTableRow(line) && i+1<lines.length && isTableSeparator(lines[i+1])){
          const heads=tableCells(line); i+=2; const rows=[];
          while(i<lines.length && lines[i].trim() && isTableRow(lines[i]) && !isTableSeparator(lines[i])) rows.push(tableCells(lines[i++]));
          out.push('<div class="ju-ai-table-wrap"><table><thead><tr>'+heads.map(x=>`<th>${inline(x)}</th>`).join('')+'</tr></thead><tbody>'+rows.map(r=>'<tr>'+heads.map((_,j)=>`<td>${inline(r[j]||'')}</td>`).join('')+'</tr>').join('')+'</tbody></table></div>'); continue;
        }
        let m;
        if((m=line.match(/^###\s+(.+)/))) out.push(`<h3>${inline(m[1])}</h3>`);
        else if((m=line.match(/^##\s+(.+)/))) out.push(`<h2>${inline(m[1])}</h2>`);
        else if((m=line.match(/^#\s+(.+)/))) out.push(`<h1>${inline(m[1])}</h1>`);
        else if((m=line.match(/^>\s?(.+)/))) out.push(`<blockquote>${inline(m[1])}</blockquote>`);
        else if(/^\s*[-*]\s+/.test(line)){
          let items=[]; while(i<lines.length && /^\s*[-*]\s+/.test(lines[i])) items.push(lines[i++].replace(/^\s*[-*]\s+/,''));
          out.push('<ul>'+items.map(x=>`<li>${inline(x)}</li>`).join('')+'</ul>'); continue;
        } else if(/^\s*\d+\.\s+/.test(line)){
          let items=[]; while(i<lines.length && /^\s*\d+\.\s+/.test(lines[i])) items.push(lines[i++].replace(/^\s*\d+\.\s+/,''));
          out.push('<ol>'+items.map(x=>`<li>${inline(x)}</li>`).join('')+'</ol>'); continue;
        } else if(/^@@CODE\d+@@$/.test(line.trim())){
          const n=+line.match(/\d+/)[0], c=code[n]; out.push(`<pre><code>${escapeHTML(c.body)}</code></pre>`);
        } else if(line.trim()) out.push(`<p>${inline(line)}</p>`);
        i++;
      }
      return out.join('\n');
    };

    const autoResize=()=>{input.style.height='auto';input.style.height=Math.min(input.scrollHeight,150)+'px'};
    const fijar=(left,top)=>{const r=prompt.getBoundingClientRect(),m=8;return {left:Math.max(m,Math.min(left,window.innerWidth-r.width-m)),top:Math.max(62,Math.min(top,window.innerHeight-r.height-m))}};
    const convertirPosicion=()=>{const r=prompt.getBoundingClientRect();prompt.style.transform='none';prompt.style.bottom='auto';prompt.style.left=r.left+'px';prompt.style.top=r.top+'px'};

    drag.addEventListener('pointerdown',e=>{
      if(e.button!==0 || e.target.closest('button')) return;
      convertirPosicion(); const r=prompt.getBoundingClientRect(), ox=e.clientX-r.left, oy=e.clientY-r.top;
      prompt.classList.add('is-dragging'); drag.setPointerCapture(e.pointerId);
      const mover=ev=>{const p=fijar(ev.clientX-ox,ev.clientY-oy);prompt.style.left=p.left+'px';prompt.style.top=p.top+'px'};
      const soltar=ev=>{prompt.classList.remove('is-dragging');try{drag.releasePointerCapture(ev.pointerId)}catch{};drag.removeEventListener('pointermove',mover);drag.removeEventListener('pointerup',soltar);drag.removeEventListener('pointercancel',soltar)};
      drag.addEventListener('pointermove',mover);drag.addEventListener('pointerup',soltar);drag.addEventListener('pointercancel',soltar);
    });

    minimizar.addEventListener('click',e=>{
      e.stopPropagation(); convertirPosicion(); prompt.classList.toggle('is-minimized');
      minimizar.textContent=prompt.classList.contains('is-minimized')?'□':'—'; minimizar.title=prompt.classList.contains('is-minimized')?'Restaurar':'Minimizar';
      requestAnimationFrame(()=>{const r=prompt.getBoundingClientRect(),p=fijar(r.left,r.top);prompt.style.left=p.left+'px';prompt.style.top=p.top+'px'});
    });

    resizer.addEventListener('pointerdown',e=>{
      e.preventDefault();e.stopPropagation();convertirPosicion();
      const r=prompt.getBoundingClientRect(), sx=e.clientX,sy=e.clientY,sw=r.width,sh=r.height;
      resizer.setPointerCapture(e.pointerId);
      const mover=ev=>{prompt.style.width=Math.max(360,Math.min(sw+ev.clientX-sx,window.innerWidth-r.left-8))+'px';prompt.style.height=Math.max(82,Math.min(sh+ev.clientY-sy,window.innerHeight-r.top-8))+'px'};
      const fin=ev=>{try{resizer.releasePointerCapture(ev.pointerId)}catch{};resizer.removeEventListener('pointermove',mover);resizer.removeEventListener('pointerup',fin);resizer.removeEventListener('pointercancel',fin)};
      resizer.addEventListener('pointermove',mover);resizer.addEventListener('pointerup',fin);resizer.addEventListener('pointercancel',fin);
    });

    window.addEventListener('resize',()=>{if(!prompt.style.top)return;const r=prompt.getBoundingClientRect(),p=fijar(r.left,r.top);prompt.style.left=p.left+'px';prompt.style.top=p.top+'px'});

    const enviar=async()=>{
      const texto=input.value.trim(); if(!texto||enviando)return; enviando=true;send.disabled=true;mic.disabled=true;status.textContent='Ollama está generando el nuevo contenido del escenario…';
      try{
        const respuesta=await fetch('ollama.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({mensaje:texto})});
        if(!respuesta.ok)throw new Error(`HTTP ${respuesta.status}`); const datos=await respuesta.json(); const contenido=datos.respuesta??'No he podido responder.';
        escenario.innerHTML='<article class="ju-ai-result">'+markdown(contenido)+'</article>';
        if(speakToggle.checked && 'speechSynthesis' in window){
          speechSynthesis.cancel();
          const voz=new SpeechSynthesisUtterance(String(contenido).replace(/```[\s\S]*?```/g,' ').replace(/\|/g,' ').replace(/[#*_>`~-]/g,' '));
          voz.lang='es-ES'; speechSynthesis.speak(voz);
        }
        status.textContent='Escenario actualizado.';input.value='';autoResize();setTimeout(()=>{if(status.textContent==='Escenario actualizado.')status.textContent=''},2200);
      }catch(error){console.error(error);status.textContent='No se ha podido conectar con Ollama.'}
      finally{enviando=false;send.disabled=false;mic.disabled=false;input.focus()}
    };
    send.addEventListener('click',enviar);input.addEventListener('input',autoResize);input.addEventListener('keydown',e=>{if(e.key==='Enter'&&!e.shiftKey){e.preventDefault();enviar()}});
    const SpeechRecognition=window.SpeechRecognition||window.webkitSpeechRecognition;
    if(!SpeechRecognition){mic.disabled=true;mic.title='Reconocimiento de voz no disponible en este navegador'}else mic.addEventListener('click',()=>{
      if(escuchando&&reconocimiento){reconocimiento.stop();return} reconocimiento=new SpeechRecognition();reconocimiento.lang='es-ES';reconocimiento.interimResults=false;reconocimiento.continuous=false;
      reconocimiento.onstart=()=>{escuchando=true;mic.classList.add('is-listening');status.textContent='Escuchando…'};
      reconocimiento.onresult=evento=>{const texto=evento.results[0][0].transcript;input.value=input.value.trim()?input.value.trim()+' '+texto:texto;autoResize();input.focus()};
      reconocimiento.onerror=evento=>{console.error('Error de reconocimiento:',evento.error);status.textContent='No se ha podido reconocer la voz.'};
      reconocimiento.onend=()=>{escuchando=false;mic.classList.remove('is-listening');if(status.textContent==='Escuchando…')status.textContent=''};reconocimiento.start();
    });
    autoResize();
  };
  if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',iniciarPromptIA);else iniciarPromptIA();
})();
