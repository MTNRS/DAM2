function inicio(){
	//console.log("Soy el inicio");
  contexto.clearRect(0,0,anchura,altura)
  jugador = new Nave(200,200,0,0);
  temporizador = setTimeout("bucle()",1000);
}