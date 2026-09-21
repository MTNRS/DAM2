class Roca extends Entidad {
  constructor(x, y, angulo, velocidad) {
    super(x, y, angulo, velocidad);
  }
  pintar(){
  	contexto.drawImage(imagen_roca,this.x,this.y)
  }
  derivar(){
   this.angulo = this.angulo + (Math.random()-0.5)*0.2
    this.x += Math.cos(this.angulo)
    this.y += Math.sin(this.angulo)
  }
  
}