function bucle(){
	//console.log("Yo soy el bucle")
  clearTimeout(temporizador)
  setTimeout("bucle()",1000/fps)
}