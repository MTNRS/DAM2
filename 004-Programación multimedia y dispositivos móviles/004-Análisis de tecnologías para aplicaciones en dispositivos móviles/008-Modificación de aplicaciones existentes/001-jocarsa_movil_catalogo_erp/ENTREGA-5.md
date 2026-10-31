# Entrega 5 — APIs del dispositivo

Se añade `dispositivo.js` con una API reutilizable y una vista `dispositivo` con demostraciones de cámara, captura de foto, grabación de audio, geolocalización, lectura de archivos, consulta de permisos, compartir y portapapeles.

## Seguridad y compatibilidad

- Servir por HTTPS o localhost. Las APIs dependen del navegador y del dispositivo.
- La cámara y el micrófono se solicitan mediante botones y se detienen al salir de la vista.
- Las fotos, grabaciones, archivos y coordenadas se mantienen en el dispositivo: no se envían a un servidor.
- La consulta Permissions API puede devolver «no consultable» aunque la API funcione.
- El micrófono usa el formato soportado por MediaRecorder (WebM, MP4 u Ogg según navegador).
- El Service Worker precachea la vista y los módulos de la Entrega 5; las APIs físicas no funcionan necesariamente offline en todos los dispositivos.

## Prueba

1. Servir el proyecto desde HTTPS o localhost.
2. Abrir Dispositivo y probar los permisos uno a uno.
3. Probar en Android y Safari iOS; cerrar la cámara antes de cambiar de app.
4. En modo avión, comprobar que la vista carga desde la caché tras instalar la nueva versión del Service Worker.
