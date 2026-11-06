const form = contenedor.querySelector('#demo-form');
const estado = contenedor.querySelector('#demo-estado');
const resultado = contenedor.querySelector('#demo-resultado');
const clave = 'jocarsa_movil_formularios_demo';
function valores() {
  const datos = {};
  const fd = new FormData(form);
  for (const [nombre, valor] of fd.entries()) {
    const limpio = valor instanceof File ? (valor.name || '') : valor;
    if (nombre in datos) datos[nombre] = [].concat(datos[nombre], limpio);
    else datos[nombre] = limpio;
  }
  for (const control of form.querySelectorAll('input[type="checkbox"]')) datos[control.name] = control.checked;
  return datos;
}
function mostrar(datos) { resultado.textContent = JSON.stringify(datos, null, 2); }
function cambio(evento) {
  const input = evento.target;
  if (input.matches('input[type="range"]')) input.closest('.ui-range-line')?.querySelector('output')?.replaceChildren(document.createTextNode(input.value));
  if (input.matches('.ui-rating input')) {
    const grupo = input.closest('.ui-rating');
    grupo.querySelectorAll('label').forEach(label => label.classList.toggle('seleccionado', Number(label.querySelector('input').value) <= Number(input.value)));
  }
}
function guardar(evento) {
  evento.preventDefault();
  if (!form.reportValidity()) return;
  const datos = valores();
  try { localStorage.setItem(clave, JSON.stringify(datos)); estado.textContent = 'Datos guardados localmente.'; }
  catch { estado.textContent = 'No se pudo guardar en este dispositivo.'; }
  mostrar(datos);
}
function recuperar() {
  let datos;
  try { datos = JSON.parse(localStorage.getItem(clave) || 'null'); } catch { datos = null; }
  if (!datos) { estado.textContent = 'No hay datos guardados.'; return; }
  for (const control of form.elements) {
    if (!control.name || control.disabled || control.type === 'file') continue;
    const valor = datos[control.name];
    if (valor === undefined) continue;
    if (control.type === 'checkbox') control.checked = Boolean(valor);
    else if (control.type === 'radio') control.checked = control.value === valor;
    else if (control.tagName === 'SELECT' && control.multiple) [...control.options].forEach(o => o.selected = [].concat(valor).includes(o.value));
    else control.value = valor;
    control.dispatchEvent(new Event('input', {bubbles:true}));
  }
  mostrar(datos); estado.textContent = 'Datos recuperados. Los archivos deben seleccionarse de nuevo.';
}
function resetear() { requestAnimationFrame(() => { form.querySelectorAll('input[type="range"]').forEach(i => i.dispatchEvent(new Event('input', {bubbles:true}))); form.querySelectorAll('.ui-rating label').forEach(l => l.classList.remove('seleccionado')); estado.textContent = 'Formulario restablecido.'; }); }
const boton = contenedor.querySelector('#demo-recuperar');
form.addEventListener('submit', guardar);
form.addEventListener('input', cambio);
form.addEventListener('reset', resetear);
boton.addEventListener('click', recuperar);
form.querySelectorAll('input[type="range"]').forEach(i => i.dispatchEvent(new Event('input', {bubbles:true})));
return () => { form.removeEventListener('submit', guardar); form.removeEventListener('input', cambio); form.removeEventListener('reset', resetear); boton.removeEventListener('click', recuperar); };
