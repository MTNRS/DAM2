# Versión 8 — Acceso al personalizador

- Corregida la plantilla HTML generada por `render()` en `frontend/js/erp.js`: faltaban los elementos `#erp-config`, `#erp-save-view` y `#erp-views`, aunque ya se les asignaban eventos.
- Añadido **Personalizar** en la barra de la entidad, junto a **Actualizar**.
- Añadidos selector de vistas guardadas y botón **Guardar vista**.
- Se conservan la interfaz, el CRUD, las preferencias locales y el backend sin cambios.

## Prueba

1. Abrir una entidad.
2. Pulsar **Personalizar**, cambiar columnas y guardar.
3. Pulsar **Guardar vista**, darle nombre y comprobar que aparece en **Vistas guardadas**.
4. Cambiar configuración y recuperar la vista guardada.

Las preferencias siguen siendo locales a este navegador.
