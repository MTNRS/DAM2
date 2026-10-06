# Proyecto integrado — subunidad 007

Proyecto que toma como base visual/funcional la evolución de la subunidad 007 y reincorpora conceptos de las subunidades anteriores:

- **005 — Componentes**: `template`, clonación, componentes de navegación, fichas y tabla, CSS/JS separados.
- **006 — Orígenes de datos**: carga con `fetch()` de `menu.json`, `entidades.json`, `clientes.json`, `datos.json` y `productos.json`; formulario generado desde JSON y tabla generada desde JSON.
- **007 — Eventos**: click, input, submit, reset, drag & drop, pointer events para redimensionar, teclado y minimizar/restaurar columnas.

## Ejecutar

Al usar `fetch()`, no abras `index.html` directamente con `file://`. Desde la carpeta del proyecto:

```bash
python3 -m http.server 8000
```

Y abre `http://localhost:8000`.

## Estructura

- `index.html`: estructura y templates base.
- `css/estilo.css`: estilo jocarsa-iu.
- `js/componentes.js`: renderizado y eventos.
- `data/`: orígenes de datos JSON recuperados de las prácticas previas.
- `templates/`: templates reutilizables preparados para seguir separando componentes.
