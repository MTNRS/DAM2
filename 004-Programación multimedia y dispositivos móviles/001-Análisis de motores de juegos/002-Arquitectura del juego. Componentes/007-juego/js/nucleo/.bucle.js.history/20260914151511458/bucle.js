function bucle(){
	//console.log("Yo soy el bucle")
  jugador.pintar()
  clearTimeout(temporizador)
  setTimeout("bucle()",1000/fps)
}