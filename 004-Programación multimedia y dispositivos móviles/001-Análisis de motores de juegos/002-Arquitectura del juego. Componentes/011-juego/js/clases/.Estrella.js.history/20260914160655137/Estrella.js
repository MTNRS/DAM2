class Estrella extends Entidad {
  constructor(x, y, angulo, velocidad) {
    super(x, y, angulo, velocidad);
  }

  pintar(){
    contexto.drawImage(imagen_roca, this.x, this.y);
  }

}