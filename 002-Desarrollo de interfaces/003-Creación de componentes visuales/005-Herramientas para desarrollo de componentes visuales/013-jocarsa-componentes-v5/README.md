# jocarsa | componentes v5.1

## Instalación

Incluye los archivos en este orden:

```html
<link rel="stylesheet" href="jocarsa-iu.css">
<script defer src="jocarsa-ui.js"></script>
<script defer src="jocarsa-extension.js"></script>
```

Abre `demo.html` para ver el catálogo y la salida real de `FormData`.

## Diseño y compatibilidad

- Los controles de texto y números admiten la etiqueta `jocarsa-input` con `type="nombre"`, `email`, `integer`, `moneda`, `porcentaje`, etc. y presentan el indicador semántico a la derecha, como en la versión anterior. Internamente se usa un `input` nativo.
- `input[type=date|time|datetime-local|month|week]` muestra **únicamente** un botón de visualización que abre un minimodal propio. El `input` nativo sigue existiendo, conserva su `name`, `required`, `min` y `max`, y almacena valores HTML estándar (`YYYY-MM-DD`, `HH:MM`, `YYYY-MM-DDTHH:MM`, `YYYY-MM`, `YYYY-Www`).
- `input[type=checkbox]` y `input[type=radio]` siguen siendo inputs nativos con presentación switch y radio animado.
- `select[multiple]` se transforma visualmente en **select-multiple-datalist**: buscador, selección múltiple y chips eliminables. El `select` real conserva `name`, `value` y las opciones seleccionadas para el envío.
- `.jocarsa-segment` es una botonera de selección única deslizante, con un campo oculto estándar para el formulario.
- Componentes extendidos heredados de v4.1: rich text, Markdown, editor de código, firma, DataTable, TreeView, calendarios y reloj.

## API

`window.JocarsaEnhance.init(contenedor)` permite mejorar controles añadidos dinámicamente. `window.Jocarsa.iniciar()` inicializa los componentes personalizados originales en el documento.

## Nota

El modal utiliza controles personalizados para seleccionar la fecha, pero mantiene la validación nativa. Las restricciones `min` y `max` se comprueban al enviar mediante el `input` original; la interfaz del modal todavía no deshabilita preventivamente los días fuera de rango. Los editores y la firma proceden de v4.1 y requieren revisión adicional antes de usos críticos.
