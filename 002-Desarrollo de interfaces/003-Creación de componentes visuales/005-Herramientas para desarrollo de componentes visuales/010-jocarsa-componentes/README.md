# jocarsa | componentes

Librería CSS/JS sin dependencias, inspirada en el patrón jocarsa-iu de Habla.

## Uso

```html
<link rel="stylesheet" href="jocarsa-componentes-ampliado.css">
<script src="jocarsa-componentes-ampliado.js" defer></script>
```

Los controles HTML originales se conservan en el DOM: mantienen sus `name`, `id`, `value`, validaciones, envío `FormData` y eventos. Se mejoran visualmente los tipos de texto, fecha, hora, mes, semana, número, rango, color, archivo, textarea, select, checkbox, radio, botones, output, meter y progress.

### Multiselector

```html
<select name="lenguajes[]" multiple data-placeholder="Buscar lenguaje">
  <optgroup label="Backend"><option>Python</option><option>PHP</option></optgroup>
  <option>JavaScript</option>
</select>
```

Se envía cada opción seleccionada como valor del select nativo. El filtro busca en el texto de las opciones.

### Botonera deslizante

```html
<div data-jc-segmented data-name="periodo" data-value="mes" data-storage-key="periodo-demo">
  <button data-valor="año">Año</button>
  <button data-valor="mes">Mes</button>
  <button data-valor="semana">Semana</button>
</div>
```

`data-storage-key` es opcional. Se emite `jc:change` con `event.detail.value`. `data-name` genera un campo hidden para el formulario.

### Inicialización de contenido dinámico

```js
JocarsaComponentes.init(document.querySelector('#contenedor'));
```

La inicialización es idempotente. Variables CSS `--tono`, `--saturacion`, `--brillo`, `--escalon`, `--chaflan` y `--relleno` compatibles con jocarsa-iu.

## Nota

Los selectores de fecha y hora emplean el control nativo del navegador para mantener accesibilidad, teclado, idioma y validación. El aspecto del calendario desplegable depende del navegador/sistema operativo. Los `select[multiple]` tienen representación personalizada, pero el elemento select original permanece como fuente de verdad.
