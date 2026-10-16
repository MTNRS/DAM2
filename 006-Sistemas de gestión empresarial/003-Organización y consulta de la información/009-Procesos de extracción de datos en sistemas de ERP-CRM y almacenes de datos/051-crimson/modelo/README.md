# Modelo de datos Crimson

`schema.json` conserva la representación visual del modelo. La persistencia real se realiza en la base `crimson` de JocarsaBBDD mediante la extensión nativa PHP.

El diseñador compara el diagrama con la introspección de JocarsaBBDD. Las migraciones se ejecutan dentro de una transacción del motor y se registran en `instalacion/migraciones/*.jocarsa.txt`.

En esta versión son seguras las altas de tablas, altas/bajas de columnas y nuevas FK. Los cambios de tipo existentes se rechazan explícitamente para no reconstruir registros ni alterar identificadores.
