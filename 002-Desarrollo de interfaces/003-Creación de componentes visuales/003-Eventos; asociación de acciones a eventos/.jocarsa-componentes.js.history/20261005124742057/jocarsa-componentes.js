let entradas = document.querySelectorAll("input")

entradas.forEach(function(entrada){
  let control = entrada

  let contenedor = document.createElement("div")
  contenedor.classList.add("jocarsa-componentes")

  // Comprobamos si justo antes hay un label
  let anterior = entrada.previousElementSibling

  if(anterior && anterior.tagName === "LABEL"){
    console.log("Tiene label antes:", anterior)
  }

  entrada.after(contenedor)
  entrada.remove()
  contenedor.appendChild(control)
})