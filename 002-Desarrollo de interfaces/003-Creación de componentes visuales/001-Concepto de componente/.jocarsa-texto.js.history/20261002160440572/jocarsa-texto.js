let elementos = document.querySelectorAll("jocarsa-texto");

      elementos.forEach(function(elemento){

        let elementoenmemoria = elemento;

        // Contenedor principal
        let contenedor = document.createElement("div");
        contenedor.classList.add("jocarsa-texto");

        elementoenmemoria.after(contenedor);
        elemento.remove();


        // ==============================
        // BARRA DE HERRAMIENTAS
        // ==============================

        let barra = document.createElement("div");
        barra.classList.add("barra");

        contenedor.appendChild(barra);


        // Función para crear botones
        function crearBoton(texto, comando, valor = null){

          let boton = document.createElement("button");

          boton.innerHTML = texto;

          barra.appendChild(boton);

          boton.onclick = function(){

            document.execCommand(
              comando,
              false,
              valor
            );

            contenedor_rico.focus();

            actualizarHTML();
          };

          return boton;
        }


        // ==============================
        // BOTONES
        // ==============================

        crearBoton("<b>B</b>", "bold");

        crearBoton("<i>I</i>", "italic");

        crearBoton("<u>U</u>", "underline");

        crearBoton("<s>S</s>", "strikeThrough");

        crearBoton("H1", "formatBlock", "h1");

        crearBoton("H2", "formatBlock", "h2");

        crearBoton("P", "formatBlock", "p");

        crearBoton("•", "insertUnorderedList");

        crearBoton("1.", "insertOrderedList");

        crearBoton("←", "justifyLeft");

        crearBoton("↔", "justifyCenter");

        crearBoton("→", "justifyRight");

        crearBoton("↶", "undo");

        crearBoton("↷", "redo");


        // ==============================
        // BOTÓN ENLACE
        // ==============================

        let boton_enlace = document.createElement("button");

        boton_enlace.textContent = "🔗";

        barra.appendChild(boton_enlace);

        boton_enlace.onclick = function(){

          let url = prompt("Introduce la URL:");

          if(url){

            document.execCommand(
              "createLink",
              false,
              url
            );

          }

          actualizarHTML();
        };


        // ==============================
        // QUITAR FORMATO
        // ==============================

        let boton_limpiar = document.createElement("button");

        boton_limpiar.textContent = "Tx";

        barra.appendChild(boton_limpiar);

        boton_limpiar.onclick = function(){

          document.execCommand(
            "removeFormat",
            false,
            null
          );

          actualizarHTML();
        };


        // ==============================
        // EDITOR VISUAL
        // ==============================

        let contenedor_rico = document.createElement("article");

        contenedor_rico.setAttribute(
          "contenteditable",
          "true"
        );

        contenedor.appendChild(contenedor_rico);


        // ==============================
        // TEXTAREA HTML
        // ==============================

        let contenedor_pobre = document.createElement("textarea");

        contenedor.appendChild(contenedor_pobre);

        contenedor_pobre.style.display = "none";


        // ==============================
        // SINCRONIZACIÓN
        // ==============================

        function actualizarHTML(){

          contenedor_pobre.value =
            contenedor_rico.innerHTML;

        }

        contenedor_rico.oninput = function(){

          actualizarHTML();

        };


        // ==============================
        // MOSTRAR / OCULTAR HTML
        // ==============================

        let boton_html = document.createElement("button");

        boton_html.textContent = "</>";

        barra.appendChild(boton_html);

        boton_html.onclick = function(){

          if(contenedor_pobre.style.display == "none"){

            actualizarHTML();

            contenedor_pobre.style.display = "block";
            contenedor_rico.style.display = "none";

          }else{

            contenedor_rico.innerHTML =
              contenedor_pobre.value;

            contenedor_pobre.style.display = "none";
            contenedor_rico.style.display = "block";

          }

        };

      });