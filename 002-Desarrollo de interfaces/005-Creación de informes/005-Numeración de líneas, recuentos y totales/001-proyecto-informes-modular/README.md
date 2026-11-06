# Motor de informes PHP ampliado

Proyecto completo con motor de documentos, API JSON, 16 componentes de texto, 15 tabulares, gráficas e indicadores.

- Abre `index.php` en tu servidor PHP para visualizar el ejemplo.
- Envía un POST JSON a `api.php` para generar un informe HTML.
- Ejecuta `php generar.php ejemplo-completo.json informe.html` para generar desde CLI.
- Lee `DOCUMENTACION.md` para conocer las propiedades y ejemplos.

El enlace CSS es **relativo**: `estilos.css`. Mantén el CSS junto al HTML final.


## Versión modular

Cada clase reside en su propio archivo PHP, distribuido según la jerarquía de herencia en `src/`. `bootstrap.php` centraliza los `require_once` mediante un autocargador. Para usar el motor desde otra aplicación: `require_once __DIR__ . '/bootstrap.php';`.

Las tablas admiten `numerar_filas`, `mostrar_recuento` y `totales` (índices de columnas empezando por cero). Véase `DOCUMENTACION.md` y la última página de `ejemplo-completo.json`.
