# Jocarsa BBDD — Gestor visual

Aplicación HTML/CSS/JS/PHP sobre la extensión PHP nativa `jocarsabbdd`.

## Instalación Ubuntu / Apache

1. Instala el motor Jocarsa BBDD con `sudo ./install-system.sh` desde el proyecto del motor.
2. Copia esta carpeta a una ruta servida por Apache.
3. Configura permisos de escritura para `www-data` en `private/` y en `/var/lib/jocarsa/basededatos/` (preferentemente con grupo y ACL, no chmod 777).
4. Comprueba `php -m | grep jocarsabbdd` y `phpinfo()` en Apache (CLI y Apache pueden cargar diferentes INI).
5. Accede a `index.html` mediante `http://localhost/...`, nunca con `file://`.

## Correspondencia

- Proyecto = base nativa `visual_<id>`.
- Entidad = tabla nativa; `id` y `activo` son columnas internas gestionadas por el motor.
- Categoría = colección nativa; una tabla puede pertenecer a varias.
- Registros = filas reales en el CSV de longitud fija del motor.
- Las posiciones, enlaces visuales e IDs de propiedades se guardan en `private/projects.json`; los registros no se guardan ahí.

## Limitaciones de esta versión

- Renombrar/eliminar tablas y eliminar/renombrar columnas está bloqueado para prevenir pérdida de registros. La edición del tipo de columna existente no ejecuta una migración de tipo.
- El botón «Limpiar» y la importación JSON no eliminan tablas ya existentes.
- Las claves foráneas se crean para enlaces `id -> campo`; eliminarlas requiere una operación explícita del motor.
- La base no se llama literalmente como el título del proyecto: utiliza un identificador interno estable para evitar colisiones y problemas al renombrar.
- No exponer a Internet sin autenticación y autorización; esta edición está diseñada para localhost/intranet confiable.
- El almacenamiento usa archivos nativos del motor y requiere sus permisos y capacidad de concurrencia adecuados.

## Correcciones de sincronización

- El motor nativo recibe las asociaciones de colecciones como `(tabla, coleccion)`.
- Las tablas existentes se reconocen mediante `jocarsabbdd_listar_tablas()` y no se vuelven a crear.
- Eliminar una entidad o pulsar Limpiar solicita confirmación explícita y elimina físicamente las tablas afectadas, incluyendo todos sus registros. Realiza copia de seguridad previamente.
- Eliminar una categoría de una entidad desasocia la tabla sin borrar sus registros.
- El motor de colecciones actualmente solo acepta nombres con letras ASCII, números, guion y guion bajo.
- Renombrar tablas y eliminar columnas se mantienen bloqueados hasta incorporar migraciones seguras.


## Identidad visual
Nombre: jocarsa | bbdd. Tema pink suave y logo oficial https://static.jocarsa.com/logos/pink.svg. El logo requiere conexión a Internet.
