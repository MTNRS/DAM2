| Sentencia                | Función                                | Ejemplo                                         |
| ------------------------ | -------------------------------------- | ----------------------------------------------- |
| `INSERT`                 | Insertar registros                     | `INSERT INTO clientes VALUES (1,'Ana');`        |
| `INSERT INTO ... VALUES` | Insertar valores                       | `INSERT INTO clientes (nombre) VALUES ('Ana');` |
| `INSERT INTO ... SELECT` | Insertar desde otra consulta           | `INSERT INTO copia SELECT * FROM clientes;`     |
| `UPDATE`                 | Modificar registros                    | `UPDATE clientes SET nombre='Ana';`             |
| `UPDATE ... WHERE`       | Modificar registros concretos          | `UPDATE clientes SET edad=30 WHERE id=1;`       |
| `DELETE`                 | Eliminar registros                     | `DELETE FROM clientes;`                         |
| `DELETE ... WHERE`       | Eliminar registros concretos           | `DELETE FROM clientes WHERE id=1;`              |
| `MERGE`                  | Insertar/actualizar según coincidencia | Depende del SGBD                                |
| `REPLACE`                | Reemplazar/insertar registro           | MySQL/SQLite                                    |
| `UPSERT`                 | Insertar o actualizar                  | Sintaxis según SGBD                             |
