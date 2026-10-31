# JOCARSA · Contenido por endpoints

- `endpoints.js`: mapa central de rutas editable.
- `api/*.json`: contenido inicial separado por sección.
- `front.js`: catálogo de productos y megamenú.
- `secciones.js`: carga las demás secciones sin alterar su diseño.
- `index.html`: incorpora los nuevos scripts.

Ejecuta `python3 -m http.server 8000` dentro de esta carpeta y abre http://localhost:8000. No funciona con `file://` debido a `fetch`.

Los clientes y testimonios del diseño son demostrativos y no deben presentarse como referencias verificadas. Las rutas de alias `marcas-clientes` y `soluciones-ejemplos` se incluyen para su uso futuro.
