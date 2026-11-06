# Integración v4

- El asistente flotante recupera el controlador original de jocarsa-iu, aislado en `frontend/js/asistente-iu.js` para no iniciar la aplicación de demostración. Incluye arrastre, minimización, restauración, redimensión, voz y envío.
- Login reconstruido usando el HTML y las clases de la plantilla original jocarsa-iu, conservando autenticación PHP de Crimson.
- No se ha cambiado la lógica del ERP ni su adaptador a jocarsa-bbdd.

## Nota
La respuesta de Ollama original escribe en `#escenario`; esta característica original podría sustituir temporalmente la vista de tabla ERP al utilizar la IA. No se ha modificado la lógica de IA en esta revisión de interfaz.
