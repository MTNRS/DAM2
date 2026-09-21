class Proyectil extends Entidad {
  constructor(x, y, angulo, velocidad) {
    super(x, y, angulo, velocidad);
  }

  pintar(){
    contexto.drawImage(imagen_proyectil, this.x, this.y);
  }
  mover(){
    this.y -= avance
  }

  
}