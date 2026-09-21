function bucle(){
	//console.log("Yo soy el bucle")
  contexto.clearRect(0,0,anchura,altura)
  
  // Primero pinta las rocas
  rocas.forEach(function(roca) {
    	roca.derivar();
     roca.pintar();
  });
  // Ahora pinta los proyectiles
  proyectiles.forEach(function(proyectil) {
		proyectil.mover()
     proyectil.pintar();
  });
  // Y luego pinta el jugador
  jugador.pintar()
  clearTimeout(temporizador)
  setTimeout("bucle()",1000/fps)
}