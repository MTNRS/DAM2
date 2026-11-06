# Jocarsa BBDD: subconjuntos y CSV seguro

## Cambios
- Jerarquías ilimitadas con un padre por conjunto y varias pertenencias por tabla.
- Se conserva `.colecciones.json` original, sin migración destructiva. Las relaciones padre-hijo se almacenan en `.colecciones_padres.json`.
- Los campos CSV que contienen comas, comillas o saltos de línea se entrecomillan automáticamente. Las comillas internas se duplican. La lectura interpreta estos valores.
- API C++, CLI, extensión Python nativa, extensión PHP nativa y ORM FFI Python/PHP ampliados.
- Eliminar un conjunto con hijos se rechaza. Primero mueve o elimina sus hijos.

## Compilar en Ubuntu
```bash
sudo apt update
sudo apt install -y build-essential make pkg-config libjson-c-dev python3-dev php-dev php-cli python3
make clean
make all python php
```

## Instalación global
1. Detén las aplicaciones que escriben en el motor y haz una copia de seguridad consistente de los datos y de `/etc/jocarsa/config.json`.
2. Revisa la ruta de datos de la configuración. El instalador conserva `/etc/jocarsa/config.json` existente.
3. Ejecuta `sudo ./install-system.sh`. Instala el ejecutable, bibliotecas y módulos Python/PHP y reinicia Apache si está activo.
4. Comprueba `which jocarsabbdd`, `php -m | grep jocarsabbdd` y `python3 -c 'import jocarsabbdd,jocarsaorm;print("OK")'`.
5. Asegúrate de que los usuarios de aplicaciones tienen permisos adecuados sobre el directorio de datos.

## CLI (tras CREAR BASE / USAR)
```
COLECCION CREAR negocio
COLECCION SUBCREAR negocio ventas
COLECCION SUBCREAR ventas online
COLECCION AGREGAR online clientes
COLECCION HIJAS ventas
COLECCION RECURSIVO negocio
COLECCION ARBOL
COLECCION MOVER online negocio
COLECCION MOVER online RAIZ
COLECCION ELIMINAR ventas
```
Los nombres de colecciones son identificadores únicos en toda la base, no rutas.

## Python nativo
```python
import jocarsabbdd as db
db.usar_base('erp')
db.crear_coleccion('negocio')
db.crear_subcoleccion('negocio','ventas')
print(db.subcolecciones_de('negocio'))
print(db.tablas_de_coleccion_recursiva('negocio'))
print(db.arbol_colecciones()) # JSON string
```

## PHP nativo
`jocarsabbdd_crear_subcoleccion($padre,$nombre)`, `jocarsabbdd_mover_coleccion($nombre,$padre)`, `jocarsabbdd_subcolecciones_de($nombre)`, `jocarsabbdd_tablas_coleccion_recursiva($nombre)`, `jocarsabbdd_arbol_colecciones()` (JSON string).

## ORM Python/PHP
`crear_subcoleccion`, `mover_coleccion`, `subcolecciones_de`, `tablas_de_coleccion_recursiva`, `arbol_colecciones` (Python); equivalentes camelCase en PHP.

## Precauciones
- La compatibilidad con registros antiguos que ya contenían comas sin escapar no se puede recuperar automáticamente.
- Se conserva el límite de registro fijo `tamanoRegistro`; las comillas de escape también consumen bytes.
- Los metadatos no disponen todavía de bloqueo entre procesos ni rollback transaccional integral.
- El build PHP requiere `php-config` y `php.h`; no se pudo compilar el módulo PHP en el entorno de preparación. Compílalo y pruébalo en Ubuntu con `php-dev` antes de desplegarlo.
