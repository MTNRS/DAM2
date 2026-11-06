# Actualización experimental: jerarquía y CSV

## Compilación comprobada

```bash
g++ -std=c++17 -O2 -Iinclude src/JocarsaBBDD.cpp src/main.cpp -ljson-c -o jocarsabbdd
```

## CLI

```text
COLECCION CREAR business
COLECCION SUBCREAR business sales
COLECCION SUBCREAR business/sales online
COLECCION MOVER business/sales/online business
COLECCION HIJAS business
COLECCION RECURSIVO business
```

`COLECCION MOVER nombre -` mueve a raíz. Los nombres completos usan `/` como separador. La jerarquía se almacena en el archivo de colecciones existente, mediante nombres cualificados, sin copiar tablas. No se permite eliminar un conjunto que tenga descendientes.

## CSV

Los campos con coma, comillas, saltos de línea o espacios en extremos se entrecomillan y las comillas internas se duplican. Ejemplo:

```text
INSERTAR clientes Ana,"Valencia, España"
```

El serializador funciona en C++; se ha probado inserción y lectura desde CLI.

## Límites y precauciones

- Este paquete se ha reconstruido del informe Markdown, que no contiene todos los archivos originales (por ejemplo Makefile). No es un reemplazo listo para desplegar.
- Los conectores Python/PHP y la interfaz FFI aún no exponen las nuevas operaciones de jerarquía. Sus operaciones de colecciones existentes continúan usando nombres completos.
- El formato CSV con comillas puede no recuperar registros antiguos ya escritos de forma ambigua. Respaldar datos antes de instalar.
- El almacenamiento de registros sigue siendo de tamaño fijo. Los saltos de línea se admiten dentro de campos, pero algunas herramientas externas que procesan una línea por registro pueden fallar.
- La jerarquía basada en rutas usa nombres completos como identidad; mover un conjunto cambia los nombres de sus descendientes.
- No hay bloqueo entre procesos ni rollback garantizado para cambios de colecciones.
