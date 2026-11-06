# jocarsa | lenguaje — fase 1: salidas

Intérprete en C adaptado a partir del proyecto Logos aportado.

## Compilar y probar (Linux)

```bash
make
make prueba
```

O bien: `./jocarsa-lenguaje ejemplos/01_salidas.jocarsa`

## Sintaxis incorporada

`escribir(expresion)` imprime un valor seguido de salto de línea.
Acepta cadenas, enteros, variables, operaciones aritméticas y comparaciones.
Las comparaciones se imprimen como `verdadero` o `falso`; los nulos como `nulo`.

## Limitaciones actuales

El resto de palabras reservadas de Logos todavía están en latín.
Todavía no hay `leer()`, tipos declarados, constantes, listas, bucles, funciones ni clases.
Se mantiene la estructura original del intérprete para facilitar las próximas fases.

## Fase 2: comentarios

- Comentario de una línea: `# comentario` (también al final de una instrucción).
- Comentario multilínea: `/* comentario que puede ocupar varias líneas */`.
- Los comentarios multilínea pueden aparecer entre tokens de una expresión.
- Los delimitadores dentro de cadenas de texto no se interpretan como comentarios.
- Un comentario multilínea sin cerrar produce un error con línea y columna.
- No se admiten comentarios multilínea anidados.

Ejecuta `./jocarsa-lenguaje ejemplos/02_comentarios.jocarsa` para probarlos.
