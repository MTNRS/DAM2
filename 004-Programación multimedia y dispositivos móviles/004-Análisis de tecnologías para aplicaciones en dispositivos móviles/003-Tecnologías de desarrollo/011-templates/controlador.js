// Controlador principal de navegación
// Vistas y controladores siempre actualizados.

const contenedor = document.querySelector("#contenedor");

let navegacion = 0;
let limpiezaActual = null;

async function cargarVista(nombre) {

    if (!/^[a-z0-9_-]+$/i.test(nombre)) {
        return;
    }

    const actual = ++navegacion;

    try {

        // 1. Cargar vista sin caché

        const respuesta = await fetch(
            `vistas/${nombre}.html?t=${Date.now()}`,
            {
                cache: "no-store"
            }
        );

        if (!respuesta.ok) {
            throw new Error("Vista no encontrada");
        }

        const html = await respuesta.text();

        if (actual !== navegacion) {
            return;
        }

        // 2. Crear contenedor temporal

        const temporal = document.createElement("div");

        temporal.innerHTML = html;

        // 3. Renderizar los componentes HTML

        await Componentes.renderizar(temporal);

        if (actual !== navegacion) {
            return;
        }

        // 4. Limpiar controlador anterior

        if (limpiezaActual) {

            limpiezaActual();

            limpiezaActual = null;
        }

        // 5. Mostrar nueva vista

        contenedor.replaceChildren(
            ...temporal.childNodes
        );

        // 6. Actualizar navegación activa

        document.querySelectorAll("[data-url]")
            .forEach(boton => {

                boton.classList.toggle(
                    "activo",
                    boton.dataset.url === nombre
                );

            });

        // 7. Cargar controlador de la vista sin caché

        const modulo = await fetch(
            `controladores/${nombre}.js?t=${Date.now()}`,
            {
                cache: "no-store"
            }
        );

        if (actual !== navegacion) {
            return;
        }

        if (modulo.ok) {

            const codigo = await modulo.text();

            if (actual !== navegacion) {
                return;
            }

            // Ejecutar controlador de la vista

            const iniciar = new Function(
                "contenedor",
                codigo
            );

            const limpiar = iniciar(contenedor);

            if (typeof limpiar === "function") {
                limpiezaActual = limpiar;
            }
        }

    } catch (error) {

        if (actual === navegacion) {

            console.error(error);

            contenedor.textContent =
                "Error al cargar la vista";
        }
    }
}

// Navegación mediante delegación de eventos

document.addEventListener("click", evento => {

    const boton = evento.target.closest("[data-url]");

    if (boton) {

        evento.preventDefault();

        cargarVista(boton.dataset.url);
    }

});

// Cargar vista inicial

cargarVista("inicio");