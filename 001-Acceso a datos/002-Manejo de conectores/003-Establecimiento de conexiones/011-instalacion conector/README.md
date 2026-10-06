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
