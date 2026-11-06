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
