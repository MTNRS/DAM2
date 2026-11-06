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
