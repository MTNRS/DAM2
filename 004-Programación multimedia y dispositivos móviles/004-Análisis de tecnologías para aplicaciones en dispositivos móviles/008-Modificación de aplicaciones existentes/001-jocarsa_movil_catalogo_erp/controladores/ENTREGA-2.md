# Entrega 2 — Instalador PWA

Incluye íntegra la Entrega 1. Se incorpora `instalador.js`, la vista `vistas/instalacion.html`, su controlador y la pestaña **Instalar** en el menú inferior.

## Pruebas

1. Servir el proyecto mediante HTTPS o localhost.
2. Abrir **Instalar**.
3. En Chrome/Edge compatibles, cuando se emita `beforeinstallprompt`, aparece el botón para mostrar el diálogo nativo.
4. En iOS se muestran instrucciones para añadir a pantalla de inicio.
5. Tras abrir la app desde el icono instalado, aparece el estado instalado.
6. Confirmar que el splash inicial y todas las vistas anteriores siguen funcionando.

**Importante:** `beforeinstallprompt` no está disponible en todos los navegadores y no se puede forzar. El modo offline y el Service Worker todavía no están implementados.
