# V18 — CSV y consistencia cromática

- Exportar CSV de registros filtrados, con todas las columnas reales y valores de origen (no etiquetas de FK).
- Descargar plantilla CSV con columnas insertables, sin Identificador.
- Importar CSV UTF-8 con delimitadores punto y coma o coma, comillas y saltos de línea escapados. Inserción mediante API Crimson y permisos existentes.
- El importador informa de errores por fila; no realiza una transacción global ni deshace filas anteriores.
- Evita pestañas duplicadas al no aplicar el adaptador genérico de vistas a tablas del ERP.
- Unifica colores de tabla, botones y campos con SaddleBrown.

**Nota:** Las referencias FK deben importarse usando sus identificadores, no sus nombres visibles.
