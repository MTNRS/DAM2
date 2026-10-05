/**
 * jocarsa | iu
 * Control principal de la interfaz mediante programación orientada a objetos.
 *
 * Nota sobre el namespace:
 * JavaScript no admite "|" dentro de un identificador. Por ello el namespace
 * técnico equivalente es `jocarsa.iu`.
 */
window.jocarsa = window.jocarsa || {};
window.jocarsa.iu = window.jocarsa.iu || {};

(() => {
  "use strict";

  class DOM {
    static uno(selector, contexto = document) {
      return contexto.querySelector(selector);
    }

    static todos(selector, contexto = document) {
      return [...contexto.querySelectorAll(selector)];
    }
  }

  class Utilidades {
    static hashTexto(texto) {
      let hash = 0;

      for (const caracter of texto) {
        hash = caracter.charCodeAt(0) + ((hash << 5) - hash);
      }

      return Math.abs(hash);
    }

    static colorTexto(texto) {
      return `hsl(${Utilidades.hashTexto(texto) % 360},55%,45%)`;
    }
  }

  class OrigenDatos {
    static async cargarJSON(url) {
      const respuesta = await fetch(url);

      if (!respuesta.ok) {
        throw new Error(url);
      }

      return respuesta.json();
    }
  }

  class EstadoIU {
    constructor() {
      this.menu = [];
      this.entidades = [];
      this.clientes = null;
      this.formulario = null;
      this.seleccion = "Listado";
    }
  }

  class FichaIU {
    static completar(elemento, texto) {
      const ficha = DOM.uno(".ficha", elemento);
      const etiqueta = DOM.uno(".etiqueta", elemento);

      ficha.textContent = texto.charAt(0).toUpperCase();
      ficha.style.background = Utilidades.colorTexto(texto);
      etiqueta.textContent = texto;
    }
  }

  class ToastIU {
    constructor(selectorPila = "#toastStack") {
      this.selectorPila = selectorPila;
      this.iconos = {
        success: "✓",
        info: "i",
        warning: "!",
        danger: "×"
      };
    }

    obtenerPila() {
      let pila = DOM.uno(this.selectorPila);

      if (!pila) {
        pila = document.createElement("div");
        pila.id = this.selectorPila.replace(/^#/, "");
        pila.className = "ju-toast-stack";
        document.body.appendChild(pila);
      }

      return pila;
    }

    mostrar(tipo = "info", titulo = "Información", texto = "Operación realizada") {
      const pila = this.obtenerPila();
      const toast = document.createElement("article");

      toast.className = `ju-toast is-${tipo}`;
      toast.innerHTML = `
        <div class="ju-toast-icon">${this.iconos[tipo] || "i"}</div>
        <div>
          <p class="ju-toast-title">${titulo}</p>
          <p class="ju-toast-text">${texto}</p>
        </div>
        <button class="ju-toast-close" type="button">×</button>
      `;

      const cerrar = () => {
        toast.classList.add("is-leaving");
        setTimeout(() => toast.remove(), 240);
      };

      DOM.uno("button", toast).addEventListener("click", cerrar);
      pila.appendChild(toast);
      setTimeout(cerrar, 5000);
    }
  }

  class GestorColumnas {
    constructor() {
      this.anchoMinimo = 100;
      this.anchoMaximo = 600;
    }

    activar() {
      this.activarMinimizacion();
      this.activarSeparadores();
    }

    activarMinimizacion() {
      DOM.todos(".minimizar").forEach((boton) => {
        const columna = boton.closest("nav,section");
        columna.dataset.ancho = columna.getBoundingClientRect().width;

        boton.addEventListener("click", () => {
          this.alternarColumna(columna, boton);
        });
      });
    }

    alternarColumna(columna, boton) {
      if (columna.classList.contains("compacto")) {
        const ancho = parseFloat(columna.dataset.ancho) || 250;

        columna.classList.remove("compacto");
        columna.style.flexBasis = `${ancho}px`;
        columna.style.width = `${ancho}px`;
        boton.textContent = "◀";
        return;
      }

      columna.dataset.ancho = columna.getBoundingClientRect().width;
      columna.style.flexBasis = `${columna.dataset.ancho}px`;
      columna.style.width = `${columna.dataset.ancho}px`;

      void columna.offsetWidth;

      columna.classList.add("compacto");
      boton.textContent = "▶";
    }

    activarSeparadores() {
      DOM.todos(".separador").forEach((separador) => {
        separador.addEventListener("pointerdown", (evento) => {
          this.iniciarRedimension(separador, evento);
        });
      });
    }

    iniciarRedimension(separador, evento) {
      evento.preventDefault();

      const columna = separador.previousElementSibling;

      if (!columna || columna.classList.contains("compacto")) {
        return;
      }

      const inicio = evento.clientX;
      const anchoInicial = columna.getBoundingClientRect().width;

      columna.style.transition = "none";
      document.body.classList.add("redimensionando");
      separador.setPointerCapture(evento.pointerId);

      const mover = (eventoMovimiento) => {
        const ancho = Math.min(
          this.anchoMaximo,
          Math.max(this.anchoMinimo, anchoInicial + eventoMovimiento.clientX - inicio)
        );

        columna.style.width = `${ancho}px`;
        columna.style.flexBasis = `${ancho}px`;
        columna.dataset.ancho = ancho;
      };

      const terminar = () => {
        separador.removeEventListener("pointermove", mover);
        columna.style.transition = "";
        document.body.classList.remove("redimensionando");
      };

      separador.addEventListener("pointermove", mover);
      separador.addEventListener("pointerup", terminar, { once: true });
      separador.addEventListener("pointercancel", terminar, { once: true });
    }
  }

  class AplicacionIU {
    constructor() {
      this.estado = new EstadoIU();
      this.toast = new ToastIU();
      this.columnas = new GestorColumnas();
    }

    async iniciar() {
      try {
        [
          this.estado.menu,
          this.estado.entidades,
          this.estado.clientes,
          this.estado.formulario
        ] = await Promise.all([
          OrigenDatos.cargarJSON("data/menu.json"),
          OrigenDatos.cargarJSON("data/entidades.json"),
          OrigenDatos.cargarJSON("data/clientes.json"),
          OrigenDatos.cargarJSON("data/datos.json")
        ]);

        this.renderMenu();
        this.renderEntidades();
        this.mostrarTabla();
        this.columnas.activar();
        this.activarEventos();
      } catch (error) {
        DOM.uno("#escenario").innerHTML = `
          <div class="panel">
            <h2>No se pudieron cargar los orígenes de datos</h2>
            <p>Sirve esta carpeta mediante HTTP, por ejemplo: <code>python3 -m http.server 8000</code></p>
            <pre>${error}</pre>
          </div>
        `;
      }
    }

    renderMenu() {
      const contenedor = DOM.uno("#navegacion .contenido-columna");
      contenedor.innerHTML = "";

      this.estado.menu.forEach((elemento, indice) => {
        const nodo = DOM.uno("#tpl-nav").content.cloneNode(true);
        const enlace = DOM.uno("a", nodo);

        FichaIU.completar(enlace, elemento.texto);
        enlace.classList.toggle("activo", Boolean(elemento.activo));
        enlace.dataset.indice = indice;

        enlace.addEventListener("click", (evento) => {
          evento.preventDefault();
          DOM.todos("#navegacion a").forEach((item) => item.classList.remove("activo"));
          enlace.classList.add("activo");
          this.mostrarTabla();
        });

        contenedor.appendChild(nodo);
      });
    }

    renderEntidades() {
      const contenedor = DOM.uno("#entidades .contenido-columna");
      contenedor.innerHTML = "";

      this.estado.entidades.forEach((elemento, indice) => {
        const nodo = DOM.uno("#tpl-card").content.cloneNode(true);
        const articulo = DOM.uno("article", nodo);

        FichaIU.completar(articulo, elemento.texto);
        articulo.classList.toggle("activo", elemento.texto === this.estado.seleccion);
        articulo.dataset.indice = indice;

        articulo.addEventListener("click", () => {
          this.estado.seleccion = elemento.texto;
          this.renderEntidades();
          this.mostrarVista(elemento.texto);
        });

        articulo.addEventListener("dragstart", (evento) => {
          evento.dataTransfer.setData("text/plain", elemento.texto);
        });

        contenedor.appendChild(nodo);
      });
    }

    mostrarVista(vista) {
      if (vista === "Elemento" || vista === "Formulario") {
        this.mostrarFormularioUI();
      } else if (vista === "Login") {
        this.mostrarLoginDemo();
      } else if (vista === "Toasts") {
        this.mostrarToastsDemo();
      } else {
        this.mostrarTabla();
      }
    }

    mostrarTabla(filtro = "") {
      const datos = this.estado.clientes;
      const registros = datos.registros.filter((registro) =>
        Object.values(registro)
          .join(" ")
          .toLowerCase()
          .includes(filtro.toLowerCase())
      );
      const escenario = DOM.uno("#escenario");
      const tabla = document.createElement("table");

      escenario.innerHTML = "";
      tabla.innerHTML = `
        <caption>${datos.titulo}</caption>
        <thead>
          <tr>${datos.columnas.map((columna) => `<th>${columna.etiqueta}</th>`).join("")}</tr>
        </thead>
        <tbody></tbody>
        <tfoot>
          <tr>
            <th colspan="${Math.max(1, datos.columnas.length - 1)}">Total</th>
            <td>${registros.length}</td>
          </tr>
        </tfoot>
      `;

      const cuerpo = DOM.uno("tbody", tabla);

      registros.forEach((registro) => {
        const fila = DOM.uno("#tpl-fila").content.cloneNode(true).querySelector("tr");

        datos.columnas.forEach((columna) => {
          const celda = document.createElement("td");
          celda.textContent = registro[columna.campo] ?? "";
          fila.appendChild(celda);
        });

        cuerpo.appendChild(fila);
      });

      escenario.appendChild(tabla);
    }

    mostrarFormularioUI() {
      const escenario = DOM.uno("#escenario");

      escenario.innerHTML = `
        <article class="ju-card">
          <h2>Librería · Formularios</h2>
          <p>Controles reutilizables con el lenguaje visual jocarsa-iu.</p>
          <form id="demoForm" class="ju-form-grid">
            <label>
              <span>Nombre</span>
              <input name="nombre" required>
            </label>
            <label>
              <span>Email</span>
              <input name="email" type="email" required>
            </label>
            <label>
              <span>Tipo</span>
              <select name="tipo">
                <option>Cliente</option>
                <option>Proveedor</option>
                <option>Alumno</option>
              </select>
            </label>
            <label>
              <span>Fecha</span>
              <input name="fecha" type="date">
            </label>
            <label class="ju-form-full">
              <span>Descripción</span>
              <textarea name="descripcion"></textarea>
            </label>
            <label class="ju-check ju-form-full">
              <input type="checkbox" name="activo">
              <span>Registro activo</span>
            </label>
            <div class="ju-form-full ju-button-row">
              <button class="ju-btn" type="submit">Guardar</button>
              <button class="ju-btn secondary" type="reset">Limpiar</button>
            </div>
          </form>
        </article>
      `;

      DOM.uno("#demoForm").addEventListener("submit", (evento) => {
        evento.preventDefault();
        this.toast.mostrar(
          "success",
          "Formulario guardado",
          "El evento submit se ha capturado correctamente."
        );
      });
    }

    mostrarLoginDemo() {
      const escenario = DOM.uno("#escenario");

      escenario.innerHTML = `
        <div class="ju-login-layout" style="margin:auto">
          <article class="ju-login-side">
            <div class="ju-login-brand">
              <img src="https://static.jocarsa.com/logos/white.svg" alt="">
              <h1 class="ju-login-product-title">jocarsa | interfaz integrada</h1>
              <p>Pantalla de acceso basada en jocarsa-iu.</p>
            </div>
          </article>
          <article class="ju-login-card">
            <h2>Iniciar sesión</h2>
            <form id="loginDemo" class="ju-form-grid">
              <label class="ju-form-full">
                <span>Email</span>
                <input type="email" required>
              </label>
              <label class="ju-form-full">
                <span>Contraseña</span>
                <input type="password" required>
              </label>
              <label class="ju-check ju-form-full">
                <input type="checkbox">
                <span>Mantener sesión</span>
              </label>
              <div class="ju-form-full ju-login-actions">
                <button class="ju-btn">Entrar</button>
                <a class="ju-text-link" href="login.html">Abrir pantalla completa</a>
              </div>
            </form>
          </article>
        </div>
      `;

      DOM.uno("#loginDemo").addEventListener("submit", (evento) => {
        evento.preventDefault();
        this.toast.mostrar(
          "success",
          "Acceso simulado",
          "Login capturado como componente reutilizable."
        );
      });
    }

    mostrarToastsDemo() {
      const escenario = DOM.uno("#escenario");

      escenario.innerHTML = `
        <article class="ju-card">
          <h2>Librería · Toasts</h2>
          <p>Mensajes emergentes reutilizables con autocierre y cierre manual.</p>
          <div class="ju-button-row">
            <button class="ju-btn" data-t="success">Éxito</button>
            <button class="ju-btn secondary" data-t="info">Información</button>
            <button class="ju-btn secondary" data-t="warning">Aviso</button>
            <button class="ju-btn secondary" data-t="danger">Error</button>
          </div>
        </article>
      `;

      const titulos = {
        success: "Correcto",
        info: "Información",
        warning: "Revisar",
        danger: "Error"
      };

      DOM.todos("[data-t]", escenario).forEach((boton) => {
        boton.addEventListener("click", () => {
          this.toast.mostrar(
            boton.dataset.t,
            titulos[boton.dataset.t],
            "Ejemplo de mensaje emergente de la librería."
          );
        });
      });
    }

    activarEventos() {
      DOM.uno("#buscador").addEventListener("input", (evento) => {
        this.mostrarTabla(evento.target.value);
      });

      DOM.uno("#nuevo").addEventListener("click", () => {
        this.estado.seleccion = "Elemento";
        this.renderEntidades();
        this.mostrarFormularioUI();
      });

      const escenario = DOM.uno("#escenario");

      escenario.addEventListener("dragover", (evento) => {
        evento.preventDefault();
        escenario.classList.add("drag-over");
      });

      escenario.addEventListener("dragleave", () => {
        escenario.classList.remove("drag-over");
      });

      escenario.addEventListener("drop", (evento) => {
        evento.preventDefault();
        escenario.classList.remove("drag-over");

        const vista = evento.dataTransfer.getData("text/plain");
        this.estado.seleccion = vista;
        this.renderEntidades();
        this.mostrarVista(vista);
      });

      document.addEventListener("keydown", (evento) => {
        if (evento.key === "Escape") {
          this.mostrarTabla();
        }
      });
    }
  }

  window.jocarsa.iu.DOM = DOM;
  window.jocarsa.iu.Utilidades = Utilidades;
  window.jocarsa.iu.OrigenDatos = OrigenDatos;
  window.jocarsa.iu.EstadoIU = EstadoIU;
  window.jocarsa.iu.FichaIU = FichaIU;
  window.jocarsa.iu.ToastIU = ToastIU;
  window.jocarsa.iu.GestorColumnas = GestorColumnas;
  window.jocarsa.iu.AplicacionIU = AplicacionIU;

  document.addEventListener("DOMContentLoaded", () => {
    window.jocarsa.iu.aplicacion = new AplicacionIU();
    window.jocarsa.iu.aplicacion.iniciar();
  });
})();
