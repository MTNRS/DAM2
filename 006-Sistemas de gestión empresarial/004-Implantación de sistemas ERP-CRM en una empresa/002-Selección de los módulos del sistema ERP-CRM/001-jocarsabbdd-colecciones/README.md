# Jocarsa BBDD: colecciones transversales

Proyecto reconstruido a partir del código aportado. Conserva motor CSV/SQL, ORM de documentos JSON y conectores PHP/Python.

## Compilar (Ubuntu)

```bash
sudo apt install build-essential libjson-c-dev python3-dev php-dev
make
make python php  # conectores SQL nativos opcionales (requiere php-dev)
mkdir -p datos
```

Desde la raíz del proyecto:

```bash
./jocarsabbdd
# CREAR BASE erp
# USAR erp
# CREAR TABLA productos nombre:TEXTO,precio:REAL
# COLECCION CREAR marketing
# COLECCION CREAR produccion
# COLECCION AGREGAR marketing productos
# COLECCION AGREGAR produccion productos
# COLECCION TABLAS marketing
# COLECCION DE productos
# COLECCION LISTAR
# COLECCION QUITAR marketing productos
# COLECCION ELIMINAR marketing
```

ORM Python:

```bash
PYTHONPATH=python JOCARSAORM_LIB="$PWD/libjocarsaorm.so" python3 examples/colecciones.py
```

PHP FFI:

```bash
php -d ffi.enable=1 examples/colecciones.php
```

## API

C++: `crearColeccion`, `eliminarColeccion`, `listarColecciones`, `agregarTablaColeccion`, `quitarTablaColeccion`, `tablasDeColeccion`, `coleccionesDeTabla`.

Python: `crear_coleccion`, `eliminar_coleccion`, `listar_colecciones`, `agregar_tabla_coleccion`, `quitar_tabla_coleccion`, `tablas_de_coleccion`, `colecciones_de_tabla`.

PHP ORM: métodos equivalentes en camelCase.

Los metadatos se guardan en `datos/<base>/.colecciones.json`. Una tabla puede aparecer en varias colecciones sin duplicar sus registros. Eliminar una colección no borra tablas. Eliminar una tabla elimina sus pertenencias.

**Importante:** la API ORM original `guardar(coleccion, documento)` conserva su significado previo: colección de documentos JSON almacenados en tablas `orm_*`. Las nuevas colecciones transversales son agrupaciones de tablas y se manejan mediante los nuevos métodos. No son intercambiables.

## Limitaciones

- Los registros del motor original son de tamaño fijo; el límite de documentos JSON sigue vigente.
- El guardado usa fichero temporal y renombrado atómico, con mutex dentro de un proceso; no ofrece bloqueo entre procesos, ni integración garantizada con rollback del motor.
- No se ha implementado aún control de acceso por colección ni una interfaz web para editar las agrupaciones.
- Antes de usar en producción: copias de seguridad y pruebas de concurrencia y transacciones.
