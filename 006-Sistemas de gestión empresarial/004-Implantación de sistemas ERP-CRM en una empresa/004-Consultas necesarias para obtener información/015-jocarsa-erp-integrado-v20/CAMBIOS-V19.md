# v19 — Filtros por columna y edición en línea

- `tabla_filtrar`: endpoint GET con filtros JSON por columna; verifica permiso de lectura y campos existentes. Busca por coincidencia parcial, sin distinción de mayúsculas, incluidos textos de FK.
- Fila de filtros debajo de las cabeceras de la tabla, con búsqueda en servidor y retardo de 320 ms.
- Doble clic en celda editable: Enter guarda, Escape cancela. Las claves foráneas utilizan `datalist` con opciones del endpoint `fk_opciones` y guardan el ID.
- Las escrituras siguen pasando por `actualizar`, con los permisos y reglas de flujo existentes.
- No se modifica el motor de persistencia.

Limitación: la búsqueda se realiza sobre los registros devueltos por el adaptador jocarsa-bbdd y se filtra en PHP; no hay índices ni paginación. Para conjuntos de datos grandes conviene añadir filtrado nativo al motor.
