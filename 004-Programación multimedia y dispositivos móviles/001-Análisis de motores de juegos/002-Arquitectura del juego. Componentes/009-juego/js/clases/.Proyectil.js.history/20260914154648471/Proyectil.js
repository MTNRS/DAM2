class Proyectil extends Entidad {
  constructor(x, y, angulo, velocidad) {
    super(x, y, angulo, velocidad);
  }

  pintar(){
    contexto.drawImage(imagen_proyectil, this.x, this.y);
  }

  derivar(){
    this.a = this.a + (Math.random() - 0.5) * 0.2;
    this.x += Math.cos(this.a);
    this.y += Math.sin(this.a);
  }
}