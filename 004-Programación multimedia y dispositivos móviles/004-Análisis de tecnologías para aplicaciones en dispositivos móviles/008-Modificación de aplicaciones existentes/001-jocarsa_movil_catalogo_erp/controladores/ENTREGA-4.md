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
