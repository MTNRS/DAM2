let entradas = document.querySelectorAll("input")
entradas.forEach(function(entrada){
	let control = entrada;
  
  let contenedor = document.createElement("div")
  contenedor.classList.add("jocarsa-componentes")
  entrada.after(contenedor)
  entrada.remove();
  contenedor.appendChild(control)
  
})