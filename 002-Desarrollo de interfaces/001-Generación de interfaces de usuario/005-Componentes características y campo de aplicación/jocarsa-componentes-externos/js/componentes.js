// ============================================================
// CARGADOR DE TEMPLATES EXTERNOS
// ============================================================

let templates = {};

async function cargarTemplates(){
  let archivos = {
    nav:"templates/nav.html",
    article:"templates/article.html",
    herramienta:"templates/herramienta.html",
    usuario:"templates/usuario.html",
    fila:"templates/fila.html",
    celda:"templates/celda.html"
  };

  for(let nombre in archivos){
    let respuesta = await fetch(archivos[nombre]);
    templates[nombre] = await respuesta.text();
  }
}

function crearComponente(nombre){
  let template = document.createElement("template");
  template.innerHTML = templates[nombre].trim();
  return template.content.cloneNode(true);
}

// ============================================================
// NAVEGACIÓN
// ============================================================

async function componenteNavegacion(){
  let navegaciones = document.querySelectorAll("nav[data-source]");

  for(let navegacion of navegaciones){
    let respuesta = await fetch(navegacion.dataset.source);
    let datos = await respuesta.json();

    datos.forEach(function(dato){
      let clon = crearComponente("nav");
      let enlace = clon.querySelector("a");

      clon.querySelector(".emoji").textContent = dato.emoji;
      clon.querySelector(".texto").textContent = dato.texto;
      enlace.href = dato.url;

      if(dato.activo){
        enlace.classList.add("activo");
      }

      navegacion.appendChild(clon);
    });
  }
}

// ============================================================
// ARTÍCULOS
// ============================================================

async function componenteArticulos(){
  let secciones = document.querySelectorAll("section[data-source]");

  for(let seccion of secciones){
    let respuesta = await fetch(seccion.dataset.source);
    let datos = await respuesta.json();

    datos.forEach(function(dato){
      let clon = crearComponente("article");
      let articulo = clon.querySelector("article");

      clon.querySelector(".emoji").textContent = dato.emoji;
      clon.querySelector(".texto").textContent = dato.texto;

      if(dato.activo){
        articulo.classList.add("activo");
      }

      seccion.appendChild(clon);
    });
  }
}

// ============================================================
// HERRAMIENTAS
// ============================================================

async function componenteHerramientas(){
  let contenedor = document.querySelector("#herramientas");
  let respuesta = await fetch(contenedor.dataset.source);
  let datos = await respuesta.json();

  datos.forEach(function(dato){
    let clon = crearComponente("herramienta");

    clon.querySelector(".emoji").textContent = dato.emoji;
    clon.querySelector(".texto").textContent = dato.texto;

    contenedor.appendChild(clon);
  });
}

// ============================================================
// USUARIO
// ============================================================

async function componenteUsuario(){
  let contenedor = document.querySelector("#usuario");
  let respuesta = await fetch(contenedor.dataset.source);
  let datos = await respuesta.json();

  let clon = crearComponente("usuario");
  clon.querySelector(".nombre").textContent = datos.nombre;

  contenedor.appendChild(clon);
}

// ============================================================
// TABLAS
// ============================================================

async function componenteTablas(){
  let tablas = document.querySelectorAll("table[data-source]");

  for(let tabla of tablas){
    let respuesta = await fetch(tabla.dataset.source);
    let datos = await respuesta.json();

    let caption = document.createElement("caption");
    caption.textContent = datos.titulo;
    tabla.appendChild(caption);

    let thead = document.createElement("thead");
    let filaCabecera = document.createElement("tr");

    datos.columnas.forEach(function(columna){
      let th = document.createElement("th");
      th.textContent = columna.etiqueta;
      filaCabecera.appendChild(th);
    });

    thead.appendChild(filaCabecera);
    tabla.appendChild(thead);

    let tbody = document.createElement("tbody");

    datos.registros.forEach(function(registro){
      let clonFila = crearComponente("fila");
      let fila = clonFila.querySelector("tr");

      datos.columnas.forEach(function(columna){
        let clonCelda = crearComponente("celda");
        let celda = clonCelda.querySelector("td");

        celda.textContent = registro[columna.campo] ?? "";
        fila.appendChild(clonCelda);
      });

      tbody.appendChild(clonFila);
    });

    tabla.appendChild(tbody);
  }
}

// ============================================================
// INICIALIZACIÓN
// ============================================================

async function iniciarComponentes(){
  await cargarTemplates();

  await Promise.all([
    componenteNavegacion(),
    componenteArticulos(),
    componenteTablas(),
    componenteHerramientas(),
    componenteUsuario()
  ]);
}

document.addEventListener("DOMContentLoaded", iniciarComponentes);
