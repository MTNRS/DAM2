function bucle(){
	//console.log("Yo soy el bucle")
  contexto.clearRect(0,0,anchura,altura)
  // Primero pinta las estrellas
  estrellas.forEach(function(estrella) {
     estrella.pintar();
  });
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
  // Colisión rocas con balas
  for(let i = rocas.length - 1; i >= 0; i--){
      for(let j = proyectiles.length - 1; j >= 0; j--){
          if(
              distancia(
                  rocas[i].x,
                  rocas[i].y,
                  proyectiles[j].x,
                  proyectiles[j].y
              ) < 50
          ){
              // Eliminar roca
              rocas.splice(i, 1);
              // Eliminar proyectil
              proyectiles.splice(j, 1);
              // Esta roca ya ha sido destruida
              break;
          }
      }
  }
  // Colisión rocas con jugador
  iu_rocas.textContent ="Rocas: "+rocas.length
  ganar()
  clearTimeout(temporizador)
  setTimeout("bucle()",1000/fps)
}