document.addEventListener("DOMContentLoaded", function(){

  // ============================================================
  // UTILIDADES
  // ============================================================

  function crearContenedor(elemento){

    let contenedor = document.createElement("div")
    contenedor.classList.add("jocarsa-componentes")

    let anterior = elemento.previousElementSibling

    elemento.after(contenedor)

    if(anterior && anterior.tagName === "LABEL"){
      contenedor.appendChild(anterior)
    }

    return contenedor
  }


  function crearControl(){

    let control = document.createElement("div")
    control.classList.add("control")

    return control
  }


  function crearCampo(valor = ""){

    let campo = document.createElement("div")

    campo.classList.add(
      "campo",
      "focusable"
    )

    campo.setAttribute("contenteditable", "true")
    campo.setAttribute("tabindex", "0")

    campo.textContent = valor

    return campo
  }


  function crearTipo(texto){

    let tipo = document.createElement("span")

    tipo.classList.add("tipo-input")

    tipo.textContent = texto

    return tipo
  }


  function ocultarOriginal(elemento){

    elemento.classList.add(
      "jocarsa-original"
    )

    elemento.setAttribute(
      "tabindex",
      "-1"
    )
  }


  function estadoNeutro(campo){

    campo.classList.remove(
      "valido",
      "invalido"
    )
  }


  function estadoValido(campo){

    campo.classList.add("valido")
    campo.classList.remove("invalido")
  }


  function estadoInvalido(campo){

    campo.classList.add("invalido")
    campo.classList.remove("valido")
  }


  // ============================================================
  // INPUTS
  // ============================================================

  let entradas = document.querySelectorAll("input")

  entradas.forEach(function(entrada){

    let tipoOriginal = entrada.type
    let valorOriginal = entrada.value

    // ==========================================================
    // HIDDEN
    // ==========================================================

    if(tipoOriginal === "hidden"){
      return
    }


    // Guardamos formulario ANTES de mover cosas
    let formularioOriginal =
      entrada.closest("form")


    let contenedor =
      crearContenedor(entrada)

    let control =
      crearControl()


    // ==========================================================
    // SUBMIT
    // ==========================================================

    if(tipoOriginal === "submit"){

      let boton =
        document.createElement("button")

      boton.type = "button"

      boton.classList.add(
        "boton-jocarsa",
        "clickable"
      )

      boton.textContent =
        entrada.value || "Enviar"


      boton.addEventListener(
        "click",
        function(){

          if(formularioOriginal){

            /*
              Usamos el submit original como submitter.
              De esta forma también conserva name/value.
            */

            formularioOriginal.requestSubmit(
              entrada
            )

          }

        }
      )


      control.appendChild(boton)

      contenedor.appendChild(control)

      ocultarOriginal(entrada)

      return
    }


    // ==========================================================
    // RESET
    // ==========================================================

    if(tipoOriginal === "reset"){

      let boton =
        document.createElement("button")

      boton.type = "button"

      boton.classList.add(
        "boton-jocarsa",
        "clickable"
      )

      boton.textContent =
        entrada.value ||
        "Restablecer"


      boton.addEventListener(
        "click",
        function(){

          if(formularioOriginal){
            formularioOriginal.reset()
          }

        }
      )


      control.appendChild(boton)

      contenedor.appendChild(control)

      ocultarOriginal(entrada)

      return
    }


    // ==========================================================
    // BUTTON
    // ==========================================================

    if(tipoOriginal === "button"){

      let boton =
        document.createElement("button")

      boton.type = "button"

      boton.classList.add(
        "boton-jocarsa",
        "clickable"
      )

      boton.textContent =
        entrada.value || "Botón"


      boton.addEventListener(
        "click",
        function(){

          entrada.click()

        }
      )


      control.appendChild(boton)

      contenedor.appendChild(control)

      ocultarOriginal(entrada)

      return
    }


    // ==========================================================
    // CHECKBOX
    // ==========================================================

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

      switchControl.appendChild(
        circulo
      )


      function actualizarSwitch(){

        if(entrada.checked){

          switchControl.classList.add(
            "activo"
          )

          switchControl.setAttribute(
            "aria-checked",
            "true"
          )

        }else{

          switchControl.classList.remove(
            "activo"
          )

          switchControl.setAttribute(
            "aria-checked",
            "false"
          )

        }

      }


      function cambiarSwitch(){

        entrada.checked =
          !entrada.checked

        entrada.dispatchEvent(
          new Event(
            "change",
            {
              bubbles:true
            }
          )
        )

        actualizarSwitch()
      }


      actualizarSwitch()


      switchControl.addEventListener(
        "click",
        cambiarSwitch
      )


      switchControl.addEventListener(
        "keydown",
        function(event){

          if(
            event.key === " " ||
            event.key === "Enter"
          ){

            event.preventDefault()

            cambiarSwitch()

          }

        }
      )


      entrada.addEventListener(
        "change",
        actualizarSwitch
      )


      control.appendChild(
        switchControl
      )

      contenedor.appendChild(
        control
      )

      ocultarOriginal(entrada)

      return
    }


    // ==========================================================
    // RADIO
    // ==========================================================

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

      radio.appendChild(
        interior
      )


      function actualizarRadio(){

        if(entrada.checked){

          radio.classList.add(
            "activo"
          )

          radio.setAttribute(
            "aria-checked",
            "true"
          )

        }else{

          radio.classList.remove(
            "activo"
          )

          radio.setAttribute(
            "aria-checked",
            "false"
          )

        }

      }


      function activarRadio(){

        /*
          Dejamos al input nativo realizar
          toda la lógica de grupos radio.
        */

        entrada.click()

        /*
          Actualizamos todos los radios
          transformados.
        */

        document
          .querySelectorAll(
            'input[type="radio"].jocarsa-original'
          )
          .forEach(function(inputRadio){

            inputRadio.dispatchEvent(
              new Event(
                "jocarsa-actualizar-radio"
              )
            )

          })
      }


      entrada.addEventListener(
        "jocarsa-actualizar-radio",
        actualizarRadio
      )


      entrada.addEventListener(
        "change",
        function(){

          document
            .querySelectorAll(
              'input[type="radio"].jocarsa-original'
            )
            .forEach(function(inputRadio){

              inputRadio.dispatchEvent(
                new Event(
                  "jocarsa-actualizar-radio"
                )
              )

            })

        }
      )


      radio.addEventListener(
        "click",
        activarRadio
      )


      radio.addEventListener(
        "keydown",
        function(event){

          if(
            event.key === " " ||
            event.key === "Enter"
          ){

            event.preventDefault()

            activarRadio()

          }

        }
      )


      actualizarRadio()


      control.appendChild(radio)

      contenedor.appendChild(control)

      ocultarOriginal(entrada)

      return
    }


    // ==========================================================
    // RESTO DE INPUTS
    // ==========================================================

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

      file: "F"

    }


    let tipo =
      crearTipo(
        tipos[tipoOriginal] || "?"
      )


    // ==========================================================
    // SINCRONIZACIÓN CON INPUT REAL
    // ==========================================================

    campo.addEventListener(
      "input",
      function(){

        /*
          Password se gestiona aparte.
        */

        if(tipoOriginal !== "password"){

          entrada.value =
            campo.textContent

          entrada.dispatchEvent(
            new Event(
              "input",
              {
                bubbles:true
              }
            )
          )

        }

      }
    )


    // ==========================================================
    // EMAIL
    // ==========================================================

    if(tipoOriginal === "email"){

      function validarEmail(){

        let valor =
          campo.textContent.trim()


        entrada.value = valor


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


    // ==========================================================
    // URL
    // ==========================================================

    if(tipoOriginal === "url"){

      function validarURL(){

        let valor =
          campo.textContent.trim()


        entrada.value = valor


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


    // ==========================================================
    // NUMBER
    // ==========================================================

    if(tipoOriginal === "number"){

      function validarNumero(){

        let valor =
          campo.textContent.trim()


        /*
          Solo escribimos al input number
          si el navegador lo acepta.
        */

        if(valor === ""){

          entrada.value = ""

          estadoNeutro(campo)

          return
        }


        if(
          !isNaN(valor) &&
          isFinite(valor)
        ){

          entrada.value = valor

          /*
            checkValidity también comprueba:
            min
            max
            step
          */

          if(entrada.checkValidity()){

            estadoValido(campo)

          }else{

            estadoInvalido(campo)

          }

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


    // ==========================================================
    // PASSWORD
    // ==========================================================

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
        document.createElement(
          "button"
        )

      ojo.type = "button"

      ojo.classList.add(
        "ojo-password",
        "clickable"
      )


      let iconoOjo =
        document.createElement(
          "span"
        )

      iconoOjo.classList.add(
        "icono-ojo"
      )

      ojo.appendChild(
        iconoOjo
      )


      ojo.addEventListener(
        "click",
        function(){

          visible = !visible


          if(visible){

            campo.textContent =
              valorReal

            ojo.classList.add(
              "activo"
            )

          }else{

            /*
              Si estaba visible, almacenamos
              lo que haya escrito.
            */

            valorReal =
              campo.textContent

            entrada.value =
              valorReal

            campo.textContent =
              "*".repeat(
                valorReal.length
              )

            ojo.classList.remove(
              "activo"
            )

          }

        }
      )


      campo.addEventListener(
        "input",
        function(){

          if(visible){

            valorReal =
              campo.textContent

            entrada.value =
              valorReal

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

    ocultarOriginal(entrada)

  })


  // ============================================================
  // TEXTAREA
  // ============================================================

  let textareas =
    document.querySelectorAll(
      "textarea"
    )


  textareas.forEach(
    function(textarea){

      let valor =
        textarea.value


      let contenedor =
        crearContenedor(
          textarea
        )


      let control =
        crearControl()


      let campo =
        crearCampo(valor)


      campo.classList.add(
        "campo-textarea"
      )


      let tipo =
        crearTipo("TXT")


      /*
        Sincronizamos el contenteditable
        con el textarea real.
      */

      campo.addEventListener(
        "input",
        function(){

          textarea.value =
            campo.textContent

          textarea.dispatchEvent(
            new Event(
              "input",
              {
                bubbles:true
              }
            )
          )

        }
      )


      control.appendChild(campo)
      control.appendChild(tipo)

      contenedor.appendChild(control)

      ocultarOriginal(textarea)

    }
  )


  // ============================================================
  // BUTTON NATIVO <button>
  // ============================================================

  let botones =
    document.querySelectorAll(
      "button:not(.ojo-password)"
    )


  botones.forEach(
    function(botonOriginal){

      /*
        Si está dentro de un componente,
        significa que lo acabamos de crear
        nosotros. No lo procesamos.
      */

      if(
        botonOriginal.closest(
          ".jocarsa-componentes"
        )
      ){
        return
      }


      let formularioOriginal =
        botonOriginal.closest("form")

      let tipoBoton =
        botonOriginal.type || "submit"


      let contenedor =
        crearContenedor(
          botonOriginal
        )


      let control =
        crearControl()


      let boton =
        document.createElement("button")

      boton.type = "button"

      boton.classList.add(
        "boton-jocarsa",
        "clickable"
      )


      boton.innerHTML =
        botonOriginal.innerHTML


      boton.addEventListener(
        "click",
        function(){

          if(
            tipoBoton === "submit" &&
            formularioOriginal
          ){

            formularioOriginal.requestSubmit(
              botonOriginal
            )

          }else if(
            tipoBoton === "reset" &&
            formularioOriginal
          ){

            formularioOriginal.reset()

          }else{

            botonOriginal.click()

          }

        }
      )


      control.appendChild(boton)

      contenedor.appendChild(control)

      ocultarOriginal(
        botonOriginal
      )

    }
  )

})