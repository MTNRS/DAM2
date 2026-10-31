# Integración de 013-jocarsa-componentes v5 en 005-Habla

Los módulos **Entradas v5** y **Reporting v5** aparecen ahora como dos elementos de la segunda columna de la aplicación principal. Se eliminan los accesos adicionales de la cabecera.

Al pulsar un elemento se carga la demostración correspondiente en el escenario central sin cambiar de página, conservando navegación, buscador, asistente local, modales y paneles existentes.

Las demos utilizan un iframe de aislamiento CSS/JavaScript para impedir que la librería v5 modifique controles de la aplicación anfitriona; el iframe no contiene otra copia de la navegación de Habla. Los colores, chaflanes, fondos, tipografía y espaciado se ajustan a jocarsa-iu.

Se conservan las páginas de demo autónomas y todos los archivos originales para compatibilidad.

Iniciar: `python3 -m http.server 8000` y abrir `/index.html`.


## v4 - Menús desplegables y vistas de tablas
Menús responsive plegables, botones a anchura completa y vistas Tabla/Tarjetas/Árbol. Las tablas originales permanecen en DOM; la vista móvil por defecto es Tarjetas. Las acciones complejas se abren en la tabla original. Los datos asíncronos se actualizan mediante MutationObserver.

## v5 — Accesibilidad por teclado
- Nuevo `css/teclado.css` y `js/teclado.js` sin sustituir los scripts originales.
- Enlace visible al recibir foco para saltar al contenido; logo de cabecera operable con Enter/Espacio.
- Indicador de foco de alto contraste y navegación con flechas/Home/End en menús.
- Tarjetas dinámicas de entidades enfocables y activables con Enter/Espacio.
- Títulos descriptivos por página.
- Pendiente: auditoría manual de todos los modales, operaciones de arrastre y controles de terceros para garantizar operabilidad completa con teclado.


## v9 — Tamaño de letra y mayúsculas

Se añaden al panel un ampliador tipográfico independiente (100–200 %) y un switch de mayúsculas. Las preferencias se guardan en localStorage. Se conservan todos los archivos de v8.
