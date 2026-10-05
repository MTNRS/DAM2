# JocarsaBBDD — documentación completa

## 1. Descripción

JocarsaBBDD es un SGBD didáctico escrito en C++20. Incluye un intérprete de comandos en terminal y un conector nativo para Python. Su objetivo es mostrar de forma transparente cómo pueden construirse las piezas fundamentales de un gestor de bases de datos: almacenamiento físico, esquema, índices, CRUD, consultas, integridad referencial, JOIN y agregaciones.

Esta versión incorpora:

- bases de datos y tablas;
- registros de tamaño fijo;
- identificador `id` automático;
- borrado lógico mediante `activo`;
- índice físico por `id`;
- DDL y DML en español;
- DQL con filtros y operadores lógicos;
- clave primaria implícita;
- claves foráneas con integridad referencial;
- `JOIN`/`UNIR` interno;
- `CONTAR`, `SUMA`, `MEDIA`, `MINIMO` y `MAXIMO`;
- `AGRUPAR POR`;
- `TENIENDO`;
- ordenación, límite, alias y valores distintos;
- módulo nativo Python.

---

## 2. Archivos del proyecto

- `JocarsaBBDD.hpp`: interfaz pública del motor.
- `JocarsaBBDD.cpp`: implementación del almacenamiento, restricciones y DQL.
- `main.cpp`: terminal interactiva y parser de comandos DDL/DML.
- `conector_python.cpp`: extensión nativa CPython.
- `Makefile`: compilación e instalación.
- `config.json`: configuración del directorio de datos y tamaño de registro.
- `PRUEBA_PK_FK_JOIN.txt`: prueba del hito relacional.
- `PRUEBA_AGREGACIONES.txt`: prueba de agregaciones, agrupación y HAVING/TENIENDO.

---

## 3. Compilación

Dependencias habituales en Ubuntu/Debian:

```bash
sudo apt update
sudo apt install build-essential python3-dev
```

Compilar:

```bash
make clean
make -j$(nproc)
```

Instalar:

```bash
sudo make install
```

Ejecutar:

```bash
jocarsabbdd
```

También puede ejecutarse el binario local:

```bash
./jocarsabbdd
```

---

## 4. Configuración

Ejemplo de `config.json`:

```json
{
  "instalacion": "/var/jocarsa-basededatos/",
  "tamanoRegistro": 512
}
```

`instalacion` indica dónde se guardan las bases. `tamanoRegistro` define el tamaño físico fijo de cada registro.

La configuración se busca en la ruta indicada al constructor/ejecutable, en `JOCARSABBDD_CONFIG` y finalmente en `/etc/jocarsabbdd/config.json`.

---

## 5. Organización física

Cada base de datos es un directorio. Una tabla puede generar estos archivos:

```text
clientes.csv
clientes.esquema
clientes.idx
clientes.rel
```

### `.csv`

Contiene los registros. Aunque la extensión sea CSV, el motor utiliza registros físicos de tamaño fijo para poder saltar directamente a posiciones conocidas.

### `.esquema`

Contiene los nombres de las columnas. El motor añade automáticamente:

```text
id,activo
```

Por ejemplo, al crear:

```text
CREAR TABLA clientes nombre,email
```

el esquema real es:

```text
id,activo,nombre,email
```

### `.idx`

Relaciona el `id` lógico con la posición física del registro dentro del archivo de datos. Esto permite localizar directamente un registro por su identificador.

### `.rel`

Contiene metadatos relacionales. Ejemplo:

```text
PK,id
FK,id_cliente,clientes,id
```

---

## 6. Crear y seleccionar una base

```text
CREAR BASE comercio
USAR comercio
```

Eliminar una base:

```text
ELIMINAR BASE comercio
```

---

## 7. Tablas

Crear:

```text
CREAR TABLA clientes nombre,email,ciudad
```

Consultar esquema:

```text
ESQUEMA clientes
```

Añadir columna:

```text
ALTERAR TABLA clientes ANADIR telefono
```

Eliminar columna:

```text
ALTERAR TABLA clientes ELIMINAR COLUMNA telefono
```

Renombrar:

```text
ALTERAR TABLA clientes RENOMBRAR COLUMNA email correo
```

Vaciar tabla:

```text
TRUNCAR TABLA clientes
```

Eliminar tabla:

```text
ELIMINAR TABLA clientes
```

---

## 8. Clave primaria

Cada tabla tiene una clave primaria implícita:

```text
PK,id
```

El usuario no necesita proporcionar el `id`. El motor calcula el siguiente identificador y lo incorpora al registro.

---

## 9. Claves foráneas

Ejemplo:

```text
CREAR TABLA clientes nombre,email
CREAR TABLA pedidos id_cliente,total
ALTERAR TABLA pedidos ANADIR CLAVE FORANEA id_cliente REFERENCIAS clientes id
```

Un pedido válido:

```text
INSERTAR EN clientes VALORES ('Ana','ana@correo.com')
INSERTAR EN pedidos VALORES ('1','120')
```

Una referencia inexistente es rechazada:

```text
INSERTAR EN pedidos VALORES ('999','120')
```

La integridad también se comprueba al actualizar. El borrado del registro padre usa política `RESTRICT`: si existen registros hijos que lo referencian, no puede eliminarse.

---

## 10. Inserción

Insertar todos los campos de usuario:

```text
INSERTAR EN clientes VALORES ('Ana','ana@correo.com','Valencia')
```

Insertar columnas concretas:

```text
INSERTAR EN clientes (nombre,ciudad) VALORES ('Luis','Madrid')
```

Sintaxis antigua compatible:

```text
INSERTAR clientes Ana,ana@correo.com,Valencia
```

---

## 11. Actualización

```text
ACTUALIZAR clientes ESTABLECER ciudad='Barcelona' DONDE nombre='Ana'
```

Varios campos:

```text
ACTUALIZAR clientes ESTABLECER ciudad='Barcelona',email='nuevo@correo.com' DONDE id='1'
```

Sin `DONDE`, la modificación se aplica a todos los registros activos.

---

## 12. Eliminación

```text
ELIMINAR DE clientes DONDE id='1'
```

El borrado es lógico: el registro permanece físicamente pero `activo` pasa a `0`.

Eliminar todos los registros activos:

```text
ELIMINAR DE clientes
```

Las claves foráneas pueden impedir un borrado si el registro sigue referenciado.

---

## 13. SELECT básico

```text
SELECCIONAR * DE clientes
```

Columnas concretas:

```text
SELECCIONAR nombre,ciudad DE clientes
```

Alias:

```text
SELECCIONAR nombre COMO cliente DE clientes
```

Valores distintos:

```text
SELECCIONAR DISTINTO ciudad DE clientes
```

---

## 14. DONDE

```text
SELECCIONAR * DE clientes DONDE ciudad = 'Valencia'
```

Operadores de comparación:

```text
=  <>  !=  >  <  >=  <=
```

Operadores lógicos:

```text
Y
O
NO
```

Ejemplo:

```text
SELECCIONAR * DE clientes DONDE ciudad='Valencia' Y edad >= 18
```

Paréntesis:

```text
SELECCIONAR * DE clientes DONDE (ciudad='Valencia' O ciudad='Madrid') Y edad >= 18
```

---

## 15. ENTRE, EN y COMO

Intervalo:

```text
SELECCIONAR * DE clientes DONDE edad ENTRE 18 Y 65
```

Conjunto:

```text
SELECCIONAR * DE clientes DONDE ciudad EN ('Valencia','Madrid')
```

Patrón:

```text
SELECCIONAR * DE clientes DONDE nombre COMO 'An%'
```

`%` representa cualquier secuencia y `_` un carácter.

---

## 16. Ordenación y límite

```text
SELECCIONAR * DE clientes ORDENAR POR nombre ASCENDENTE
```

```text
SELECCIONAR * DE clientes ORDENAR POR edad DESCENDENTE LIMITE 10
```

---

## 17. INNER JOIN

Ejemplo de modelo:

```text
clientes: id,nombre,email
pedidos: id,id_cliente,total
```

Consulta:

```text
SELECCIONAR clientes.nombre,pedidos.total DE clientes JOIN pedidos EN clientes.id = pedidos.id_cliente
```

También se acepta `UNIR` y `ON` como equivalentes en el parser.

Con alias y ordenación:

```text
SELECCIONAR clientes.nombre COMO cliente,pedidos.total COMO importe DE clientes JOIN pedidos EN clientes.id = pedidos.id_cliente ORDENAR POR importe DESCENDENTE
```

---

# 18. Funciones de agregación

Esta versión incorpora cinco funciones.

## CONTAR

Número de filas:

```text
SELECCIONAR CONTAR(*) DE pedidos
```

Con alias:

```text
SELECCIONAR CONTAR(*) COMO numero_pedidos DE pedidos
```

Sobre una columna, cuenta valores no vacíos:

```text
SELECCIONAR CONTAR(total) DE pedidos
```

## SUMA

```text
SELECCIONAR SUMA(total) DE pedidos
```

Los valores deben ser numéricos.

## MEDIA

```text
SELECCIONAR MEDIA(total) DE pedidos
```

## MINIMO

```text
SELECCIONAR MINIMO(total) DE pedidos
```

## MAXIMO

```text
SELECCIONAR MAXIMO(total) DE pedidos
```

Varias agregaciones pueden convivir:

```text
SELECCIONAR CONTAR(*) COMO numero,SUMA(total) COMO suma,MEDIA(total) COMO media,MINIMO(total) COMO minimo,MAXIMO(total) COMO maximo DE pedidos
```

---

## 19. AGRUPAR POR

Permite calcular agregados por grupos.

```text
SELECCIONAR id_cliente,CONTAR(*) COMO pedidos,SUMA(total) COMO facturacion DE pedidos AGRUPAR POR id_cliente
```

Una columna seleccionada que no sea una función agregada debe aparecer en `AGRUPAR POR`.

Esto es correcto:

```text
SELECCIONAR id_cliente,SUMA(total) DE pedidos AGRUPAR POR id_cliente
```

Esto produce error conceptual/sintáctico en el motor:

```text
SELECCIONAR id_cliente,SUMA(total) DE pedidos
```

porque `id_cliente` no está agregado ni agrupado.

---

## 20. TENIENDO

`TENIENDO` filtra los grupos después de calcular las agregaciones.

```text
SELECCIONAR id_cliente,SUMA(total) COMO facturacion DE pedidos AGRUPAR POR id_cliente TENIENDO facturacion > 200
```

Diferencia fundamental:

- `DONDE` filtra registros **antes** de agrupar.
- `TENIENDO` filtra grupos **después** de agregar.

Ejemplo combinado:

```text
SELECCIONAR id_cliente,SUMA(total) COMO facturacion DE pedidos DONDE total > 50 AGRUPAR POR id_cliente TENIENDO facturacion > 200
```

---

## 21. Agregaciones + JOIN

Esta es una de las consultas más importantes del hito:

```text
SELECCIONAR clientes.nombre COMO cliente,CONTAR(pedidos.id) COMO pedidos,SUMA(pedidos.total) COMO facturacion DE clientes JOIN pedidos EN clientes.id = pedidos.id_cliente AGRUPAR POR clientes.nombre ORDENAR POR facturacion DESCENDENTE
```

También puede filtrarse el resultado agregado:

```text
SELECCIONAR clientes.nombre COMO cliente,SUMA(pedidos.total) COMO facturacion DE clientes JOIN pedidos EN clientes.id = pedidos.id_cliente AGRUPAR POR clientes.nombre TENIENDO facturacion > 200
```

---

## 22. Orden lógico de una consulta

Conceptualmente, una consulta compleja de esta versión se procesa así:

```text
DE / JOIN
   ↓
DONDE
   ↓
AGRUPAR POR
   ↓
CONTAR / SUMA / MEDIA / MINIMO / MAXIMO
   ↓
TENIENDO
   ↓
SELECCIONAR / alias
   ↓
DISTINTO
   ↓
ORDENAR POR
   ↓
LIMITE
```

Este orden explica por qué `DONDE` opera sobre registros y `TENIENDO` sobre resultados agregados.

---

## 23. Ejemplo completo

```text
CREAR BASE comercio_demo
USAR comercio_demo
CREAR TABLA clientes nombre,email
CREAR TABLA pedidos id_cliente,total
ALTERAR TABLA pedidos ANADIR CLAVE FORANEA id_cliente REFERENCIAS clientes id

INSERTAR EN clientes VALORES ('Ana','ana@correo.com')
INSERTAR EN clientes VALORES ('Luis','luis@correo.com')

INSERTAR EN pedidos VALORES ('1','120')
INSERTAR EN pedidos VALORES ('1','75')
INSERTAR EN pedidos VALORES ('2','250')

SELECCIONAR * DE clientes
SELECCIONAR * DE pedidos

SELECCIONAR CONTAR(*) COMO pedidos,SUMA(total) COMO facturacion,MEDIA(total) COMO ticket_medio DE pedidos

SELECCIONAR clientes.nombre COMO cliente,CONTAR(pedidos.id) COMO pedidos,SUMA(pedidos.total) COMO facturacion DE clientes JOIN pedidos EN clientes.id = pedidos.id_cliente AGRUPAR POR clientes.nombre ORDENAR POR facturacion DESCENDENTE

SELECCIONAR clientes.nombre COMO cliente,SUMA(pedidos.total) COMO facturacion DE clientes JOIN pedidos EN clientes.id = pedidos.id_cliente AGRUPAR POR clientes.nombre TENIENDO facturacion > 200
```

Resultados esperados de las agregaciones globales con esos datos:

```text
pedidos = 3
facturacion = 445
ticket_medio = 148.333333333333
```

Por cliente:

```text
Luis | 1 | 250
Ana  | 2 | 195
```

---

## 24. Conector Python

Ejemplo:

```python
import jocarsabbdd

jocarsabbdd.usar_base("comercio_demo")

filas = jocarsabbdd.consultar(
    "SELECCIONAR clientes.nombre COMO cliente,"
    "CONTAR(pedidos.id) COMO pedidos,"
    "SUMA(pedidos.total) COMO facturacion "
    "DE clientes "
    "JOIN pedidos EN clientes.id = pedidos.id_cliente "
    "AGRUPAR POR clientes.nombre "
    "ORDENAR POR facturacion DESCENDENTE"
)

print(filas)
```

`consultar()` devuelve una lista de diccionarios Python utilizando los nombres/alias de las columnas resultantes.

---

## 25. Funciones principales del conector Python

Entre otras, el módulo expone:

```text
crear_base
usar_base
crear_tabla
alterar_anadir
alterar_eliminar_columna
alterar_renombrar_columna
alterar_modificar_columna
anadir_clave_foranea
truncar_tabla
eliminar_tabla
eliminar_base
insertar
insertar_columnas
insertar_desde
actualizar_donde
eliminar_donde
seleccionar
consultar
listar
buscar
actualizar
eliminar
configuracion
```

---

## 26. Limitaciones actuales

Esta versión es deliberadamente didáctica. Entre las piezas todavía no implementadas se encuentran:

- tipos de datos declarativos (`ENTERO`, `TEXTO`, etc.);
- `UNICO`, `NO NULO` y valores predeterminados;
- índices secundarios configurables;
- `LEFT JOIN`, `RIGHT JOIN` y `FULL JOIN`;
- subconsultas;
- transacciones;
- vistas;
- procedimientos almacenados;
- funciones almacenadas;
- triggers;
- concurrencia avanzada y bloqueo transaccional.

`SUMA` y `MEDIA` requieren que los valores almacenados puedan interpretarse numéricamente. `MINIMO` y `MAXIMO` comparan numéricamente cuando ambos valores son números y lexicográficamente en caso contrario.

---

## 27. Próximos hitos sugeridos

Una evolución razonable del proyecto sería:

```text
DDL / DML / DQL
      ↓
PK / FK
      ↓
INNER JOIN
      ↓
AGREGACIONES                  ← versión actual
AGRUPAR POR
TENIENDO
      ↓
LEFT JOIN
      ↓
UNICO / NO NULO / DEFAULT
      ↓
INDICES SECUNDARIOS
      ↓
SUBCONSULTAS
      ↓
TRANSACCIONES
      ↓
VISTAS
      ↓
PROCEDIMIENTOS / FUNCIONES
      ↓
TRIGGERS
```

---

## 28. Ayuda rápida

Dentro del terminal:

```text
AYUDA
```

Salir:

```text
SALIR
```

Para comprobar específicamente este hito puede copiarse línea por línea el archivo:

```text
PRUEBA_AGREGACIONES.txt
```

---

# Hito: LEFT JOIN y restricciones de integridad de columna

## LEFT JOIN

Además de `JOIN`/`INNER JOIN`, el motor soporta combinación externa izquierda:

```text
SELECCIONAR clientes.nombre,pedidos.total DE clientes LEFT JOIN pedidos EN clientes.id = pedidos.id_cliente
```

También se acepta la forma española:

```text
SELECCIONAR clientes.nombre,pedidos.total DE clientes IZQUIERDA JOIN pedidos EN clientes.id = pedidos.id_cliente
```

A diferencia del `JOIN` interior, las filas de la tabla izquierda sin correspondencia permanecen en el resultado y las columnas de la tabla derecha se devuelven vacías. Esto permite, por ejemplo, localizar clientes que todavía no tienen pedidos.

## Restricción NO NULO

```text
ALTERAR TABLA clientes MODIFICAR email NO NULO
```

La restricción se almacena en `.rel` como:

```text
NN,email
```

No puede aplicarse si ya existen filas activas cuyo valor sea vacío. Una vez aplicada, `INSERTAR` y `ACTUALIZAR` rechazan valores vacíos.

## Restricción UNICO

```text
ALTERAR TABLA clientes MODIFICAR email UNICO
```

Metadato:

```text
UQ,email
```

Antes de activar la restricción se comprueban los datos existentes. Si hay duplicados, la operación falla. En inserciones y actualizaciones posteriores se rechaza cualquier valor no vacío repetido. Los valores vacíos pueden repetirse salvo que la columna tenga además `NO NULO`.

## POR DEFECTO

```text
ALTERAR TABLA clientes MODIFICAR ciudad POR DEFECTO 'Valencia'
```

Metadato:

```text
DF,ciudad,Valencia
```

Si una inserción por columnas no proporciona `ciudad`, el motor sustituye el valor vacío por `Valencia` antes de validar el resto de restricciones.

Ejemplo:

```text
INSERTAR EN clientes (nombre,email) VALORES ('Ana','ana@correo.com')
```

Si `ciudad` tiene `POR DEFECTO 'Valencia'`, la fila queda almacenada con `Valencia`.

## Combinación de restricciones

Es posible combinar:

```text
ALTERAR TABLA clientes MODIFICAR email NO NULO
ALTERAR TABLA clientes MODIFICAR email UNICO
ALTERAR TABLA clientes MODIFICAR ciudad POR DEFECTO 'Valencia'
```

El orden de validación de una escritura es:

1. Completar valores `POR DEFECTO`.
2. Validar `NO NULO`.
3. Validar `UNICO`.
4. Validar claves foráneas.
5. Escribir el registro de tamaño fijo y actualizar el índice cuando corresponde.

`ESQUEMA clientes` muestra tanto columnas como PK, FK y restricciones de columna.

## Python

Las consultas `LEFT JOIN` se ejecutan mediante la misma función DQL:

```python
import jocarsabbdd
jocarsabbdd.usar_base("comercio")
filas = jocarsabbdd.consultar(
    "SELECCIONAR clientes.nombre,pedidos.total "
    "DE clientes LEFT JOIN pedidos "
    "EN clientes.id = pedidos.id_cliente"
)
print(filas)
```

Las nuevas restricciones también están expuestas directamente:

```python
jocarsabbdd.establecer_no_nulo("clientes", "email")
jocarsabbdd.establecer_unico("clientes", "email")
jocarsabbdd.establecer_por_defecto("clientes", "ciudad", "Valencia")
```

# Scripts de construcción e instalación

## limpiar.sh

Ejecuta `make clean`:

```bash
./limpiar.sh
```

## compilar.sh

Compila terminal y conector Python usando todos los núcleos disponibles:

```bash
./compilar.sh
```

## instalar.sh

Compila e instala el proyecto completo:

```bash
./instalar.sh
```

Internamente ejecuta `make` y `sudo make install`.

## compilar_python.sh

Compila únicamente el conector nativo para la versión de Python activa:

```bash
./compilar_python.sh
```

Puede seleccionarse otro intérprete:

```bash
PYTHON=python3.12 ./compilar_python.sh
```

## instalar_python.sh

Compila, instala el módulo en el `platlib` detectado por Python y comprueba la importación:

```bash
./instalar_python.sh
```

# Ejemplo integral del hito

```text
CREAR BASE comercio_left
USAR comercio_left
CREAR TABLA clientes nombre,email,ciudad
ALTERAR TABLA clientes MODIFICAR email NO NULO
ALTERAR TABLA clientes MODIFICAR email UNICO
ALTERAR TABLA clientes MODIFICAR ciudad POR DEFECTO 'Valencia'
INSERTAR EN clientes (nombre,email) VALORES ('Ana','ana@correo.com')
INSERTAR EN clientes (nombre,email) VALORES ('Luis','luis@correo.com')
CREAR TABLA pedidos id_cliente,total
ALTERAR TABLA pedidos ANADIR CLAVE FORANEA id_cliente REFERENCIAS clientes id
INSERTAR EN pedidos VALORES ('1','120')
SELECCIONAR clientes.nombre,pedidos.total DE clientes LEFT JOIN pedidos EN clientes.id = pedidos.id_cliente
ESQUEMA clientes
```

Resultado conceptual del LEFT JOIN:

```text
clientes.nombre | pedidos.total
Ana             | 120
Luis            |
```


# Hito: transacciones, subconsultas, vistas y tipos de datos

## 1. Transacciones

JocarsaBBDD incorpora transacciones explícitas:

```text
INICIAR TRANSACCION
INSERTAR EN productos VALORES ('Teclado','10','25.50','VERDADERO')
ACTUALIZAR productos ESTABLECER stock='9' DONDE id='1'
CONFIRMAR
```

Para cancelar todos los cambios efectuados desde `INICIAR TRANSACCION`:

```text
INICIAR TRANSACCION
ELIMINAR DE productos DONDE id='1'
DESHACER
```

### Implementación interna

Esta primera implementación utiliza un **snapshot físico de la base activa**. Al iniciar la transacción se copia el conjunto de ficheros de la base a un directorio temporal `.txn_<base>`. `CONFIRMAR` elimina el snapshot y conserva los ficheros modificados; `DESHACER` restaura el snapshot.

Es una implementación deliberadamente pedagógica y fácil de inspeccionar. Proporciona atomicidad para una única instancia del motor, pero todavía **no es un WAL**, no implementa MVCC, niveles de aislamiento ni control de concurrencia entre procesos. Esos conceptos quedan como evolución posterior.

Solo puede existir una transacción activa por instancia de `JocarsaBBDD`.

## 2. Subconsultas

Se admiten subconsultas **no correlacionadas** de una columna.

### Subconsulta con EN

```text
SELECCIONAR nombre
DE clientes
DONDE id EN (
    SELECCIONAR id_cliente
    DE pedidos
    DONDE total > 100
)
```

La consulta interior se ejecuta primero y sus valores se convierten en el conjunto usado por `EN`.

### Subconsulta escalar

```text
SELECCIONAR nombre,precio
DE productos
DONDE precio > (
    SELECCIONAR MEDIA(precio)
    DE productos
)
```

Una subconsulta escalar debe devolver exactamente una columna y una fila.

Las subconsultas pueden utilizar el DQL ya existente: filtros, agregaciones, agrupación, `TENIENDO`, etc. Esta versión no implementa todavía subconsultas correlacionadas.

## 3. Vistas

Las vistas son **no materializadas**: se almacena su consulta, no una copia de sus resultados.

```text
CREAR VISTA productos_caros COMO
SELECCIONAR nombre,precio
DE productos
DONDE precio > 100
```

Después se consulta como una fuente:

```text
SELECCIONAR * DE productos_caros
```

Administración:

```text
LISTAR VISTAS
ELIMINAR VISTA productos_caros
```

Cada vista se guarda en:

```text
<base>/<nombre>.vista
```

El contenido del fichero es el DQL de la vista. Por tanto, al consultar la vista se obtienen los datos actuales de las tablas subyacentes.

Se limita la profundidad de expansión de vistas para detectar cadenas recursivas accidentales.

## 4. Tipos de datos

Los tipos disponibles en esta versión son:

| Tipo | Descripción | Ejemplos |
|---|---|---|
| `TEXTO` | Cadena de caracteres | `Ana`, `Valencia` |
| `ENTERO` | Número entero con signo | `10`, `-4` |
| `REAL` | Número real | `25.50`, `-3.14` |
| `BOOLEANO` | Valor lógico | `1`, `0`, `VERDADERO`, `FALSO`, `TRUE`, `FALSE` |

Creación:

```text
CREAR TABLA productos nombre:TEXTO,stock:ENTERO,precio:REAL,disponible:BOOLEANO
```

Las columnas internas se tipan automáticamente:

```text
id      -> ENTERO
activo  -> BOOLEANO
```

Los tipos se almacenan en:

```text
productos.tipos
```

Ejemplo:

```text
id,ENTERO
activo,BOOLEANO
nombre,TEXTO
stock,ENTERO
precio,REAL
disponible,BOOLEANO
```

### Validación

Esto es válido:

```text
INSERTAR EN productos VALORES ('Teclado','10','25.5','VERDADERO')
```

Esto es rechazado:

```text
INSERTAR EN productos VALORES ('Teclado','muchos','25.5','VERDADERO')
```

porque `stock` es `ENTERO`.

Los booleanos se normalizan internamente a `1` y `0`.

### Almacenamiento físico

Los tipos son **tipos lógicos**. El registro físico continúa utilizando el formato textual de tamaño fijo del proyecto. Esta decisión conserva compatibilidad con la arquitectura anterior y permite estudiar por separado el concepto de tipado y el de representación binaria.

## 5. Combinación de las cuatro características

Las características no son módulos aislados. Por ejemplo:

```text
CREAR TABLA ventas producto:TEXTO,cantidad:ENTERO,total:REAL,pagado:BOOLEANO

INICIAR TRANSACCION
INSERTAR EN ventas VALORES ('Monitor','2','399.80','VERDADERO')
INSERTAR EN ventas VALORES ('Teclado','1','25.50','FALSO')
CONFIRMAR

CREAR VISTA ventas_importantes COMO
SELECCIONAR producto,total
DE ventas
DONDE total > 100

SELECCIONAR *
DE ventas_importantes
DONDE total > (
    SELECCIONAR MEDIA(total)
    DE ventas
)
```

## 6. API Python añadida

Además de `consultar()`, que automáticamente entiende subconsultas y vistas:

```python
import jocarsabbdd

jocarsabbdd.crear_tabla_tipada(
    "productos",
    {
        "nombre": "TEXTO",
        "stock": "ENTERO",
        "precio": "REAL",
        "disponible": "BOOLEANO",
    }
)

print(jocarsabbdd.tipos("productos"))

jocarsabbdd.crear_vista(
    "caros",
    "SELECCIONAR nombre,precio DE productos DONDE precio > 100"
)

print(jocarsabbdd.listar_vistas())
print(jocarsabbdd.consultar("SELECCIONAR * DE caros"))

jocarsabbdd.iniciar_transaccion()
# operaciones...
jocarsabbdd.confirmar()

jocarsabbdd.iniciar_transaccion()
# operaciones...
jocarsabbdd.deshacer()
```

## 7. Scripts de compilación e instalación

Se conservan los scripts del hito anterior:

```bash
./limpiar.sh
./compilar.sh
./instalar.sh
./compilar_python.sh
./instalar_python.sh
```

`compilar_python.sh` obtiene el sufijo ABI desde el Python activo, por lo que no fija manualmente nombres como `cpython-312` o `cpython-313`.

## 8. Compatibilidad

Las tablas antiguas que no posean `.tipos` se interpretan como `TEXTO`, excepto las columnas internas `id` y `activo`. Esto permite abrir bases creadas por versiones anteriores.

La sintaxis histórica:

```text
CREAR TABLA clientes nombre,email,ciudad
```

sigue siendo válida y crea las columnas del usuario como `TEXTO`.

## 9. Limitaciones conscientes de esta versión

- Las transacciones usan snapshot, no WAL/MVCC.
- No existe todavía concurrencia transaccional entre varios procesos.
- No existen `SAVEPOINT`.
- Las subconsultas son no correlacionadas.
- Una subconsulta usada como escalar debe devolver una fila.
- Las vistas son no materializadas.
- No hay `INSERT/UPDATE/DELETE` directamente sobre vistas.
- Los tipos son lógicos; el formato físico continúa siendo textual.
- No existe todavía `FECHA`, `HORA`, `DATETIME`, `DECIMAL(p,s)` ni tamaños `VARCHAR(n)`.
- No existe un valor `NULL` tipado real; se mantiene la semántica previa de cadena vacía.
- Los índices secundarios y el optimizador de consultas siguen pendientes.

## 10. Próximos hitos recomendados

Con esta versión ya tiene sentido abordar:

1. índices secundarios;
2. `EXPLICAR`;
3. alias de tablas y optimización de `JOIN`;
4. WAL y recuperación;
5. aislamiento/concurrencia;
6. tipos `FECHA`, `DATETIME` y `DECIMAL`;
7. procedimientos, funciones y triggers.



# Conector nativo PHP

Además del conector Python, el proyecto incluye `conector_php.cpp`, una extensión nativa PHP que enlaza directamente el mismo motor `JocarsaBBDD.cpp`.

## Compilación

En Ubuntu/Debian:

```bash
sudo apt install php-dev
./compilar_php.sh
```

El script usa `php-config --includes` y `php-config --extension-dir`, por lo que toma las cabeceras y ABI de la instalación PHP activa.

Para probar sin instalar globalmente:

```bash
php -d extension=./jocarsabbdd.so demo_php.php
```

Instalación opcional:

```bash
./instalar_php.sh
```

## API PHP principal

```php
jocarsabbdd_configurar('/ruta/config.json');
jocarsabbdd_crear_base('empresa');
jocarsabbdd_usar_base('empresa');
jocarsabbdd_crear_tabla('clientes', 'nombre,email');
jocarsabbdd_crear_tabla_tipada('productos', [
    'nombre' => 'TEXTO',
    'stock' => 'ENTERO',
    'precio' => 'REAL',
]);
$id = jocarsabbdd_insertar('productos', ['Teclado', '10', '25.5']);
$filas = jocarsabbdd_consultar('SELECCIONAR * DE productos');
```

También se exponen actualización y eliminación condicional, claves foráneas, restricciones `NO NULO`, `UNICO` y `POR DEFECTO`, vistas y transacciones.

## Transacciones desde PHP

```php
jocarsabbdd_iniciar_transaccion();
jocarsabbdd_insertar('productos', ['Temporal', '1', '5']);

// Confirmar:
jocarsabbdd_confirmar();

// O, en otra transacción, cancelar:
jocarsabbdd_iniciar_transaccion();
jocarsabbdd_eliminar_donde('productos', 'id', '1');
jocarsabbdd_deshacer();
```

## Documentación ejecutable

La carpeta `doc/` contiene ejemplos equivalentes:

```text
doc/
├── README.md
├── python/
│   ├── 01_basico.py
│   ├── 02_transacciones_vistas.py
│   └── 03_subconsultas.py
└── php/
    ├── 01_basico.php
    ├── 02_transacciones_vistas.php
    └── 03_subconsultas.php
```

El objetivo es que cada característica pueda enseñarse comparando directamente el uso del mismo motor desde Python y PHP.


# Fases 1–4: índices, planificador, tipos avanzados y JOIN

## Fase 1 — Índices secundarios

```text
CREAR INDICE idx_email EN clientes email
MOSTRAR INDICES clientes
ELIMINAR INDICE idx_email EN clientes
```

Los metadatos se guardan en `<tabla>.indices` y cada índice en `<tabla>.sidx_<nombre>`.
En esta primera implementación pedagógica los índices se reconstruyen después de las mutaciones. `buscarColumna()` utiliza el índice cuando existe.

## Fase 2 — EXPLICAR

```text
EXPLICAR SELECCIONAR * DE clientes DONDE email = 'ana@x.es'
```

Con índice de igualdad devuelve conceptualmente:

```text
PLAN DE EJECUCION
FUENTE: clientes
METODO: INDEX SCAN
INDICE: idx_email
COLUMNA: email
FUENTE TIPO: TABLA
```

Sin un índice aplicable indica `TABLE SCAN`. Esta es la primera capa del planificador; todavía no estima costes ni reordena JOIN.

## Fase 3 — NULL, FECHA y DECIMAL

Tipos disponibles:

```text
TEXTO
ENTERO
REAL
BOOLEANO
FECHA
DECIMAL
```

Ejemplo:

```text
CREAR TABLA clientes nombre:TEXTO,nacimiento:FECHA,saldo:DECIMAL
INSERTAR EN clientes VALORES ('Ana','2026-01-10','100.25')
INSERTAR EN clientes VALORES ('Luis',NULL,'50.50')
```

`FECHA` valida el formato `AAAA-MM-DD`. `DECIMAL` valida un valor decimal. En esta versión `DECIMAL` sigue usando conversión numérica de punto flotante y no es todavía un decimal exacto financiero.

`NULL` ya se diferencia internamente de `""`. Su representación física reservada es `__JOCARSA_NULL__`; terminal y conectores lo presentan como `NULL`/`None`/`null`. Queda pendiente completar la lógica SQL ternaria y operadores `ES NULO` / `NO ES NULO`.

## Fase 4 — Alias y JOIN ampliado

Alias:

```text
SELECCIONAR c.nombre,p.total
DE clientes c
JOIN pedidos p EN c.id = p.id_cliente
```

También se acepta `COMO` para el alias.

JOIN soportados:

```text
JOIN / INNER JOIN implícito
LEFT JOIN
RIGHT JOIN
FULL JOIN
CROSS JOIN
```

Ejemplos:

```text
SELECCIONAR c.nombre,p.total DE clientes c LEFT JOIN pedidos p EN c.id = p.id_cliente
SELECCIONAR c.nombre,p.total DE clientes c RIGHT JOIN pedidos p EN c.id = p.id_cliente
SELECCIONAR c.nombre,p.total DE clientes c FULL JOIN pedidos p EN c.id = p.id_cliente
SELECCIONAR c.nombre,p.total DE clientes c CROSS JOIN pedidos p
```

Se siguen admitiendo múltiples JOIN encadenados.

## Conectores

Python añade:

```python
jocarsabbdd.crear_indice("idx_email", "clientes", "email")
jocarsabbdd.indices("clientes")
jocarsabbdd.eliminar_indice("idx_email", "clientes")
print(jocarsabbdd.explicar("SELECCIONAR * DE clientes DONDE email='a@b.com'"))
```

PHP añade:

```php
jocarsabbdd_crear_indice("idx_email", "clientes", "email");
print_r(jocarsabbdd_indices("clientes"));
jocarsabbdd_eliminar_indice("idx_email", "clientes");
echo jocarsabbdd_explicar("SELECCIONAR * DE clientes DONDE email='a@b.com'");
```

Ejemplos nuevos:

```text
doc/python/04_indices_tipos_join.py
doc/php/04_indices_tipos_join.php
PRUEBA_FASES_1_A_4.txt
```

## Límites conscientes

- El índice secundario se reconstruye tras mutaciones; todavía no es B+Tree.
- `EXPLICAR` es un planificador inicial, no un optimizador por costes.
- `DECIMAL` aún no usa aritmética decimal exacta.
- `FECHA` comprueba formato y rangos básicos, no calendario completo.
- `NULL` es ya un valor distinguible, pero falta implementar toda la lógica ternaria SQL.
- RIGHT/FULL/CROSS JOIN son nested-loop.
- Falta detección estricta de columnas no cualificadas ambiguas.
