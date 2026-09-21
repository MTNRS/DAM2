function bucle(){
	//console.log("Yo soy el bucle")
  contexto.clearRect(0,0,anchura,altura)
  jugador.pintar()
  for(let i )
  clearTimeout(temporizador)
  setTimeout("bucle()",1000/fps)
}