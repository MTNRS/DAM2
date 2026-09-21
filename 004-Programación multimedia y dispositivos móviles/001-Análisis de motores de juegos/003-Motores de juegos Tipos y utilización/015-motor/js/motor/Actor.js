/** Actor es la unidad genérica del motor. No sabe qué es una nave, roca o bala. */
class Actor {
  constructor(config = {}){
    this.x=config.x??0; this.y=config.y??0; this.angulo=config.angulo??0;
    this.vx=config.vx??0; this.vy=config.vy??0; this.velocidad=config.velocidad??0;
    this.radio=config.radio??16; this.vida=config.vida??1; this.activo=true;
    this.etiquetas=new Set(config.etiquetas??[]); this.datos={...(config.datos??{})};
    this.comportamientos=[];
    this.alActualizar=config.alActualizar??null;
    this.alDibujar=config.alDibujar??null;
    this.alColisionar=config.alColisionar??null;
    this.alCrear=config.alCrear??null;
    this.alDestruir=config.alDestruir??null;
  }
  agregarComportamiento(fn){ this.comportamientos.push(fn); return this; }
  actualizar(dt, escena){
    this.x += this.vx*dt; this.y += this.vy*dt;
    for(const fn of this.comportamientos) fn(this,dt,escena);
    this.alActualizar?.(this,dt,escena);
  }
  dibujar(ctx, escena){ this.alDibujar?.(this,ctx,escena); }
  colisionar(otro, escena){ this.alColisionar?.(this,otro,escena); }
  tiene(etiqueta){ return this.etiquetas.has(etiqueta); }
  destruir(escena){ if(!this.activo)return; this.activo=false; this.alDestruir?.(this,escena); }
}
