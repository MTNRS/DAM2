# Integración de 013-jocarsa-componentes v5 en 005-Habla

Los módulos **Entradas v5** y **Reporting v5** aparecen ahora como dos elementos de la segunda columna de la aplicación principal. Se eliminan los accesos adicionales de la cabecera.

Al pulsar un elemento se carga la demostración correspondiente en el escenario central sin cambiar de página, conservando navegación, buscador, asistente local, modales y paneles existentes.

Las demos utilizan un iframe de aislamiento CSS/JavaScript para impedir que la librería v5 modifique controles de la aplicación anfitriona; el iframe no contiene otra copia de la navegación de Habla. Los colores, chaflanes, fondos, tipografía y espaciado se ajustan a jocarsa-iu.

Se conservan las páginas de demo autónomas y todos los archivos originales para compatibilidad.

Iniciar: `python3 -m http.server 8000` y abrir `/index.html`.


## v4 - Menús desplegables y vistas de tablas
Menús responsive plegables, botones a anchura completa y vistas Tabla/Tarjetas/Árbol. Las tablas originales permanecen en DOM; la vista móvil por defecto es Tarjetas. Las acciones complejas se abren en la tabla original. Los datos asíncronos se actualizan mediante MutationObserver.
