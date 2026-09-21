class Estrella extends Entidad {
  constructor(x, y, angulo, velocidad,radio) {
    super(x, y, angulo, velocidad);
    this.radio = radio
  }

  pintar(){
    contexto.fillStyle = "white"
    contexto.beginPath()
    contexto.arc(this.x, this.y,this.radio,0,Math.PI*2);
    contexto.fill()
  }

}