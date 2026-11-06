# Integración aditiva del motor de formularios V2

- Archivo nuevo: `frontend/js/crimson-form-engine.js`.
- `frontend/index.html` carga el motor antes de `js/erp.js`.
- `frontend/js/erp.js` conserva el formulario anterior como alternativa si falla el nuevo renderizado.
- Se reutilizan los componentes Habla V5 que ya están incluidos en el proyecto (`jocarsa-ui.js`, `jocarsa-extension.js`).
- Los metadatos llegan por la API `bloque=tabla`, sin cambiar la persistencia ni las comprobaciones de permisos.
- 36 tipos de la demo mapeados, además de alias SQL. Se conserva el orden, secciones y etiquetas personalizados.
- FK: se mantienen las opciones recuperadas desde `fk_opciones`.
- Serialización: checkbox, multiselección, punto, JSON y valores nativos.

## Límites intencionados de seguridad

- BINARIO/BINARIO_LARGO: se muestra selector de archivos, pero se bloquea el guardado si hay un archivo seleccionado hasta implementar un endpoint binario seguro. No se finge una subida ni se guarda únicamente el nombre.
- PROGRESO y MEDIDOR: visualización, no se envían modificaciones.
- Los componentes enriquecidos de Habla pueden requerir adaptar su serialización a los formatos de almacenamiento del backend. Probar antes de habilitarlos en producción.
- Los metadatos del motor pueden contener tipos nuevos; el control vuelve a texto si no reconoce uno.

## Verificaciones

- Sintaxis JavaScript comprobada con `node --check`.
- Pendiente: pruebas end-to-end con PHP/Apache y motor V2 real, permisos, edición, alta, FK, customFields y navegadores.
- Recomendado: desplegar primero en entorno de pruebas y conservar copia del proyecto anterior.
