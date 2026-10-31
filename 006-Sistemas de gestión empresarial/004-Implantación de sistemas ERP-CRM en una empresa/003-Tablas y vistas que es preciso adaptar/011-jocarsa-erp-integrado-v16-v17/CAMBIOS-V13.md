# Versión 13 — Acciones con parámetros y plantillas

- Editor de parámetros declarativos por acción: nombre, etiqueta, tipo y obligatoriedad.
- Diálogo de parámetros reutilizable para acciones por registro y en lote.
- Impresión con título y plantilla de texto; interpolación segura de campos y parámetros.
- Actualización de campo usando opcionalmente el primer parámetro del formulario.
- Configuración compartida existente: `crimson_ui_config` en jocarsa-bbdd a través de Crimson.
- Sin ejecución arbitraria de código HTML/JavaScript en plantillas.
- No se modifican los contratos de la API de escritura.

**Limitación:** la plantilla es de texto con saltos de línea y variables, no un editor WYSIWYG ni un motor HTML libre. La ejecución en lote sigue siendo secuencial.
