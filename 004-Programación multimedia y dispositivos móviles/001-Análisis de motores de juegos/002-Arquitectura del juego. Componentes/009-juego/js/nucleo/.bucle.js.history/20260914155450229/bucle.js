function bucle(){
	//console.log("Yo soy el bucle")
  contexto.clearRect(0,0,anchura,altura)
  
  
  rocas.forEach(function(roca) {
    	roca.derivar();
     roca.pintar();
  });
  proyectiles.forEach(function(proyectil) {

     proyectil.pintar();
  });
  jugador.pintar()
  clearTimeout(temporizador)
  setTimeout("bucle()",1000/fps)
}