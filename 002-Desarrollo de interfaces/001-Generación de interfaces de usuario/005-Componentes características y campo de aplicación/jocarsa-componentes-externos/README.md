# jocarsa | componentes externos

Ejemplo de componentización donde HTML, CSS, JavaScript, templates y datos JSON
están separados en archivos externos.

## Estructura

- `index.html`
- `css/estilo.css`
- `js/componentes.js`
- `templates/*.html`
- `data/*.json`

Los componentes se cargan mediante `fetch()`, por lo que debe ejecutarse desde
un servidor HTTP y no abriendo `index.html` directamente con `file://`.

Por ejemplo:

```bash
python3 -m http.server 8000
```

Después abre `http://localhost:8000`.
