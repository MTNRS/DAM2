// Motor de componentes: cada etiqueta ui-* usa templates/<nombre>.html
const Componentes = (() => {
  async function plantilla(nombre) {
    if (!/^[a-z][a-z0-9-]*$/.test(nombre)) throw new Error('Componente inválido');
    const respuesta = await fetch(`templates/${nombre}.html?t=${Date.now()}`, {cache:'no-store'});
    if (!respuesta.ok) throw new Error(`Falta templates/${nombre}.html`);
    return respuesta.text();
  }
  function escapar(valor) {
    return String(valor ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
  }
  function atributos(nodo) {
    return [...nodo.attributes].filter(a => !['label','placeholder','columnas','class','type'].includes(a.name))
      .map(a => `${a.name}="${escapar(a.value)}"`).join(' ');
  }
  async function renderizar(raiz) {
    // Renderizar primero los componentes hijos; después el padre.
    async function transformar(nodo) {
      for (const hijo of [...nodo.children]) await transformar(hijo);
      if (!nodo.localName.startsWith('ui-')) return;
      const nombre = nodo.localName.slice(3);
      let html = await plantilla(nombre);
      const columnas = (nodo.getAttribute('columnas') || '').split(',').filter(Boolean);
      const variables = {
        contenido: nodo.innerHTML,
        attrs: atributos(nodo),
        class: escapar(nodo.getAttribute('class') || ''),
        label: escapar(nodo.getAttribute('label') || ''),
        placeholder: escapar(nodo.getAttribute('placeholder') || ''),
        type: escapar(nodo.getAttribute('type') || (nombre === 'boton' ? 'button' : 'text')),
        cabeceras: columnas.map(c => `<th>${escapar(c.trim())}</th>`).join('')
      };
      html = html.replace(/{{(\w+)}}/g, (_, clave) => variables[clave] ?? '');
      const fragmento = document.createRange().createContextualFragment(html);
      nodo.replaceWith(fragmento);
    }
    for (const hijo of [...raiz.children]) await transformar(hijo);
  }
  return {renderizar};
})();
