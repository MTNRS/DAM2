class Nave extends Entidad {
  constructor(x, y, angulo, velocidad) {
    super(x, y, angulo, velocidad);
  }
  pintar(){
  	contexto.drawImage(imagen_nave,this.x,this.y)
  }
  mover(x,y){
  	this.x += x
    this.y += y
  }
}