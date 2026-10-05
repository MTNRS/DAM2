# Jocarsa BBDD C++ + conector Python

Motor de base de datos en C++20 con ejecutable de terminal y módulo nativo para Python 3.

## 1. Dependencias (Ubuntu 24.04)

```bash
sudo apt update
sudo apt install build-essential python3-dev
```

## 2. Compilar

```bash
make clean
make
```

Se generan:

- `jocarsabbdd`: cliente de terminal.
- `jocarsabbdd.cpython-312-...so`: módulo nativo de Python (el sufijo depende de tu Python).

## 3. Instalar a nivel de sistema

```bash
sudo make install
```

Instala:

- `/usr/local/bin/jocarsabbdd`
- `/usr/local/lib/python3.12/dist-packages/jocarsabbdd....so` (ruta detectada automáticamente)
- `/etc/jocarsabbdd/config.json`
- datos en `/var/jocarsa-basededatos/`

El instalador asigna la carpeta de datos al usuario que ejecutó `sudo make install`, para que pueda crear bases sin ejecutar Python como root.

## 4. Probar desde cualquier directorio

```bash
cd /tmp
python3 -c "import jocarsabbdd; print(jocarsabbdd.__file__)"
python3 -c "import jocarsabbdd; print(jocarsabbdd.configuracion())"
```

El `import` ya no construye el motor inmediatamente. El motor se crea en la primera operación y cualquier excepción C++ se transforma en `RuntimeError` de Python, en lugar de abortar el intérprete.

## 5. Uso desde Python

```python
import jocarsabbdd

jocarsabbdd.crear_base("empresa")
jocarsabbdd.usar_base("empresa")
jocarsabbdd.crear_tabla("clientes", "nombre,apellidos,email")

id_cliente = jocarsabbdd.insertar(
    "clientes",
    ["Ana", "Garcia", "ana@empresa.com"]
)

print(id_cliente)
print(jocarsabbdd.seleccionar("clientes", id_cliente))
print(jocarsabbdd.listar("clientes"))
print(jocarsabbdd.buscar("clientes", "nombre", "Ana"))

jocarsabbdd.actualizar(
    "clientes",
    id_cliente,
    ["Ana", "Garcia", "nuevo@empresa.com"]
)

jocarsabbdd.eliminar("clientes", id_cliente)
```

Funciones disponibles:

```text
crear_base(nombre)
usar_base(nombre)
crear_tabla(tabla, esquema)
insertar(tabla, lista)
seleccionar(tabla, id)
listar(tabla)
buscar(tabla, columna, valor)
actualizar(tabla, id, lista)
eliminar(tabla, id)
configuracion()
```

## 6. Terminal

Tras instalar:

```bash
jocarsabbdd
```

Comandos principales:

```text
CREAR BASE empresa
USAR empresa
CREAR TABLA clientes nombre,apellidos,email
INSERTAR clientes Ana,Garcia,ana@empresa.com
LISTAR clientes
SELECCIONAR clientes 1
BUSCAR clientes nombre Ana
ACTUALIZAR clientes 1 Ana,Garcia,nueva@empresa.com
ELIMINAR clientes 1
SALIR
```

## 7. Configuración

Configuración global:

```text
/etc/jocarsabbdd/config.json
```

Contenido por defecto:

```json
{
  "instalacion": "/var/jocarsa-basededatos/",
  "tamanoRegistro": 512
}
```

Orden de búsqueda de configuración:

1. `config.json` del directorio actual (útil durante desarrollo).
2. Variable de entorno `JOCARSABBDD_CONFIG` si apunta a un archivo existente.
3. `/etc/jocarsabbdd/config.json`.

Ejemplo con configuración personalizada:

```bash
export JOCARSABBDD_CONFIG=/home/josevicente/mi-config.json
python3 programa.py
```

## 8. Reinstalar tras modificar C++

```bash
make clean
make
sudo make install
```

Comprueba desde fuera del proyecto:

```bash
cd /tmp
python3 -c "import jocarsabbdd; print(jocarsabbdd.configuracion())"
```

## 9. Desinstalar

```bash
sudo make uninstall
```

La desinstalación conserva deliberadamente `/var/jocarsa-basededatos` para no borrar bases de datos.

## 10. Instrucciones DDL adicionales en español

El terminal incorpora estas equivalencias:

```text
SQL                                      JocarsaBBDD
ALTER TABLE clientes ADD telefono        ALTERAR TABLA clientes ANADIR telefono
ALTER TABLE clientes DROP COLUMN telefono ALTERAR TABLA clientes ELIMINAR COLUMNA telefono
ALTER TABLE clientes RENAME COLUMN a TO b ALTERAR TABLA clientes RENOMBRAR COLUMNA a b
ALTER TABLE clientes MODIFY/ALTER ...     ALTERAR TABLA clientes MODIFICAR COLUMNA a b
TRUNCATE TABLE clientes                   TRUNCAR TABLA clientes
DROP TABLE clientes                       ELIMINAR TABLA clientes
DROP DATABASE empresa                     ELIMINAR BASE empresa
```

`ANADIR` también acepta `AÑADIR`. Como este motor todavía no maneja tipos de columna, `MODIFICAR COLUMNA` modifica el nombre de la columna y se comporta como `RENOMBRAR COLUMNA`.

Desde Python están disponibles además:

```python
jocarsabbdd.alterar_anadir("clientes", "telefono")
jocarsabbdd.alterar_eliminar_columna("clientes", "telefono")
jocarsabbdd.alterar_renombrar_columna("clientes", "email", "correo")
jocarsabbdd.alterar_modificar_columna("clientes", "correo", "email")
jocarsabbdd.truncar_tabla("clientes")
jocarsabbdd.eliminar_tabla("clientes")
jocarsabbdd.eliminar_base("empresa")
```

## DML v0.3 - INSERTAR, ACTUALIZAR y ELIMINAR

La terminal acepta las nuevas sentencias con nomenclatura en español:

```text
INSERTAR EN clientes VALORES ('Ana','30')
INSERTAR EN clientes (nombre) VALORES ('Ana')
INSERTAR EN copia SELECCIONAR * DE clientes

ACTUALIZAR clientes ESTABLECER nombre='Ana'
ACTUALIZAR clientes ESTABLECER edad='30' DONDE id='1'

ELIMINAR DE clientes
ELIMINAR DE clientes DONDE id='1'
```

`DONDE` implementa en esta versión comparaciones de igualdad (`columna=valor`).
`ELIMINAR` continúa usando borrado lógico internamente, preservando la arquitectura del motor.
`INSERTAR ... SELECCIONAR *` requiere que origen y destino tengan el mismo número de columnas de usuario.

### Python

```python
import jocarsabbdd

jocarsabbdd.usar_base("empresa")
jocarsabbdd.insertar("clientes", ["Ana", "30"])
jocarsabbdd.insertar_columnas("clientes", ["nombre"], ["Luis"])
jocarsabbdd.insertar_desde("copia", "clientes")
jocarsabbdd.actualizar_donde("clientes", {"edad": "31"}, "nombre", "Ana")
jocarsabbdd.eliminar_donde("clientes", "nombre", "Luis")
# Sin condición elimina lógicamente todos los registros:
jocarsabbdd.eliminar_donde("clientes")
```

### Actualizar una instalación anterior

```bash
make clean
make -j$(nproc)
sudo make install
```

`make install` sobrescribe el ejecutable y el módulo Python instalados, manteniendo las bases de datos de `/var/jocarsa-basededatos`.

## DQL - consultas (version actual)

Esta version incorpora el primer subconjunto de consultas del motor, manteniendo la nomenclatura en espanol:

```text
SELECCIONAR * DE clientes
SELECCIONAR nombre,edad DE clientes
SELECCIONAR DISTINTO ciudad DE clientes
SELECCIONAR nombre COMO cliente DE clientes
SELECCIONAR * DE clientes DONDE edad >= 18
SELECCIONAR * DE clientes DONDE edad >= 18 Y ciudad = 'Valencia'
SELECCIONAR * DE clientes DONDE NO ciudad = 'Madrid'
SELECCIONAR * DE clientes DONDE edad ENTRE 18 Y 65
SELECCIONAR * DE clientes DONDE ciudad EN ('Valencia','Madrid')
SELECCIONAR * DE clientes DONDE nombre COMO 'Jos%'
SELECCIONAR * DE clientes DONDE nombre LIKE 'Jos%'
SELECCIONAR * DE clientes ORDENAR POR nombre ASCENDENTE
SELECCIONAR * DE clientes ORDENAR POR edad DESCENDENTE LIMITE 10
```

Operadores de comparacion: `=`, `<>`, `!=`, `>`, `<`, `>=`, `<=`.

Los operadores logicos respetan precedencia: `NO`, despues `Y`, despues `O`. Se admiten parentesis en `DONDE`.

`COMO` tiene dos usos coherentes con la nomenclatura elegida: alias en la lista de seleccion (`nombre COMO cliente`) y busqueda de patron dentro de `DONDE` (`nombre COMO 'Jos%'`). Tambien se admite `LIKE` como sinonimo de este ultimo para facilitar pruebas y migraciones. Los patrones soportan `%` y `_`.

Desde Python se puede ejecutar la misma consulta:

```python
import jocarsabbdd
jocarsabbdd.usar_base("empresa")
filas = jocarsabbdd.consultar("SELECCIONAR nombre,edad DE clientes DONDE edad >= 18 ORDENAR POR edad DESCENDENTE LIMITE 10")
print(filas)
```
