let entradas = document.querySelectorAll("input")

entradas.forEach(function(entrada){

  let tipoOriginal = entrada.type
  let valorOriginal = entrada.value

  let contenedor = crearContenedor(entrada)
  let control = crearControl()


  // ========================================================
  // INPUT TYPE SUBMIT
  // ========================================================

  if(tipoOriginal === "submit"){

    let boton = document.createElement("button")

    boton.type = "button"

    boton.classList.add(
      "boton-jocarsa",
      "clickable"
    )

    boton.textContent =
      entrada.value || "Enviar"


    boton.addEventListener("click", function(){

      let formulario =
        entrada.closest("form")

      if(formulario){

        /*
          requestSubmit() es preferible a submit():
          
          - ejecuta validación HTML5
          - dispara el evento submit
          - respeta required
          - respeta validadores
        */

        formulario.requestSubmit()

      }

    })


    control.appendChild(boton)

    contenedor.appendChild(control)

    /*
      Ahora podemos eliminar el input original
      porque ya hemos guardado todo lo necesario.
    */

    entrada.remove()

    return
  }


  // ========================================================
  // INPUT TYPE RESET
  // ========================================================

  if(tipoOriginal === "reset"){

    let boton = document.createElement("button")

    boton.type = "button"

    boton.classList.add(
      "boton-jocarsa",
      "clickable"
    )

    boton.textContent =
      entrada.value || "Restablecer"


    boton.addEventListener("click", function(){

      let formulario =
        entrada.closest("form")

      if(formulario){
        formulario.reset()
      }

    })


    control.appendChild(boton)

    contenedor.appendChild(control)

    entrada.remove()

    return
  }


  // ========================================================
  // INPUT TYPE BUTTON
  // ========================================================

  if(tipoOriginal === "button"){

    let boton = document.createElement("button")

    boton.type = "button"

    boton.classList.add(
      "boton-jocarsa",
      "clickable"
    )

    boton.textContent =
      entrada.value || "Botón"


    /*
      Conservamos el onclick original si existe
    */

    if(entrada.onclick){

      boton.onclick =
        entrada.onclick

    }


    control.appendChild(boton)

    contenedor.appendChild(control)

    entrada.remove()

    return
  }


  // ========================================================
  // CHECKBOX
  // ========================================================

  if(tipoOriginal === "checkbox"){

    let switchControl =
      document.createElement("div")

    switchControl.classList.add(
      "switch",
      "clickable"
    )

    switchControl.setAttribute(
      "tabindex",
      "0"
    )

    switchControl.setAttribute(
      "role",
      "checkbox"
    )


    let circulo =
      document.createElement("span")

    switchControl.appendChild(circulo)


    if(entrada.checked){

      switchControl.classList.add(
        "activo"
      )

      switchControl.setAttribute(
        "aria-checked",
        "true"
      )

    }else{

      switchControl.setAttribute(
        "aria-checked",
        "false"
      )

    }


    function cambiarSwitch(){

      switchControl.classList.toggle(
        "activo"
      )

      switchControl.setAttribute(
        "aria-checked",
        switchControl.classList.contains("activo")
          ? "true"
          : "false"
      )

    }


    switchControl.onclick =
      cambiarSwitch


    switchControl.onkeydown =
      function(event){

        if(
          event.key === " " ||
          event.key === "Enter"
        ){

          event.preventDefault()

          cambiarSwitch()

        }

      }


    control.appendChild(
      switchControl
    )

    contenedor.appendChild(
      control
    )

    entrada.remove()

    return
  }


  // ========================================================
  // RADIO
  // ========================================================

  if(tipoOriginal === "radio"){

    let radio =
      document.createElement("div")

    radio.classList.add(
      "radio-jocarsa",
      "clickable"
    )

    radio.setAttribute(
      "tabindex",
      "0"
    )

    radio.setAttribute(
      "role",
      "radio"
    )


    let interior =
      document.createElement("span")

    radio.appendChild(interior)


    if(entrada.checked){

      radio.classList.add(
        "activo"
      )

      radio.setAttribute(
        "aria-checked",
        "true"
      )

    }else{

      radio.setAttribute(
        "aria-checked",
        "false"
      )

    }


    function activarRadio(){

      let radios =
        document.querySelectorAll(
          ".radio-jocarsa"
        )

      radios.forEach(function(r){

        r.classList.remove(
          "activo"
        )

        r.setAttribute(
          "aria-checked",
          "false"
        )

      })


      radio.classList.add(
        "activo"
      )

      radio.setAttribute(
        "aria-checked",
        "true"
      )

    }


    radio.onclick =
      activarRadio


    radio.onkeydown =
      function(event){

        if(
          event.key === " " ||
          event.key === "Enter"
        ){

          event.preventDefault()

          activarRadio()

        }

      }


    control.appendChild(radio)

    contenedor.appendChild(control)

    entrada.remove()

    return
  }


  // ========================================================
  // RESTO DE INPUTS
  // ========================================================

  let campo =
    crearCampo(valorOriginal)


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

    "datetime-local": "DT",

    month: "M",

    week: "W",

    color: "RGB",

    range: "-",

    file: "F",

    hidden: "H"

  }


  let tipo =
    crearTipo(
      tipos[tipoOriginal] || "?"
    )


  // ========================================================
  // EMAIL
  // ========================================================

  if(tipoOriginal === "email"){

    function validarEmail(){

      let valor =
        campo.textContent.trim()


      if(valor === ""){

        estadoNeutro(campo)

        return

      }


      let expresion =
        /^[^\s@]+@[^\s@]+\.[^\s@]+$/


      if(expresion.test(valor)){

        estadoValido(campo)

      }else{

        estadoInvalido(campo)

      }

    }


    campo.addEventListener(
      "input",
      validarEmail
    )

    validarEmail()

  }


  // ========================================================
  // URL
  // ========================================================

  if(tipoOriginal === "url"){

    function validarURL(){

      let valor =
        campo.textContent.trim()


      if(valor === ""){

        estadoNeutro(campo)

        return

      }


      try{

        let url =
          new URL(valor)


        if(
          url.protocol === "http:" ||
          url.protocol === "https:"
        ){

          estadoValido(campo)

        }else{

          estadoInvalido(campo)

        }

      }catch(error){

        estadoInvalido(campo)

      }

    }


    campo.addEventListener(
      "input",
      validarURL
    )

    validarURL()

  }


  // ========================================================
  // NUMBER
  // ========================================================

  if(tipoOriginal === "number"){

    function validarNumero(){

      let valor =
        campo.textContent.trim()


      if(valor === ""){

        estadoNeutro(campo)

        return

      }


      if(
        valor !== "" &&
        !isNaN(valor) &&
        isFinite(valor)
      ){

        estadoValido(campo)

      }else{

        estadoInvalido(campo)

      }

    }


    campo.addEventListener(
      "input",
      validarNumero
    )

    validarNumero()

  }


  // ========================================================
  // PASSWORD
  // ========================================================

  if(tipoOriginal === "password"){

    let valorReal =
      valorOriginal

    let visible =
      false


    campo.textContent =
      "*".repeat(
        valorReal.length
      )


    let ojo =
      document.createElement("button")


    ojo.type =
      "button"


    ojo.classList.add(
      "ojo-password",
      "clickable"
    )


    let iconoOjo =
      document.createElement("span")


    iconoOjo.classList.add(
      "icono-ojo"
    )


    ojo.appendChild(
      iconoOjo
    )


    ojo.onclick =
      function(){

        visible =
          !visible


        if(visible){

          campo.textContent =
            valorReal

          ojo.classList.add(
            "activo"
          )

        }else{

          valorReal =
            campo.textContent

          campo.textContent =
            "*".repeat(
              valorReal.length
            )

          ojo.classList.remove(
            "activo"
          )

        }

      }


    campo.addEventListener(
      "input",
      function(){

        if(visible){

          valorReal =
            campo.textContent

        }

      }
    )


    control.appendChild(campo)
    control.appendChild(ojo)
    control.appendChild(tipo)

  }else{

    control.appendChild(campo)
    control.appendChild(tipo)

  }


  contenedor.appendChild(control)

  entrada.remove()

})