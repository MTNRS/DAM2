function inicio(){
	//console.log("Soy el inicio");
  jugador = new Nave(20,20,0,0);
  temporizador = setTimeout("bucle()",1000);
}