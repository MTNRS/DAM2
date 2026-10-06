let entradas = document.querySelectorAll("input")

entradas.forEach(function(entrada){
  let control = entrada

  let contenedor = document.createElement("div")
  contenedor.classList.add("jocarsa-componentes")

  // Comprobamos si justo antes hay un label
  let anterior = entrada.previousElementSibling

  // Creamos el editor
  let mielemento = document.createElement("div")
  mielemento.setAttribute("contenteditable", "true")

  // Creamos indicador del tipo
  let tipo = document.createElement("span")
  tipo.classList.add("tipo-input")

  let tipos = {
    text: "T",
    number: "1",
    password: "*",
    email: "@",
    tel: "TEL",
    url: "URL",
    search: "?",
    date: "D",
    time: "H",
    datetime-local: "DT",
    month: "M",
    week: "W",
    color: "RGB",
    range: "---",
    checkbox: "X",
    radio: "O",
    file: "FILE",
    hidden: "H",
    submit: "OK",
    reset: "R",
    button: "BTN"
  }

  tipo.textContent = tipos[entrada.type] || "?"

  // Insertamos el contenedor
  entrada.after(contenedor)

  // Si hay label, lo movemos dentro
  if(anterior && anterior.tagName === "LABEL"){
    contenedor.appendChild(anterior)
  }

  // Añadimos indicador + editor
  contenedor.appendChild(tipo)
  contenedor.appendChild(mielemento)

  // Eliminamos input original
  entrada.remove()
})