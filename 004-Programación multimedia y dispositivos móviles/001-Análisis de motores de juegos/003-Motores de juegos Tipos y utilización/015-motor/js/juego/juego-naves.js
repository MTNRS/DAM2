const canvas=document.querySelector("#escenario"), motor=new Motor(canvas), escena=new Escena();
const uiObjetivos=document.querySelector("#objetivos"),uiEnergia=document.querySelector("#energia"),uiMensaje=document.querySelector("#mensaje");

// Comportamientos reutilizables: equivalentes sencillos a componentes/configuración de un motor.
const limitarPantalla=(a,dt,e)=>{ a.x=Math.max(a.radio,Math.min(e.motor.canvas.width-a.radio,a.x)); a.y=Math.max(a.radio,Math.min(e.motor.canvas.height-a.radio,a.y)); };
const destruirFuera=(a,dt,e)=>{ if(a.x<-100||a.x>e.motor.canvas.width+100||a.y<-100||a.y>e.motor.canvas.height+100)a.destruir(e); };
const deriva=(a,dt,e)=>{ a.angulo+=(Math.random()-.5)*.8*dt; a.x+=Math.cos(a.angulo)*a.velocidad*dt; a.y+=Math.sin(a.angulo)*a.velocidad*dt; if(a.x<0||a.x>e.motor.canvas.width)a.angulo=Math.PI-a.angulo; if(a.y<0||a.y>e.motor.canvas.height)a.angulo=-a.angulo; };

function estrella(){ return new Actor({x:Math.random()*innerWidth,y:Math.random()*innerHeight,radio:Math.random()*1.5+.4,etiquetas:["decorado"],alDibujar:(a,c)=>{c.fillStyle="white";c.globalAlpha=.35+a.radio/3;c.beginPath();c.arc(a.x,a.y,a.radio,0,Math.PI*2);c.fill();c.globalAlpha=1;}}); }
function roca(){ const a=new Actor({x:Math.random()*innerWidth,y:Math.random()*innerHeight,angulo:Math.random()*Math.PI*2,velocidad:35+Math.random()*55,radio:24,etiquetas:["enemigo","objetivo"],alDibujar:(a,c)=>{c.save();c.translate(a.x,a.y);c.rotate(a.angulo);c.strokeStyle="#bbb";c.lineWidth=3;c.fillStyle="#343944";c.beginPath();for(let i=0;i<9;i++){const an=i/9*Math.PI*2,r=a.radio*(.72+Math.random()*.22);const x=Math.cos(an)*r,y=Math.sin(an)*r;i?c.lineTo(x,y):c.moveTo(x,y)}c.closePath();c.fill();c.stroke();c.restore();},alColisionar:(a,o,e)=>{if(o.tiene("proyectil")){a.destruir(e);o.destruir(e);}}}); return a.agregarComportamiento(deriva); }
function proyectil(x,y){ const a=new Actor({x,y,vx:0,vy:-520,radio:5,etiquetas:["proyectil"],alDibujar:(a,c)=>{c.fillStyle="#7cf7ff";c.fillRect(a.x-2,a.y-10,4,20);}});return a.agregarComportamiento(destruirFuera); }

let ultimoDisparo=0;
const jugador=new Jugador({x:innerWidth/2,y:innerHeight-90,radio:22,vida:100,etiquetas:["jugador"],
  controles:{arriba:["ArrowUp","KeyW"],abajo:["ArrowDown","KeyS"],izquierda:["ArrowLeft","KeyA"],derecha:["ArrowRight","KeyD"],disparar:"Space"},
  acciones:{arriba:(a,dt)=>a.y-=260*dt,abajo:(a,dt)=>a.y+=260*dt,izquierda:(a,dt)=>a.x-=260*dt,derecha:(a,dt)=>a.x+=260*dt,disparar:(a,dt,e)=>{const ahora=performance.now();if(ahora-ultimoDisparo>180){e.agregar(proyectil(a.x,a.y-28));ultimoDisparo=ahora;}}},
  alDibujar:(a,c)=>{c.save();c.translate(a.x,a.y);c.fillStyle="#61d7ff";c.strokeStyle="white";c.lineWidth=2;c.beginPath();c.moveTo(0,-28);c.lineTo(20,22);c.lineTo(0,14);c.lineTo(-20,22);c.closePath();c.fill();c.stroke();c.restore();},
  alColisionar:(a,o)=>{if(o.tiene("enemigo")){a.vida-=18; o.angulo+=Math.PI;}}
}); jugador.agregarComportamiento(limitarPantalla);

escena.alIniciar=e=>{for(let i=0;i<180;i++)e.agregar(estrella());for(let i=0;i<8;i++)e.agregar(roca());e.agregar(jugador);};
escena.alActualizar=e=>{const objetivos=e.buscar("objetivo").length;uiObjetivos.textContent=`Rocas: ${objetivos}`;uiEnergia.textContent=`Energía: ${Math.max(0,jugador.vida)}`;if(objetivos===0){uiMensaje.textContent="HAS GANADO";motor.detener();}else if(jugador.vida<=0){uiMensaje.textContent="HAS PERDIDO";motor.detener();}};
motor.cargar(escena);motor.iniciar();
