const formulario = contenedor.querySelector('#formulario-productos');
const campo = contenedor.querySelector('#nombreproducto');
const tabla = contenedor.querySelector('#tabla-productos tbody');
function leer() {
  try { const datos = JSON.parse(localStorage.getItem('productos') || '[]'); return Array.isArray(datos) ? datos : []; }
  catch { return []; }
}
function mostrar() {
  tabla.replaceChildren();
  leer().forEach((producto, indice) => {
    const fila = document.createElement('tr');
    [indice + 1, producto].forEach(valor => {
      const celda = document.createElement('td');
      celda.textContent = valor;
      fila.appendChild(celda);
    });
    tabla.appendChild(fila);
  });
}
function agregar(evento) {
  evento.preventDefault();
  const valor = campo.value.trim();
  if (!valor) return;
  const datos = leer(); datos.push(valor);
  localStorage.setItem('productos', JSON.stringify(datos));
  campo.value = ''; mostrar();
}
formulario.addEventListener('submit', agregar);
mostrar();
return () => formulario.removeEventListener('submit', agregar);
