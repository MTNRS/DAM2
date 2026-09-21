# JocarsaBBDD — Documentación para desarrolladores


## Resumen rápido de operaciones

| Operación | Método | Código Python |
|---|---|---|
| Crear una base de datos | `creaBaseDatos()` | `bbdd.creaBaseDatos("empresa")` |
| Seleccionar una base de datos | `usaBaseDatos()` | `bbdd.usaBaseDatos("empresa")` |
| Crear una tabla | `creaTabla()` | `bbdd.creaTabla("clientes","nombre,apellidos,email")` |
| Obtener el esquema | `obtenerEsquema()` | `esquema = bbdd.obtenerEsquema("clientes")` |
| Obtener el siguiente ID | `siguienteId()` | `id = bbdd.siguienteId("clientes")` |
| Insertar un registro | `insertarDatos()` | `id = bbdd.insertarDatos("clientes",["Jose","Carratala","jose@example.com"])` |
| Buscar la posición física de un ID | `buscarPosicion()` | `posicion = bbdd.buscarPosicion("clientes",1)` |
| Leer un registro | `leerRegistro()` | `registro = bbdd.leerRegistro("clientes",1)` |
| Seleccionar un registro como diccionario | `seleccionar()` | `cliente = bbdd.seleccionar("clientes",1)` |
| Listar todos los registros activos | `listarTodo()` | `bbdd.listarTodo("clientes")` |
| Buscar por una columna | `buscarColumna()` | `bbdd.buscarColumna("clientes","nombre","Jose")` |
| Actualizar un registro | `actualizar()` | `bbdd.actualizar("clientes",1,["Jose","Carratala","nuevo@example.com"])` |
| Eliminar lógicamente un registro | `eliminar()` | `bbdd.eliminar("clientes",1)` |
| Serializar una lista | `serializar()` | `cadena = serial.serializar(["uno","dos","tres"])` |
| Desserializar una cadena | `desserializar()` | `lista = serial.desserializar("uno,dos,tres")` |

### Inicialización mínima

Antes de utilizar las operaciones de base de datos:

```python
from jocarsa_bbdd import JocarsaBBDD

bbdd = JocarsaBBDD()
```

Para utilizar directamente el serializador:

```python
from jocarsa_bbdd import JocarsaSerializador

serial = JocarsaSerializador()
```

---

## 1. Descripción

`JocarsaBBDD` es una implementación didáctica de un pequeño motor de almacenamiento persistente basado en archivos.

El sistema utiliza tres archivos por tabla:

```text
tabla.csv       Datos
tabla.esquema   Definición de columnas
tabla.idx       Índice id → posición física
```

Los registros tienen un tamaño fijo de **512 bytes**. Esta decisión permite acceder directamente a un registro y modificarlo sin cargar la tabla completa en memoria.

La biblioteca contiene dos clases:

```text
JocarsaSerializador
JocarsaBBDD
```

`JocarsaSerializador` convierte listas a cadenas delimitadas y realiza la operación inversa.

`JocarsaBBDD` administra bases de datos, tablas, registros e índices.

---

## 2. Arquitectura de almacenamiento

Por defecto las bases de datos se almacenan en:

```text
/var/jocarsa-basededatos/
```

Cada base de datos es un directorio:

```text
/var/jocarsa-basededatos/
└── empresa/
    ├── clientes.csv
    ├── clientes.esquema
    └── clientes.idx
```

La ruta puede cambiarse modificando:

```python
bbdd.instalacion = "/otra/ruta/"
```

Esto resulta especialmente útil para pruebas.

---

## 3. Formato del esquema

Al crear una tabla:

```python
bbdd.creaTabla(
  "clientes",
  "nombre,apellidos,email"
)
```

el esquema almacenado será:

```text
id,activo,nombre,apellidos,email
```

Los campos `id` y `activo` son añadidos automáticamente por el motor.

### `id`

Identificador numérico del registro.

### `activo`

Indica si el registro está activo:

```text
1 = activo
0 = eliminado
```

El borrado es, por tanto, un **borrado lógico**.

---

## 4. Registros de tamaño fijo

Cada registro ocupa:

```python
tamanoRegistro = 512
```

bytes.

Un registro lógico como:

```text
1,1,Jose Vicente,Carratala,jose@example.com
```

se codifica en UTF-8, se rellena con espacios y termina con un salto de línea hasta completar exactamente 512 bytes.

Conceptualmente:

```text
| registro 1 - 512 bytes |
| registro 2 - 512 bytes |
| registro 3 - 512 bytes |
```

Esto permite usar:

```python
archivo.seek(posicion)
```

para acceder directamente al bloque correspondiente.

La implementación evita cargar el archivo completo durante `leerRegistro`, `actualizar` y `eliminar`.

---

## 5. Índice

El archivo `.idx` relaciona cada identificador con su posición física.

Ejemplo:

```text
1,0
2,512
3,1024
```

Esto significa:

```text
id 1 → byte 0
id 2 → byte 512
id 3 → byte 1024
```

`buscarPosicion()` consulta este archivo para localizar un registro.

Actualmente el índice se recorre secuencialmente, por lo que la búsqueda en el `.idx` es O(n), aunque el acceso posterior al registro de datos es directo.

---

## 6. JocarsaSerializador

### serializar(lista, delimitador=",")

Convierte una lista en una cadena.

```python
serial = JocarsaSerializador()

cadena = serial.serializar(
  ["Jose","Valencia",48]
)
```

Resultado:

```text
Jose,Valencia,48
```

También admite otro delimitador:

```python
serial.serializar(["uno","dos","tres"],"|")
```

Resultado:

```text
uno|dos|tres
```

### desserializar(cadena, delimitador=",")

Realiza la operación inversa:

```python
serial.desserializar(
  "Jose,Valencia,48"
)
```

Resultado:

```python
["Jose","Valencia","48"]
```

Todos los valores recuperados son cadenas.

---

## 7. JocarsaBBDD

### Constructor

```python
bbdd = JocarsaBBDD()
```

Valores iniciales principales:

```python
self.instalacion = "/var/jocarsa-basededatos/"
self.basededatos = ""
self.tamanoRegistro = 512
```

---

## 8. creaBaseDatos(nombre)

Crea el directorio correspondiente a una base de datos.

```python
bbdd.creaBaseDatos("empresa")
```

Produce:

```text
/var/jocarsa-basededatos/empresa/
```

La operación falla si la base de datos ya existe.

---

## 9. usaBaseDatos(nombre)

Selecciona la base de datos activa.

```python
bbdd.usaBaseDatos("empresa")
```

A partir de ese momento las operaciones se ejecutan sobre `empresa`.

---

## 10. creaTabla(nombre, esquema)

Crea los tres archivos necesarios para una tabla.

```python
bbdd.creaTabla(
  "clientes",
  "nombre,apellidos,email"
)
```

Genera:

```text
clientes.csv
clientes.esquema
clientes.idx
```

El esquema interno incluye automáticamente:

```text
id,activo
```

---

## 11. obtenerEsquema(tabla)

Recupera el esquema como lista.

```python
esquema = bbdd.obtenerEsquema("clientes")
```

Resultado:

```python
[
  "id",
  "activo",
  "nombre",
  "apellidos",
  "email"
]
```

---

## 12. siguienteId(tabla)

Obtiene el próximo identificador disponible leyendo el índice.

```python
id = bbdd.siguienteId("clientes")
```

Si el último registro tiene id `3`, devuelve:

```text
4
```

Los identificadores eliminados no se reutilizan.

---

## 13. insertarDatos(tabla, datos)

Inserta un nuevo registro.

```python
id = bbdd.insertarDatos(
  "clientes",
  [
    "Jose Vicente",
    "Carratala",
    "jose@example.com"
  ]
)
```

Internamente se construye:

```text
id,1,nombre,apellidos,email
```

El método:

1. calcula el siguiente id;
2. serializa los datos;
3. comprueba que caben en 511 bytes de contenido;
4. completa el bloque hasta 512 bytes;
5. añade el bloque al `.csv`;
6. añade `id,posicion` al `.idx`.

Devuelve el nuevo identificador o `None` si se produce un error.

---

## 14. buscarPosicion(tabla, id)

Busca en el índice la posición física de un registro.

```python
posicion = bbdd.buscarPosicion(
  "clientes",
  2
)
```

Puede devolver, por ejemplo:

```text
512
```

Si no encuentra el registro devuelve:

```text
-1
```

---

## 15. leerRegistro(tabla, id)

Lee directamente el bloque de 512 bytes correspondiente al identificador.

```python
registro = bbdd.leerRegistro(
  "clientes",
  1
)
```

Resultado:

```python
[
  "1",
  "1",
  "Jose Vicente",
  "Carratala",
  "jose@example.com"
]
```

Un registro marcado con `activo=0` no se devuelve.

---

## 16. seleccionar(tabla, id)

Convierte un registro en un diccionario utilizando el esquema.

```python
cliente = bbdd.seleccionar(
  "clientes",
  1
)
```

Resultado:

```python
{
  "id":"1",
  "activo":"1",
  "nombre":"Jose Vicente",
  "apellidos":"Carratala",
  "email":"jose@example.com"
}
```

Esta es la forma más cómoda de recuperar un registro individual.

---

## 17. listarTodo(tabla)

Recorre secuencialmente la tabla e imprime todos los registros activos.

```python
bbdd.listarTodo("clientes")
```

Los registros con:

```text
activo = 0
```

son ignorados.

Este método no carga toda la tabla en memoria: procesa un bloque de 512 bytes cada vez.

---

## 18. buscarColumna(tabla, columna, valor)

Busca registros activos cuyo campo coincida exactamente con un valor.

```python
bbdd.buscarColumna(
  "clientes",
  "nombre",
  "Ana"
)
```

La búsqueda es secuencial sobre el archivo de datos.

La columna debe existir en el esquema.

---

## 19. actualizar(tabla, id, datos)

Actualiza un registro existente **en su misma posición física**.

```python
bbdd.actualizar(
  "clientes",
  2,
  [
    "Ana Maria",
    "Garcia Perez",
    "anamaria@example.com"
  ]
)
```

El procedimiento es:

```text
buscar id en índice
        ↓
obtener posición
        ↓
leer registro actual
        ↓
crear nuevo bloque de 512 bytes
        ↓
seek(posición)
        ↓
sobrescribir únicamente ese bloque
```

No se reescribe el archivo completo y el índice no necesita modificarse.

El número de campos recibidos debe coincidir con el esquema excluyendo `id` y `activo`.

---

## 20. eliminar(tabla, id)

Realiza un borrado lógico.

```python
bbdd.eliminar(
  "clientes",
  3
)
```

No elimina físicamente el bloque.

Modifica:

```text
activo = 1
```

a:

```text
activo = 0
```

y vuelve a escribir únicamente los 512 bytes del registro.

Ventajas:

- no desplaza registros;
- no modifica posiciones;
- no obliga a reconstruir el índice;
- el coste de escritura es constante.

---

## 21. Gestión de errores

Los métodos siguen el patrón:

```python
try:
  # operación
except Exception as error:
  print("Se ha producido un error:")
  print(error)
```

También se utilizan `assert` para expresar condiciones que deben cumplirse:

```python
assert os.path.exists(ruta), "La tabla no existe"
```

Los métodos que producen un resultado suelen devolver un valor especial cuando ocurre un error:

```text
None
-1
```

dependiendo del método.

---

## 22. Ejemplo completo

```python
from jocarsa_bbdd import JocarsaBBDD

bbdd = JocarsaBBDD()

bbdd.creaBaseDatos("empresa")
bbdd.usaBaseDatos("empresa")

bbdd.creaTabla(
  "clientes",
  "nombre,apellidos,email"
)

id = bbdd.insertarDatos(
  "clientes",
  [
    "Jose Vicente",
    "Carratala",
    "jose@example.com"
  ]
)

cliente = bbdd.seleccionar(
  "clientes",
  id
)

print(cliente)

bbdd.actualizar(
  "clientes",
  id,
  [
    "Jose Vicente",
    "Carratala Sanchis",
    "nuevo@example.com"
  ]
)

bbdd.eliminar(
  "clientes",
  id
)
```

---

## 23. Pruebas

El archivo:

```text
pruebas_jocarsa_bbdd.py
```

crea una instalación temporal y comprueba, entre otras cosas:

- serialización;
- desserialización;
- creación de bases de datos;
- selección de bases de datos;
- creación de tablas;
- esquema;
- generación de ids;
- inserción;
- índice;
- lectura;
- selección;
- listado;
- búsqueda por columna;
- actualización in-place;
- borrado lógico;
- registros demasiado grandes;
- ids inexistentes;
- tablas y bases de datos inexistentes;
- duplicados;
- número incorrecto de campos.

La prueba utiliza un directorio temporal y lo elimina al finalizar, por lo que no debería afectar a `/var/jocarsa-basededatos/`.

Ejecución:

```bash
python3 pruebas_jocarsa_bbdd.py
```

Si el módulo principal no se llama:

```text
jocarsa_bbdd.py
```

hay que modificar el `import` inicial del archivo de pruebas.

---

## 24. Complejidad y comportamiento con bases grandes

### Inserción

Los datos se añaden al final del archivo.

El registro de datos se escribe directamente, aunque `siguienteId()` recorre actualmente el índice para localizar el último id.

### Lectura por id

El índice se recorre hasta encontrar el id y después el acceso al `.csv` es directo mediante `seek()`.

### Actualización

Solo se sobrescribe un bloque de 512 bytes.

### Eliminación

Solo se sobrescribe un bloque de 512 bytes.

### Listado y búsqueda por columna

Requieren recorrer la tabla secuencialmente, pero mantienen únicamente un registro en memoria cada vez.

---

## 25. Limitaciones actuales

El diseño es deliberadamente sencillo y didáctico.

Conviene tener presentes estas limitaciones:

- el serializador no escapa delimitadores incluidos dentro de los valores;
- no existe tipado de columnas;
- no hay bloqueo para escrituras concurrentes;
- no hay transacciones;
- el índice se busca secuencialmente;
- no existe compactación de registros eliminados;
- los registros tienen un máximo fijo de 511 bytes de contenido;
- `listarTodo()` y `buscarColumna()` imprimen los resultados en lugar de devolver una colección;
- los datos recuperados por el serializador son cadenas;
- no existe todavía validación automática del número de campos durante la inserción.

Estas características son buenos puntos de extensión para futuras versiones.

---

## 26. Posibles evoluciones

Sin cambiar la filosofía general del proyecto, una evolución natural sería incorporar:

```text
validación de campos al insertar
índice cargado o indexado eficientemente
compactación/VACUUM
bloqueo de escritura
tipos de datos
índices secundarios
consultas que devuelvan generadores
escape robusto de delimitadores
metadatos de tabla
transacciones simples
```

Una mejora especialmente interesante para tablas grandes sería convertir los métodos de listado y búsqueda en **generadores Python**, manteniendo el procesamiento streaming sin acumular resultados en memoria.
