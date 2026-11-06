# Versión 11 — Acciones personalizadas

En Personalizar se pueden crear acciones declarativas por entidad: imprimir ficha o selección, duplicar registro y actualizar un campo. Ubicación por fila o selección múltiple y visibilidad condicional. Se guardan en localStorage como el resto de preferencias. Las operaciones de escritura usan la API Crimson y sus permisos; no se ejecuta código arbitrario.

Nota: las acciones en lote se ejecutan secuencialmente, sin transacción global.
