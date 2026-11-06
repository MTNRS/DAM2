# Gestor YAML en C++17

Almacena documentos YAML en `datos/base_de_datos/coleccion/uuid.yaml`.

## Requisitos (Ubuntu)

```bash
sudo apt update
sudo apt install -y g++ cmake libyaml-cpp-dev uuid-dev
```

## Compilación y ejecución

```bash
cmake -S . -B build
cmake --build build -j
./build/ejemplo
```

Ejecuta desde la raíz del proyecto para que la carpeta `datos/` quede allí.

## API

```cpp
GestorYAML db("datos");
db.crearBaseDatos("empresa");
db.crearColeccion("empresa", "clientes");
YAML::Node objeto;
objeto["nombre"] = "Ana";
objeto["edad"] = 28;
std::string id = db.guardar("empresa", "clientes", objeto);
YAML::Node leido = db.leer("empresa", "clientes", id);
std::cout << leido["nombre"].as<std::string>() << "\n";
```

Consultar `ejemplos/main.cpp` para ver creación de documentos complejos, listas, mapas anidados, referencias por ID, lectura tipada, filtros, actualización y borrado.

## Consideraciones

- Las consultas `listar`, `filtrar` y `contar` recorren los archivos YAML de la colección. No hay índices.
- `filtrar` aplica igualdad exacta en campos escalares y permite condiciones anidadas en mapas; no implementa rangos ni operadores.
- `actualizar` reemplaza el documento completo, conservando el UUID.
- `leer` devuelve un nodo indefinido si no existe el documento.
- Las escrituras usan un archivo temporal y renombrado en el mismo directorio; no hay bloqueos entre procesos, transacciones ni garantías de sincronización física ante corte de energía.
- Las referencias entre documentos se guardan como IDs sin integridad referencial automática.
- Usa nombres de bases de datos y colecciones con letras ASCII, dígitos, `_` o `-`.
- No utilices el gestor con directorios que puedan contener enlaces simbólicos manipulados por usuarios no fiables.
