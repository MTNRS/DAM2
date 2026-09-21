function inicio(){
	//console.log("Soy el inicio");
  jugador = new Jugador(20,20,0,0);
  temporizador = setTimeout("bucle()",1000);
}