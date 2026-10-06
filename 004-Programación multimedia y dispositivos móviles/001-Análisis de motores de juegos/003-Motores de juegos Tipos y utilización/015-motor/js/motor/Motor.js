class Motor {
  constructor(canvas,{fps=60}={}){
    this.canvas=canvas; this.ctx=canvas.getContext("2d"); this.fps=fps; this.entrada=new Entrada(); this.escena=null; this.ultimo=0; this.corriendo=false;
    this.redimensionar(); addEventListener("resize",()=>this.redimensionar());
  }
  redimensionar(){ this.canvas.width=innerWidth; this.canvas.height=innerHeight; }
  cargar(escena){ this.escena=escena; escena.motor=this; if(!escena.iniciada){escena.iniciada=true;escena.alIniciar?.(escena);} }
  iniciar(){ this.corriendo=true; requestAnimationFrame(t=>this.bucle(t)); }
  detener(){ this.corriendo=false; }
  bucle(t){ if(!this.corriendo)return; const dt=Math.min((t-this.ultimo)/1000||0,0.05); this.ultimo=t; this.ctx.clearRect(0,0,this.canvas.width,this.canvas.height); this.escena?.actualizar(dt); this.escena?.dibujar(this.ctx); requestAnimationFrame(x=>this.bucle(x)); }
}
