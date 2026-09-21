async function cargarIncludes() {

  const elementos = document.querySelectorAll("[data-include]");

  for (const elemento of elementos) {

    const archivo = elemento.dataset.include;

    const respuesta = await fetch(archivo);

    if (!respuesta.ok) {
      console.error("No se puede cargar:", archivo);
      continue;
    }

    elemento.innerHTML = await respuesta.text();

    // Ejecutar los scripts que hayan venido dentro del include
    const scripts = elemento.querySelectorAll("script");

    scripts.forEach(function(scriptViejo){

      const scriptNuevo = document.createElement("script");

      for (const atributo of scriptViejo.attributes) {
        scriptNuevo.setAttribute(
          atributo.name,
          atributo.value
        );
      }

      scriptNuevo.textContent = scriptViejo.textContent;

      scriptViejo.replaceWith(scriptNuevo);

    });

  }

}

cargarIncludes();
