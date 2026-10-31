# jocarsa | componentes v5

Abrir `demo.html`. Integración: cargar `jocarsa-iu.css`, `jocarsa-ui.js` y `jocarsa-extension.js` en ese orden.

- Inputs HTML originales conservados: switches y radios son CSS sobre el input nativo.
- Date/time/datetime-local/month/week: botón adicional abre minimodal propio, manteniendo el input real.
- `jocarsa-texto`, `jocarsa-markdown`, `jocarsa-editor`, `jocarsa-firma`: componentes de formulario del proyecto v4.1.
- `jocarsa-tabla`, `jocarsa-arbol`, `jocarsa-calendario`, `jocarsa-semana`, `jocarsa-hora`: informes y selectores del proyecto v4.1.
- `.jocarsa-segment` convierte una botonera con `data-valor` en selector animado; `name` crea valor de formulario, `data-storage` permite persistencia.

**Limitaciones:** el multiselect del demo es el control nativo; no se ha reconstruido el buscador multiselect de la primera librería. El editor rico heredado utiliza `document.execCommand`, obsoleto. La firma heredada no preserva el trazo al redimensionar. El selector semanal personalizado necesita pruebas de límites de año ISO.
