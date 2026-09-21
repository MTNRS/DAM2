class Nave extends Entidad {
  constructor(x, y, angulo, velocidad,energia) {
    super(x, y, angulo, velocidad);
  }
  pintar(){
  	contexto.drawImage(imagen_nave,this.x,this.y)
  }
  mover(x,y){
  	this.x += x
    this.y += y
  }
  disparar(){
  	proyectiles.push(new Proyectil(this.x,this.y,0,0))
  }
}