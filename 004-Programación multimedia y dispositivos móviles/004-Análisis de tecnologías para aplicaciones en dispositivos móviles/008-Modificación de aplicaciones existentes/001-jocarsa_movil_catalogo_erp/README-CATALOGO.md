# jocarsa | móvil — catálogo ERP

La vista **Productos+** consulta la API de productos mediante JavaScript. Configura URL y clave en `endpoints.js` (ambas son públicas). Los resultados se buscan y filtran por categoría.

El archivo de origen era un reporte Markdown de código y no incluía los archivos binarios originales de iconos. Se ha incluido un icono SVG provisional y un manifiesto funcional.

## Ejecución

Servir mediante Apache o `python3 -m http.server 8000`. Abrir `http://localhost:8000`.

En un teléfono, `localhost` apunta al propio teléfono, no al PC. Sustituye `localhost` en `endpoints.js` por un hostname/IP del servidor accesible desde el dispositivo, configura CORS en la API y usa HTTPS si publicas la PWA.

La API debe aceptar `X-API-Key` y devolver `{ "data": [{"nombre":"...","descripcion":"...","categoria":"1"}] }`. La clave es visible en el cliente, por decisión expresa para esta fase de pruebas.
