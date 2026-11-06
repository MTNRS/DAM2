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

## Fase 3 — Configuración de límites

Edita `config/servidor.conf` y reinicia el servidor. También puedes indicar otra ruta: `./bin/jocarsa-servidor /ruta/servidor.conf`.

- `solo_local=1` enlaza el servicio a 127.0.0.1 (recomendado).
- `timeout_ms` limita el tiempo de pared del intérprete.
- `cpu_segundos`, `memoria_mb`, `max_archivos`, `max_procesos` aplican `setrlimit()` al intérprete.
- `max_script_kb` y `max_salida_kb` limitan el tamaño del código y de la salida.
- `max_conexiones` configura la cola de escucha TCP; el servidor sigue atendiendo una petición cada vez.
- `max_post_bytes` está reservado para una siguiente revisión del parser HTTP: actualmente sigue limitado por su búfer de 8191 bytes.
- `usuario` es opcional: si se define, el servidor debe arrancar con privilegios suficientes para cambiar de usuario en el hijo. Para uso local, se recomienda ejecutar todo el servidor como usuario no privilegiado y dejarlo vacío.

**Limitaciones de seguridad:** aún no hay aislamiento por namespaces/seccomp/cgroups, ni sandbox de sistema de archivos, ni límites de lectura para todas las fases HTTP. `RLIMIT_NPROC` no impide de forma fiable procesos de usuarios privilegiados. No exponer directamente a Internet. Los scripts temporales y el intérprete deben residir en un entorno de confianza.
