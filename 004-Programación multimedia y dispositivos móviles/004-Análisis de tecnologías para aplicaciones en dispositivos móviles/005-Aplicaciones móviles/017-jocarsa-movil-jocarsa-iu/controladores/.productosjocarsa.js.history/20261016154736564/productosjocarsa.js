let datosjocarsa = document.querySelector("#datosjocarsa")

fetch("https://jocarsa.com/iav2/productos.txt")
.then(function(resultado){return resultado.text()})
.then(function(datos){
	datosjocarsa.textContent = datos
})