class Roca extends Entidad {
  constructor(x, y, angulo, velocidad) {
    super(x, y, angulo, velocidad);
  }

  pintar(){
    contexto.drawImage(imagen_roca, this.x, this.y);
  }

  derivar(){
    this.a = this.a + (Math.random() - 0.5) * 0.2;
    this.x += Math.cos(this.a);
    this.y += Math.sin(this.a);
    if(this.x < 0 || this.x > anchura || this.y < 0 || this.y > altura){
    	this.a += Math.PI
    }
  }
}