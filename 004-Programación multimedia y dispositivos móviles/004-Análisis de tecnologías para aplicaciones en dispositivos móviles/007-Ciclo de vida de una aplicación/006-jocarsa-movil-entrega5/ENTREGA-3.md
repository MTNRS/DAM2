# Entrega 3 — Offline y actualizaciones

- `sw.js`: precache de todos los archivos locales, fallback sin red y limpieza de versiones antiguas.
- `offline.js`: registro del SW, estado de conexión y comprobación de actualizaciones.
- `vistas/offline.html` + `controladores/offline.js`: pantalla de diagnóstico y actualización manual.
- El SW normaliza las URLs de vistas/plantillas/controladores quitando parámetros `?t=` y permite reutilizar caché.
- Las peticiones a APIs externas no se cachean. Los archivos remotos (p.ej. logo y fuente de Google) no están disponibles sin red si no los tenía ya el navegador.
- El contenido local se precachea en la primera visita; requiere HTTPS o localhost.
- Para publicar una nueva versión, incrementar `VERSION` en `sw.js` y actualizar archivos. El usuario podrá instalarla desde Offline.
- La actualización es automática en detección, pero se pide confirmación para activarla y evitar perder trabajo en formularios.
- No se incluye sincronización de datos offline con el servidor: fase posterior.
