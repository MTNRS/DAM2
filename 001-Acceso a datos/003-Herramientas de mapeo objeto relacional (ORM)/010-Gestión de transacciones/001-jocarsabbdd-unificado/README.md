# JocarsaBBDD + JocarsaORM (integración experimental)

Un solo backend de almacenamiento: **JocarsaBBDD C++**. El módulo ORM ya no abre SQLite; usa las operaciones nativas de JocarsaBBDD. Se conserva el código del motor SQL y de sus conectores originales. Los conectores ORM utilizan la biblioteca nativa `libjocarsaorm.so`.

## Compilación Ubuntu

```bash
sudo apt install build-essential libjson-c-dev python3-dev php-dev
make
make python  # módulo SQL Python, opcional
make php     # extensión SQL PHP, opcional
```

## Ejemplos

```bash
export PYTHONPATH="$PWD/python"
export JOCARSAORM_LIB="$PWD/libjocarsaorm.so"
python3 examples/demo.py
php -d ffi.enable=1 examples/demo.php
```

Editar `config.json` para cambiar la ruta de datos. **No ejecutar como root ni apuntar a una instalación de producción sin copia de seguridad.**

## API ORM

`guardar(coleccion, objeto)`, `obtener(coleccion,id)`, `todos(coleccion)`, `buscar(coleccion,campo,valor)`, `actualizar(coleccion,id,cambios)`, `eliminar(coleccion,id)`, `contar(coleccion)`.

Los nombres de colección admiten letras ASCII, dígitos y guion bajo. Cada colección crea una tabla `orm_<coleccion>` con columna `documento` y PK administrada por JocarsaBBDD. Los JSON se codifican en hexadecimal para no interferir con el formato de registros delimitados del motor.

## Limitaciones importantes

- JocarsaBBDD utiliza registros de tamaño fijo (`tamanoRegistro=512` en el config original). Al codificar en hexadecimal, **el tamaño máximo del JSON es inferior a ~250 bytes**. Un JSON grande provoca error; es una limitación del backend, no del conector. Antes de producción hay que incorporar almacenamiento de documentos grandes o registros variables al motor.
- `buscar` y `contar` materializan documentos; no hay todavía índices por campo JSON.
- `actualizar` combina propiedades de primer nivel de objetos, reemplazando cualquier valor anidado; no equivale a JSON Merge Patch.
- La integración no migra las bases SQLite del ORM anterior. No borrar ni sustituir los originales.
- PHP ORM requiere FFI habilitada; no está incorporado todavía a la extensión `jocarsabbdd.so`. La API SQL original sí mantiene su extensión nativa.
- Las operaciones ORM no son transacciones compuestas para objetos grandes o lotes; el comportamiento depende del motor.
- El código C++ se ha compilado y el flujo CRUD Python se ha probado localmente. Se recomienda ampliar pruebas de regresión antes de desplegar.
