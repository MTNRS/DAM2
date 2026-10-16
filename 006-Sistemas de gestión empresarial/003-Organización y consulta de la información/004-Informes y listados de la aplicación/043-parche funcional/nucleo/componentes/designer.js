
(function(){
window.CrimsonDesigner = function(opts){
  const canvas=document.querySelector(opts.canvas);
  const arrows=document.querySelector(opts.arrows);
  const status=document.querySelector(opts.status);
  let model=JSON.parse(JSON.stringify(opts.model||{version:1,tables:[],relations:[]}));
  let fkMode=false, firstPort=null, selected=null;
  const viewport=canvas.parentElement;
  let zoom=1, panning=false, panX=0, panY=0, panLeft=0, panTop=0;
  canvas.style.transformOrigin="0 0";
  function applyZoom(){canvas.style.transform=`scale(${zoom})`;renderArrows();mark(`${model.tables.length} entidades · ${model.relations.length} FK · zoom ${Math.round(zoom*100)}%`);}
  viewport.addEventListener("mousedown",e=>{
    if(e.button!==1)return;
    e.preventDefault();panning=true;panX=e.clientX;panY=e.clientY;panLeft=viewport.scrollLeft;panTop=viewport.scrollTop;viewport.style.cursor="grabbing";
  });
  window.addEventListener("mousemove",e=>{if(!panning)return;viewport.scrollLeft=panLeft-(e.clientX-panX);viewport.scrollTop=panTop-(e.clientY-panY)});
  window.addEventListener("mouseup",e=>{if(e.button===1||panning){panning=false;viewport.style.cursor=""}});
  viewport.addEventListener("auxclick",e=>{if(e.button===1)e.preventDefault()});
  viewport.addEventListener("wheel",e=>{
    e.preventDefault();
    const rect=viewport.getBoundingClientRect();
    const mx=e.clientX-rect.left,my=e.clientY-rect.top;
    const worldX=(viewport.scrollLeft+mx)/zoom,worldY=(viewport.scrollTop+my)/zoom;
    const next=Math.max(.35,Math.min(2.25,zoom*(e.deltaY<0?1.1:.9)));
    zoom=next;applyZoom();
    viewport.scrollLeft=worldX*zoom-mx;viewport.scrollTop=worldY*zoom-my;
  },{passive:false});

  const esc=s=>String(s??"").replace(/[&<>"']/g,c=>({"&":"&amp;","<":"&lt;",">":"&gt;",'"':"&quot;","'":"&#39;"}[c]));
  const tableByName=n=>model.tables.find(t=>t.name===n);
  const columnExists=(t,c)=>!!tableByName(t)?.columns.some(x=>x.name===c);
  const mark=m=>{ if(status) status.textContent=m; };

  function colRow(t,c){
    const row=document.createElement("div");
    row.className="col"; row.dataset.col=c.name;
    row.innerHTML=`<span class="port left"></span>
      <input value="${esc(c.name)}" title="Nombre del campo">
      <select><option>INTEGER</option><option>TEXT</option><option>REAL</option><option>NUMERIC</option><option>BLOB</option></select>
      <label class="pk"><input type="checkbox" ${c.pk?"checked":""}>PK</label>
      <button class="del" title="Eliminar campo">×</button>
      <span class="port right"></span>`;
    row.querySelector("select").value=(c.type||"TEXT").toUpperCase().split("(")[0];
    const inp=row.querySelector('input:not([type="checkbox"])');
    inp.onchange=()=>{
      const old=c.name,n=inp.value.trim();
      if(!n || t.columns.some(x=>x!==c&&x.name===n)){ inp.value=old; return; }
      c.name=n;
      model.relations.forEach(r=>{
        if(r.fromTable===t.name&&r.fromColumn===old) r.fromColumn=n;
        if(r.toTable===t.name&&r.toColumn===old) r.toColumn=n;
      });
      renderArrows(); changed();
    };
    row.querySelector("select").onchange=e=>{c.type=e.target.value;changed()};
    row.querySelector('.pk input').onchange=e=>{c.pk=e.target.checked;changed()};
    row.querySelector(".del").onclick=()=>{
      model.relations=model.relations.filter(r=>!(r.fromTable===t.name&&r.fromColumn===c.name)&&!(r.toTable===t.name&&r.toColumn===c.name));
      t.columns=t.columns.filter(x=>x!==c); render(); changed();
    };
    row.querySelectorAll(".port").forEach(p=>p.onclick=ev=>{ev.stopPropagation();portClick(p,t,c)});
    return row;
  }

  function entityHTML(t){
    const e=document.createElement("div");
    e.className="entity"; e.style.left=t.x+"px";e.style.top=t.y+"px";e.dataset.table=t.name;
    e.innerHTML=`<div class="entity-title">${esc(t.name)}<button class="del-table" title="Eliminar entidad">×</button></div><div class="cols"></div><button class="addcol">+ campo</button>`;
    const cols=e.querySelector(".cols");
    t.columns.forEach(c=>cols.appendChild(colRow(t,c)));
    e.querySelector(".addcol").onclick=()=>{
      let n="campo",i=1; while(t.columns.some(c=>c.name===n)) n="campo_"+(++i);
      t.columns.push({name:n,type:"TEXT",pk:false});render();changed();
    };
    e.querySelector(".del-table").onclick=ev=>{
      ev.stopPropagation();
      if(!confirm("¿Eliminar entidad "+t.name+"?")) return;
      model.tables=model.tables.filter(x=>x!==t);
      model.relations=model.relations.filter(r=>r.fromTable!==t.name&&r.toTable!==t.name);
      render();changed();
    };
    e.querySelector(".entity-title").ondblclick=ev=>{
      if(ev.target.closest("button"))return;
      const n=prompt("Nombre de la entidad:",t.name);
      if(!n||n===t.name)return;
      if(model.tables.some(x=>x.name===n)){alert("Ya existe esa entidad");return}
      const old=t.name;t.name=n;
      model.relations.forEach(r=>{if(r.fromTable===old)r.fromTable=n;if(r.toTable===old)r.toTable=n});
      render();changed();
    };
    dragEntity(e,t);
    e.onclick=ev=>{if(ev.target.closest(".port,input,select,button,label"))return;selected={type:"table",table:t.name};highlight()};
    return e;
  }

  function dragEntity(el,t){
    const head=el.querySelector(".entity-title");let sx,sy,ox,oy,m=false;
    head.onmousedown=e=>{
      if(e.target.closest("button")||e.detail>1)return;
      m=true;sx=e.clientX;sy=e.clientY;ox=t.x;oy=t.y;document.body.style.userSelect="none";
    };
    window.addEventListener("mousemove",e=>{
      if(!m)return;t.x=Math.max(0,ox+(e.clientX-sx)/zoom);t.y=Math.max(0,oy+(e.clientY-sy)/zoom);
      el.style.left=t.x+"px";el.style.top=t.y+"px";renderArrows();
    });
    window.addEventListener("mouseup",()=>{if(m)changed();m=false;document.body.style.userSelect=""});
  }

  function portClick(p,t,c){
    if(!fkMode){selected={type:"column",table:t.name,column:c.name};highlight();return}
    if(!firstPort){firstPort={p,t,c};p.classList.add("pick");mark(`FK: ${t.name}.${c.name} → selecciona campo destino`);return}
    if(firstPort.t===t&&firstPort.c===c)return;
    model.relations.push({fromTable:firstPort.t.name,fromColumn:firstPort.c.name,toTable:t.name,toColumn:c.name});
    firstPort=null;fkMode=false;
    if(opts.fkButton) document.querySelector(opts.fkButton).textContent="→ Crear FK";
    render();changed();
  }

  function centerOf(table,col,side){
    const e=[...canvas.querySelectorAll(".entity")].find(x=>x.dataset.table===table);if(!e)return null;
    const row=[...e.querySelectorAll(".col")].find(x=>x.dataset.col===col);if(!row)return null;
    const p=row.querySelector(".port."+side),cr=canvas.getBoundingClientRect(),r=p.getBoundingClientRect();
    return{x:(r.left-cr.left+r.width/2)/zoom,y:(r.top-cr.top+r.height/2)/zoom};
  }

  function renderArrows(){
    arrows.querySelectorAll("line").forEach(x=>x.remove());
    model.relations=model.relations.filter(r=>tableByName(r.fromTable)&&tableByName(r.toTable)&&columnExists(r.fromTable,r.fromColumn)&&columnExists(r.toTable,r.toColumn));
    model.relations.forEach((r,i)=>{
      const a=centerOf(r.fromTable,r.fromColumn,"right"),b=centerOf(r.toTable,r.toColumn,"left");if(!a||!b)return;
      const l=document.createElementNS("http://www.w3.org/2000/svg","line");
      ["x1","y1","x2","y2"].forEach((k,j)=>l.setAttribute(k,[a.x,a.y,b.x,b.y][j]));
      l.dataset.i=i;l.style.pointerEvents="stroke";
      l.onclick=()=>{selected={type:"relation",index:i};highlight()};
      arrows.appendChild(l);
    });
  }
  function highlight(){
    canvas.querySelectorAll(".entity").forEach(e=>e.style.outline="");
    arrows.querySelectorAll("line").forEach(l=>l.classList.remove("sel"));
    if(selected?.type==="table"){
      const e=[...canvas.querySelectorAll(".entity")].find(x=>x.dataset.table===selected.table);
      if(e)e.style.outline="3px solid #dc143c55";
    }
    if(selected?.type==="relation") arrows.querySelector(`line[data-i="${selected.index}"]`)?.classList.add("sel");
  }
  function render(){
    canvas.querySelectorAll(".entity").forEach(e=>e.remove());
    model.tables.forEach(t=>canvas.appendChild(entityHTML(t)));
    renderArrows();highlight();mark(`${model.tables.length} entidades · ${model.relations.length} FK`);
  }
  function changed(){ if(opts.onChange) opts.onChange(model); mark(`${model.tables.length} entidades · ${model.relations.length} FK · cambios pendientes`); }

  if(opts.addButton)document.querySelector(opts.addButton).onclick=()=>{
    let n="nueva_entidad",i=1;while(tableByName(n))n="nueva_entidad_"+(++i);
    model.tables.push({name:n,x:260,y:120,columns:[{name:"Identificador",type:"INTEGER",pk:true}]});render();changed();
  };
  if(opts.clearButton)document.querySelector(opts.clearButton).onclick=()=>{
    if(!confirm("¿Vaciar completamente el diagrama? Se eliminarán todas las entidades y relaciones del diseño."))return;
    model.tables=[];model.relations=[];selected=null;firstPort=null;fkMode=false;render();changed();
  };
  if(opts.layoutButton)document.querySelector(opts.layoutButton).onclick=()=>{
    // Redistribución sencilla por niveles de dependencias FK.
    // Las tablas referenciadas quedan antes; las dependientes avanzan a la derecha.
    const names=model.tables.map(t=>t.name), level={};
    names.forEach(n=>level[n]=0);
    for(let pass=0;pass<names.length;pass++){
      let changedLevel=false;
      model.relations.forEach(r=>{
        if(r.fromTable===r.toTable)return;
        const wanted=Math.min(8,(level[r.toTable]||0)+1);
        if((level[r.fromTable]||0)<wanted){level[r.fromTable]=wanted;changedLevel=true}
      });
      if(!changedLevel)break;
    }
    const groups={};
    model.tables.forEach(t=>(groups[level[t.name]||0]??=[]).push(t));
    Object.keys(groups).sort((a,b)=>a-b).forEach(k=>{
      groups[k].sort((a,b)=>a.name.localeCompare(b.name,"es")).forEach((t,row)=>{
        t.x=50+Number(k)*300;
        t.y=50+row*190;
      });
    });
    render();changed();
    mark("Entidades redistribuidas según sus relaciones FK");
  };
  if(opts.fkButton)document.querySelector(opts.fkButton).onclick=e=>{
    fkMode=!fkMode;firstPort=null;e.currentTarget.textContent=fkMode?"Cancelar FK":"→ Crear FK";
    mark(fkMode?"Selecciona campo origen y campo destino":"Modo FK cancelado");
  };
  if(opts.deleteButton)document.querySelector(opts.deleteButton).onclick=()=>{
    if(selected?.type==="relation"){model.relations.splice(selected.index,1);selected=null;render();changed()}
    else if(selected?.type==="table"){
      const n=selected.table;model.tables=model.tables.filter(t=>t.name!==n);model.relations=model.relations.filter(r=>r.fromTable!==n&&r.toTable!==n);
      selected=null;render();changed();
    }
  };
  render();
  return {
    getModel:()=>JSON.parse(JSON.stringify(model)),
    setModel:nuevo=>{model=JSON.parse(JSON.stringify(nuevo||{version:1,tables:[],relations:[]}));selected=null;firstPort=null;fkMode=false;render();changed();},
    render
  };
};
})();
