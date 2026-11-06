# jocarsa | lenguaje — Fase 3

Intérprete educativo escrito en C. Variables con tipado dinámico y conversiones explícitas.

## Compilar

```bash
make
```

## Ejecutar

```bash
./jocarsa-lenguaje ejemplos/01_salidas.jocarsa
./jocarsa-lenguaje ejemplos/02_comentarios.jocarsa
./jocarsa-lenguaje ejemplos/03_variables_conversiones.jocarsa
```

## Tipos

- `entero`: enteros (`25`)
- `decimal`: números con punto decimal (`19.95`)
- `texto`: cadenas (`"hola"`)
- `booleano`: valores lógicos (`verum` y `falsum`)
- `nulo`: ausencia de valor (`nulla`)

Los tipos son dinámicos: `x = 2` y luego `x = "dos"` es válido.

## Conversiones

- `entero(valor)`: convierte enteros, decimales (trunca), booleanos y textos numéricos enteros.
- `decimal(valor)`: convierte números, booleanos y textos numéricos a decimal.
- `texto(valor)`: convierte cualquier valor a cadena.
- `booleano(valor)`: usa la veracidad del valor (cero, cadena vacía y nulo son falsos).
- `tipo(valor)`: devuelve `entero`, `decimal`, `texto`, `booleano` o `nulo`.

Los textos numéricos inválidos producen un error con línea y columna. No hay conversión implícita entre texto y números en el operador `+`. Los operadores aritméticos `+`, `-`, `*`, `/` y las comparaciones admiten números decimales y enteros mezclados; `%` sigue reservado a enteros. La división de dos enteros sigue siendo entera.

**Nota:** las palabras literales booleanas siguen siendo `verum`, `falsum` y `nulla` por compatibilidad con Logos. Se traducirán al español en una fase posterior.

## Fase 4 — Constantes y literales

Las variables siguen siendo dinamicas y reasignables. `constante NOMBRE = expresion` crea una vinculacion inmutable: no puede reasignarse ni redeclararse. La comprobacion se realiza durante la ejecucion.

Literales admitidos: enteros (`42`), decimales (`3.14`), cadenas (`"hola"`), booleanos (`verdadero`, `falso`) y ausencia de valor (`nulo`). Los alias heredados `verum`, `falsum`, `nulla` siguen disponibles. `tipo()` devuelve el nombre del tipo en español.

Prueba: `./jocarsa-lenguaje ejemplos/04_constantes_literales.jocarsa`. Para comprobar el error: `./jocarsa-lenguaje ejemplos/04_error_constante.jocarsa`.

## Fase 5 — Estructuras de control

- `si ...:`, `sinosi ...:`, `sino:` (también se admiten los alias históricos `aliosi`, `alio`).
- `segun expresion:` con bloques `caso valor:` y `defecto:`. No hay caída automática entre casos.
- `para i en rango(inicio, fin):` o `rango(inicio, fin, paso)`. El límite final es exclusivo. Solo enteros.
- `mientras condicion:` (comprueba antes de ejecutar).
- `hacer:` seguido de bloque indentado y `mientras condicion` (comprueba después de ejecutar).
- `romper` y `continuar` dentro de bucles; `romper` también dentro de `segun`.

Ejecutar: `./jocarsa-lenguaje ejemplos/05_control.jocarsa`.

## Fase 6 — Estructuras de datos

Se admiten listas `[1,2]`, tuplas `(1,2)`, diccionarios `{"clave": valor}` y conjuntos `conjunto([1,2,2])`.
Acceso por índice/clave con `[]`, asignación de índices de listas y claves de diccionarios, estructuras anidadas y `longitud()`, `claves()`, `valores()`.
Mutación por instrucciones `coleccion.agregar(valor)` y `coleccion.eliminar(valor)` para listas y conjuntos, y `diccionario.eliminar(clave)`.
Los valores se copian profundamente al asignarlos; modificar una copia no modifica el original.
Limitaciones actuales: no se admiten literales de conjunto con llaves, conjuntos de elementos no hashables, operaciones union/interseccion/diferencia, ni iteración directa sobre colecciones; `para` sigue usando `rango()`.
Ejecutar: `make && ./jocarsa-lenguaje ejemplos/06_estructuras.jocarsa`.

## Fase 7 — Funciones (implementación inicial)

Se incorporan `funcion nombre():`, `funcion nombre(parametro):`, `retornar valor`, llamadas como instrucciones y como expresiones, funciones recursivas y variables locales por llamada. Las funciones pueden acceder a variables globales; las asignaciones internas no modifican el ámbito global. Se permite un máximo de 64 niveles de llamadas anidadas. Los argumentos se pasan por valor (las colecciones se copian).

**Limitación actual:** cada función admite **cero o un parámetro**; las llamadas con varios argumentos, parámetros opcionales, cierres léxicos y funciones como valores quedan pendientes. Las definiciones deben ejecutarse antes de llamar a la función. Los errores de compilación existentes pueden mostrar algunos avisos no críticos.

Prueba: `make && ./jocarsa-lenguaje ejemplos/07_funciones.jocarsa`.

## Fase 7.1: funciones avanzadas

- Hasta 32 parametros posicionales por funcion.
- Multiples argumentos en llamadas.
- Parametros opcionales con valores por defecto (despues de los obligatorios).
- Comprobacion del numero de argumentos y errores de ejecucion.
- Evaluacion de argumentos en orden y conservacion de ambitos locales.

Ejecutar: `./jocarsa-lenguaje ejemplos/08_funciones_parametros.jocarsa`

Limitaciones: no hay argumentos nombrados, parametros variadicos ni sobrecarga de funciones. Los valores por defecto se evaluan cuando se omite el argumento.
