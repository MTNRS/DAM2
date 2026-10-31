# Entrega 4 — IndexedDB y sincronización

## Módulos
- `almacenamiento.js`: almacén clave/valor y cola IndexedDB, aislados de Cache Storage.
- `sincronizacion.js`: cola explícita de escrituras JSON hacia endpoints del mismo origen, reintentos con espera exponencial, errores HTTP 4xx en estado `error`, cabecera `Idempotency-Key`.
- `vistas/sincronizacion.html` y `controladores/sincronizacion.js`: interfaz de prueba y gestión.

## Uso desde otra vista
```js
await JocarsaStorage.guardar('borrador:123', {titulo:'Mi borrador'});
const borrador = await JocarsaStorage.leer('borrador:123');
await JocarsaSync.encolar('/api/entregas.php', {method:'POST', body:{id:123,contenido:'Texto'}});
await JocarsaSync.procesar();
```

## Precauciones
- **No se interceptan automáticamente** los POST/PUT/PATCH/DELETE de `JocarsaAPI`: el desarrollador debe optar expresamente por encolar operaciones seguras de reintentar.
- El backend **debe implementar idempotencia** por clave `Idempotency-Key` para evitar duplicados tras cortes de conexión.
- La cola no almacena tokens ni contraseñas; la autenticación mediante cookies de sesión del mismo origen se aplica al transmitir. No guardar datos sensibles sin cifrado y controles adicionales.
- No se garantiza sincronización en segundo plano con la app cerrada: se reintenta al recuperar red, volver a la pestaña o pulsar sincronizar.
- Los conflictos (HTTP 409) y validaciones (4xx) quedan en estado `error` para revisión manual. La cola no decide automáticamente cómo resolverlos.
- Una petición enviada al servidor puede haberse procesado aunque la respuesta se pierda: por eso la idempotencia del backend es imprescindible.
- `navigator.onLine` no garantiza acceso real al servidor; los errores de red permanecen pendientes.
- El endpoint de prueba debe existir en el servidor; esta entrega no crea una API backend ni simula un envío exitoso.
