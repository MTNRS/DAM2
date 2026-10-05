```markdown
# Crimson ERP - API de salida

La API de salida de Crimson permite recuperar los registros de una tabla
del ERP en formato JSON.

## Endpoint

```text
io/es.php
```

## Método

```text
GET
```

## Parámetros

### tabla

Nombre de la tabla que se desea consultar.

Ejemplo:

```text
io/es.php?tabla=clientes
```

## Ejemplo con curl

```bash
curl "http://localhost/crimson/io/es.php?tabla=clientes"
```

## Ejemplo con JavaScript

```javascript
fetch("io/es.php?tabla=clientes")
    .then(respuesta => respuesta.json())
    .then(datos => {
        console.log(datos);
    });
```

## Ejemplo con PHP

```php
<?php

$json = file_get_contents(
    "http://localhost/crimson/io/es.php?tabla=clientes"
);

$datos = json_decode($json, true);

print_r($datos);

?>
```

## Respuesta correcta

```json
{
    "ok": true,
    "tabla": "clientes",
    "total": 2,
    "datos": [
        {
            "Identificador": 1,
            "nombre": "Cliente uno"
        },
        {
            "Identificador": 2,
            "nombre": "Cliente dos"
        }
    ]
}
```

## Propiedades de la respuesta

- `ok`: indica si la petición se ha realizado correctamente.
- `tabla`: tabla consultada.
- `total`: número de registros recuperados.
- `datos`: array con los registros de la tabla.

## Tabla inexistente

Petición:

```text
io/es.php?tabla=noexiste
```

Respuesta:

```json
{
    "ok": false,
    "error": "La tabla solicitada no existe."
}
```

Código HTTP:

```text
404 Not Found
```

## Parámetro tabla no indicado

Petición:

```text
io/es.php
```

Respuesta:

```json
{
    "ok": false,
    "error": "Debes indicar el parámetro tabla.",
    "ejemplo": "io/es.php?tabla=clientes"
}
```

Código HTTP:

```text
400 Bad Request
```

## Métodos admitidos

Actualmente la API de salida únicamente admite:

```text
GET
```

Otros métodos devolverán:

```text
405 Method Not Allowed
```

## Seguridad

La API:

- Comprueba que la tabla exista realmente en SQLite.
- No permite ejecutar SQL enviado por el usuario.
- Escapa el nombre de la tabla antes de construir la consulta.
- No permite acceder a tablas internas de SQLite.
- No permite acceder a tablas internas de Crimson cuyo nombre empiece por `__crimson_`.

## Arquitectura

```text
Sistema externo
      |
      | HTTP GET
      v
io/es.php
      |
      v
crimsonDb()
      |
      v
data/crimson.sqlite
      |
      v
JSON
```

## Ejemplos

Clientes:

```text
io/es.php?tabla=clientes
```

Productos:

```text
io/es.php?tabla=productos
```

Pedidos:

```text
io/es.php?tabla=pedidos
```

Empresas:

```text
io/es.php?tabla=empresas
```

Proveedores:

```text
io/es.php?tabla=proveedores
```

## Formato general

Todas las consultas correctas siguen la estructura:

```json
{
    "ok": true,
    "tabla": "nombre_tabla",
    "total": 0,
    "datos": []
}
```

Esto permite consumir cualquier tabla de negocio de Crimson mediante
el mismo endpoint sin necesidad de crear una API específica para cada entidad.
```