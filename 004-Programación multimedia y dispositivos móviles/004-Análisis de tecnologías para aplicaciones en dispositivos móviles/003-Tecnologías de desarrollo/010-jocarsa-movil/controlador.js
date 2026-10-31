 let contenedor = document.querySelector("#contenedor");
    let botones = document.querySelectorAll("nav button");
    let controladorActual = null;
    let navegacion = 0;

    async function cargarVista(nombre) {
      let actual = ++navegacion;

      try {
        // Cargar vista
        let respuesta = await fetch("vistas/" + nombre + ".html");

        if (!respuesta.ok) {
          throw new Error("Vista no encontrada");
        }

        let html = await respuesta.text();

        if (actual !== navegacion) return;

        // Eliminar controlador anterior
        if (controladorActual) {
          controladorActual.remove();
          controladorActual = null;
        }

        // Mostrar vista
        contenedor.innerHTML = html;

        // Comprobar si existe controlador
        let ruta = "controladores/" + nombre + ".js";
        let respuestaJS = await fetch(ruta);

        if (actual !== navegacion) return;

        if (respuestaJS.ok) {
          let codigo = await respuestaJS.text();

          if (actual !== navegacion) return;

          // Crear script y ejecutarlo
          let script = document.createElement("script");
          script.textContent = codigo;
          document.body.appendChild(script);

          controladorActual = script;
        }

      } catch (error) {
        if (actual === navegacion) {
          console.error(error);
          contenedor.textContent = "Error al cargar la vista";
        }
      }
    }

    botones.forEach(function(boton) {
      boton.onclick = function() {
        cargarVista(this.getAttribute("data-url"));
      };
    });

   