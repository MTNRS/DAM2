class Escena {
  constructor(){ this.actores=[]; this.motor=null; this.iniciada=false; this.alIniciar=null; this.alActualizar=null; }
  agregar(actor){ this.actores.push(actor); actor.alCrear?.(actor,this); return actor; }
  buscar(etiqueta){ return this.actores.filter(a=>a.activo&&a.tiene(etiqueta)); }
  actualizar(dt){
    this.alActualizar?.(this,dt);
    for(const a of [...this.actores]) if(a.activo) a.actualizar(dt,this);
    this.resolverColisiones();
    this.actores=this.actores.filter(a=>a.activo);
  }
  resolverColisiones(){
    for(let i=0;i<this.actores.length;i++) for(let j=i+1;j<this.actores.length;j++){
      const a=this.actores[i],b=this.actores[j]; if(!a.activo||!b.activo)continue;
      const dx=a.x-b.x,dy=a.y-b.y,r=a.radio+b.radio;
      if(dx*dx+dy*dy<=r*r){ a.colisionar(b,this); b.colisionar(a,this); }
    }
  }
  dibujar(ctx){ for(const a of this.actores) if(a.activo) a.dibujar(ctx,this); }
}
