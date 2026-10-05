# JocarsaBBDD 1.1 — compilación rápida

## Motor / CLI
```bash
g++ -std=c++17 -O2 -Wall -Wextra -pedantic \
  main.cpp JocarsaBBDD.cpp \
  -o jocarsabbdd
```

## Extensión PHP
Necesitas los headers de desarrollo de PHP (`php-dev`/`php8.x-dev`) y `php-config`.

```bash
g++ -std=c++17 -O2 -fPIC -shared \
  $(php-config --includes) \
  -DCOMPILE_DL_JOCARSABBDD \
  conector_php.cpp JocarsaBBDD.cpp \
  -o jocarsabbdd.so
```

Prueba:

```bash
php -d extension="$(pwd)/jocarsabbdd.so" \
  prueba_php_completa.php "$(pwd)/config.json"
```

Comprobar funciones:

```bash
php -d extension="$(pwd)/jocarsabbdd.so" -r '
foreach (get_extension_funcs("jocarsabbdd") as $f) echo $f, PHP_EOL;
'
```

## API añadida para PHP

- `jocarsabbdd_listar_bases()`
- `jocarsabbdd_listar_tablas()`
- `jocarsabbdd_esquema($tabla)`
- `jocarsabbdd_clave_primaria($tabla)`
- `jocarsabbdd_claves_foraneas($tabla)`
- `jocarsabbdd_restricciones($tabla, $columna)`
- `jocarsabbdd_siguiente_id($tabla)`
- `jocarsabbdd_alterar_anadir(...)`
- `jocarsabbdd_alterar_eliminar_columna(...)`
- `jocarsabbdd_alterar_renombrar_columna(...)`
- `jocarsabbdd_alterar_modificar_columna(...)`
- `jocarsabbdd_truncar_tabla($tabla)`
- `jocarsabbdd_eliminar_tabla($tabla)`
- `jocarsabbdd_eliminar_base($base)`
- `jocarsabbdd_insertar_desde(...)`
- `jocarsabbdd_buscar(...)`
- `jocarsabbdd_actualizar(...)`
- `jocarsabbdd_eliminar(...)`
- `jocarsabbdd_en_transaccion()`

El objetivo de esta versión es que PHP no tenga que leer directamente los ficheros
internos `.csv`, `.esquema`, `.idx`, `.rel`, `.tipos` o `.indices`.
