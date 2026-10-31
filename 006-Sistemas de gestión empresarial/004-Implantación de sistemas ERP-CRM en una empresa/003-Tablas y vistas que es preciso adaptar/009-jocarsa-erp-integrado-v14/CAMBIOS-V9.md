# Versión 9

- Corregido el HTML inválido del selector de vistas guardadas (`</select>` faltante), que impedía mostrar tabla y controles posteriores.
- Restauradas las pestañas Tabla, Tarjetas y Árbol, con renderizadores genéricos para cualquier entidad.
- Tema corporativo saddlebrown mediante `frontend/css/tema-empresa.css`.
- Logotipo y favicon: https://static.jocarsa.com/logos/saddlebrown.svg
- Login: imagen `frontend/jocarsa-empresa.png` (debe copiarse al desplegar; no se ha suministrado).
- Se preservan backend, permisos, CRUD y configuración de vistas existente.

La vista Árbol agrupa por un campo de categoría/grupo/estado/tipo/departamento cuando existe; de lo contrario muestra una lista jerárquica simple. No inventa relaciones padre-hijo.
