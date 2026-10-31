let datosjocarsa = document.querySelector("#datosjocarsa")

fetch("https://static.jocarsa.com/productos/productos.txt")
.then(function(resultado){return resultado.text()})
.then(function(datos){
	datosjocarsa.textContent = datos
})