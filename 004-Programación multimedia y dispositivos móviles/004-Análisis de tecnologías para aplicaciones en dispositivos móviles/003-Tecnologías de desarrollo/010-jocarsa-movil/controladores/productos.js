let botonanadeproductos = document.querySelector("#anadeproducto");

// Función para mostrar los productos
function mostrarProductos() {
    let basededatos = localStorage.getItem("productos")
        ? JSON.parse(localStorage.getItem("productos"))
        : [];

    let tabla = document.querySelector("#tablaproductos");
    tabla.innerHTML = "";

    basededatos.forEach(function(producto, indice) {
        let fila = document.createElement("tr");

        let celdaId = document.createElement("td");
        celdaId.textContent = indice + 1;

        let celdaProducto = document.createElement("td");
        celdaProducto.textContent = producto;

        fila.appendChild(celdaId);
        fila.appendChild(celdaProducto);
        tabla.appendChild(fila);
    });
}

// Añadir producto
botonanadeproductos.onclick = function() {
    let valor = document.querySelector("#nombreproducto").value.trim();

    if (valor === "") return;

    let basededatos = localStorage.getItem("productos")
        ? JSON.parse(localStorage.getItem("productos"))
        : [];

    basededatos.push(valor);

    localStorage.setItem("productos", JSON.stringify(basededatos));

    document.querySelector("#nombreproducto").value = "";

    mostrarProductos();
};

// Mostrar productos al cargar la vista
mostrarProductos();