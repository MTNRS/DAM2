# Modelo de datos Crimson

`schema.json` conserva la representación visual actual.

El schema inicial se instala desde `instalacion/schema.sql`.

Los cambios hechos en `modelo.php` no se ejecutan hasta pulsar **Aplicar migración**.
Cada aplicación:

1. compara el diagrama con SQLite;
2. genera SQL;
3. crea un backup `data/crimson-backup-YYYYmmdd_HHMMSS.sqlite`;
4. guarda el SQL en `instalacion/migraciones/`;
5. ejecuta la migración;
6. valida `PRAGMA foreign_key_check`;
7. guarda el nuevo `schema.json`.

El editor se ha reducido intencionadamente a entidades, campos y claves foráneas.
