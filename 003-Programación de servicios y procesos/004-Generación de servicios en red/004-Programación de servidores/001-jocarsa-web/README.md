# jocarsa | web — integración inicial

Dos ejecutables independientes: `bin/jocarsa-servidor` y `bin/jocarsa-lenguaje`.

```bash
make
./bin/jocarsa-servidor
# http://localhost:8000
```

Ejecutar desde la raíz del proyecto. Se conserva la directiva `<#incluir "archivo.html" #>` y se añade `<#jocarsa ... #>` en páginas HTML. El servidor procesa primero inclusiones y luego ejecuta cada bloque con el intérprete original, capturando `stdout`. Cada bloque es una ejecución independiente y `escribir()` añade salto de línea. El intérprete también sigue funcionando desde consola.

**Alcance:** prototipo local. No exponer públicamente ni ejecutar scripts de usuarios no confiables. Los límites de CPU, memoria y tiempo son defensas parciales, no un sandbox. El código se ejecuta con los permisos del servidor. No se admiten aún GET/POST, sesiones, ni scripts `.jocarsa` como endpoints. La sintaxis del lenguaje no se ha modificado.
