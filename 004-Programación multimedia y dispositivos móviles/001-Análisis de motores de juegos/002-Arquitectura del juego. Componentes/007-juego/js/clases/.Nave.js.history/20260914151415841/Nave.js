class Nave extends Entidad {
  constructor(x, y, angulo, velocidad) {
    super(x, y, angulo, velocidad);
  }
  pintar(){
  	contexto.fillRect(this.x,this.y,10,10)
  }
}