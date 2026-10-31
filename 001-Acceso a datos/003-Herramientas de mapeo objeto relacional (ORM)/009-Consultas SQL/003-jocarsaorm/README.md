# JocarsaORM 1.0 — motor nativo C++ / SQLite

## Instalar en Ubuntu

```bash
cd jocarsaorm
./install.sh
```

Dependencias: `build-essential`, `libsqlite3-dev`, `nlohmann-json3-dev`.

## Terminal

```bash
jocarsaorm demo.db crear
jocarsaorm demo.db guardar clientes '{"nombre":"Ana","habilidades":["C++","Python"]}'
jocarsaorm demo.db todos clientes
jocarsaorm demo.db obtener clientes 1
jocarsaorm demo.db buscar clientes nombre '"Ana"'
jocarsaorm demo.db actualizar clientes 1 '{"edad":29}'
jocarsaorm demo.db contar clientes
jocarsaorm demo.db exportar clientes ./salida
jocarsaorm demo.db importar clientes ./entrada
jocarsaorm demo.db eliminar clientes 1
```

## C++

```cpp
#include <JocarsaORM.h>
int main() {
    JocarsaORM orm("demo.db");
    auto id = orm.guardar("clientes", {{"nombre", "Ana"}});
    auto dato = orm.obtener("clientes", id);
}
```

Compilar: `g++ -std=c++17 main.cpp -ljocarsaorm -lsqlite3 -o demo`

## API C para Python y PHP

`include/jocarsaorm_c.h` expone funciones `jocarsa_open`, `jocarsa_guardar`, `jocarsa_obtener`, `jocarsa_todos`, `jocarsa_buscar`, `jocarsa_actualizar`, `jocarsa_eliminar`, `jocarsa_contar`, `jocarsa_importar`, `jocarsa_exportar`, `jocarsa_error`, `jocarsa_free` y `jocarsa_close`.

La memoria devuelta como `char*` debe liberarse con `jocarsa_free`.

## Notas

- Se almacena JSON recursivo en dos tablas internas, no una tabla por cada propiedad.
- `buscar` recorre y reconstruye todos los registros de la colección: todavía no es una consulta indexada.
- Los identificadores son globales dentro de la base, no consecutivos por colección.
- Se utiliza la ruta de base de datos que se suministre; no existe un demonio residente.
- El formato no es directamente compatible con las tablas de los scripts Python originales.
- La biblioteca está pensada para instancias independientes por conexión; no comparta un mismo objeto entre hilos simultáneos.
- En el entorno de generación faltaba la cabecera nlohmann/json.hpp, por lo que la compilación no se pudo verificar aquí. `install.sh` instala esa dependencia en Ubuntu antes de compilar.
