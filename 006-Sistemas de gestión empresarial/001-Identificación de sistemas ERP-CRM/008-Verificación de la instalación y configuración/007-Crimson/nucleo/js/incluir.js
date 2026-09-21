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
  }
}
cargarIncludes();