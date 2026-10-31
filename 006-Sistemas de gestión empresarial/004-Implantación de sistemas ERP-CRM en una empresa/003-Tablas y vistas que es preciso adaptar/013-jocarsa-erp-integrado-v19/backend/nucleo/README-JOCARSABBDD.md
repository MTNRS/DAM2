# Crimson ERP sobre JocarsaBBDD

Esta variante elimina la persistencia SQLite activa y usa la extensión PHP nativa `jocarsabbdd`.

## Requisitos

- `jocarsabbdd.so` instalado y cargado en PHP CLI y Apache.
- Configuración de JocarsaBBDD disponible mediante su configuración global o la configuración de la extensión.
- PHP con sesiones y `password_hash`/`password_verify`.

## Instalación

1. Copiar este proyecto al DocumentRoot.
2. Reiniciar Apache después de instalar/actualizar `jocarsabbdd.so`.
3. Abrir Crimson. Si la base `crimson` no existe, se redirige al instalador.
4. Elegir/diseñar el modelo y crear el administrador inicial.
5. El instalador crea la base `crimson`, las tablas de negocio, las FK y las tablas internas de usuarios/permisos.

## Adaptación de nombres

Crimson conserva su API lógica original:

- `Identificador` -> `id` físico de JocarsaBBDD.
- `activo` de negocio -> `__crimson_activo` físico.
- `activo` físico de JocarsaBBDD queda reservado al borrado lógico y no se expone a Crimson.

## Importante

No copies `data/crimson.sqlite`: esta versión no lo usa. Si quieres conservar datos de una instalación SQLite anterior hace falta una migración de datos independiente; este paquete migra la aplicación y su modelo, no los registros de una base SQLite previa.
