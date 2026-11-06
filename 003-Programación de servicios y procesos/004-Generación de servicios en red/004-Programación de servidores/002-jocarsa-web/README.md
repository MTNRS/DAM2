# jocarsa | web — integración inicial

Dos ejecutables independientes: `bin/jocarsa-servidor` y `bin/jocarsa-lenguaje`.

```bash
make
./bin/jocarsa-servidor
# http://localhost:8000
```

Ejecutar desde la raíz del proyecto. Se conserva la directiva `<#incluir "archivo.html" #>` y se añade `<#jocarsa ... #>` en páginas HTML. El servidor procesa primero inclusiones y luego ejecuta cada bloque con el intérprete original, capturando `stdout`. Cada bloque es una ejecución independiente y `escribir()` añade salto de línea. El intérprete también sigue funcionando desde consola.

**Alcance:** prototipo local. No exponer públicamente ni ejecutar scripts de usuarios no confiables. Los límites de CPU, memoria y tiempo son defensas parciales, no un sandbox. El código se ejecuta con los permisos del servidor. No se admiten aún GET/POST, sesiones, ni scripts `.jocarsa` como endpoints. La sintaxis del lenguaje no se ha modificado.

## Fase 2: HTTP dinámico

- Los archivos `.jocarsa` se ejecutan como endpoints y no se entregan como código fuente.
- `peticion_get("clave")`: lee el primer parámetro URL codificado de la query.
- `peticion_post("clave")`: lee el primer campo de un cuerpo `application/x-www-form-urlencoded`.
- `peticion_metodo()`: devuelve GET o POST.
- Ejemplos: `/api/saludo.jocarsa?nombre=Ana`, `/formulario.html`.

Esta versión NO implementa todavía el objeto `peticion`, cookies, sesiones, multipart, JSON de entrada ni aislamiento seguro para producción. El cuerpo POST está limitado a 8191 bytes y la ejecución sigue siendo secuencial. Utilizar solo en entorno de desarrollo confiable.
