function bucle(){
	//console.log("Yo soy el bucle")
  contexto.drawRect(jugador.x,jugador.y,10,10)
  clearTimeout(temporizador)
  setTimeout("bucle()",1000/fps)
}