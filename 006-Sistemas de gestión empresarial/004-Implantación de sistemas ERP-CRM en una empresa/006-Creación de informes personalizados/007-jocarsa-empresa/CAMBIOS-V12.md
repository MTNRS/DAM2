# V12 · Configuración global compartida

- Configuraciones, reglas, columnas, acciones y vistas guardadas en la tabla `crimson_ui_config` de jocarsa-bbdd mediante Crimson.
- Lectura autenticada y limitada a entidades accesibles; escritura exclusivamente para administradores.
- La tabla de sistema se crea al guardar por primera vez.
- Las estrellas de favoritos continúan siendo personales en localStorage; no forman parte de la configuración global.
- No se migran automáticamente preferencias antiguas del navegador.
- El motor de acciones existente se conserva; los formularios de parámetros y plantillas de impresión siguen pendientes.
