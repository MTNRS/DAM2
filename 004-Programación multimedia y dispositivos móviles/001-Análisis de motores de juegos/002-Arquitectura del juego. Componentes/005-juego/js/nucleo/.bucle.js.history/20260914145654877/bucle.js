function bucle(){
	console.log("Yo soy el bucle")
  clearTimeout(temporizador)
  setTimeout("bucle()",fps)
}