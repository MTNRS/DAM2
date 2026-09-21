class Estrella extends Entidad {
  constructor(x, y, angulo, velocidad,radio) {
    super(x, y, angulo, velocidad);
    this.radio = radio
  }

  pintar(){
    contexto.fillStyle = "white"
    contexto.drawImage(imagen_roca, this.x, this.y);
  }

}