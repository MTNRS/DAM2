function bucle(){
	//console.log("Yo soy el bucle")
  contexto.fillRect(jugador.x,jugador.y,10,10)
  clearTimeout(temporizador)
  setTimeout("bucle()",1000/fps)
}