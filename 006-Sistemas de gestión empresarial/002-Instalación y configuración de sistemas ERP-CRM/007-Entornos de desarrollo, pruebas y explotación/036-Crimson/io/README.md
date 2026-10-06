````markdown
# Crimson ERP - API E/S

La API E/S permite consultar e insertar información en las tablas de
negocio de Crimson ERP mediante JSON.

## Endpoint

```text
io/es.php
```

# Autenticación

Todas las peticiones requieren una API key.

El método recomendado es enviar la cabecera:

```text
X-API-Key: TU_CLAVE_API
```

También se admite:

```text
Authorization: Bearer TU_CLAVE_API
```

Ejemplo:

```bash
curl \
  -H "X-API-Key: TU_CLAVE_API" \
  "https://tudominio.com/io/es.php?accion=selectmany&tabla=clientes"
```

Si la clave no es correcta:

```json
{
    "ok": false,
    "error": "API key no válida."
}
```

Código HTTP:

```text
401 Unauthorized
```

---

# Acciones disponibles

La API dispone actualmente de cuatro operaciones:

| Acción | Método | Descripción |
|---|---|---|
| `selectone` | GET | Recupera un registro |
| `selectmany` | GET | Recupera todos los registros |
| `insertone` | POST | Inserta un registro |
| `insertmany` | POST | Inserta varios registros |

---

# 1. SELECT ONE

Recupera un registro mediante su `Identificador`.

## Petición

```text
GET io/es.php?accion=selectone&tabla=clientes&id=3
```

Ejemplo:

```bash
curl \
  -H "X-API-Key: TU_CLAVE_API" \
  "https://tudominio.com/io/es.php?accion=selectone&tabla=clientes&id=3"
```

## Respuesta

```json
{
    "ok": true,
    "accion": "selectone",
    "tabla": "clientes",
    "datos": {
        "Identificador": 3,
        "empresa_id": 1,
        "nombre": "Cliente de ejemplo",
        "cif": "B12345678",
        "email": "cliente@ejemplo.com"
    }
}
```

Si no existe:

```json
{
    "ok": false,
    "error": "Registro no encontrado."
}
```

---

# 2. SELECT MANY

Recupera todos los registros de una tabla.

## Petición

```text
GET io/es.php?accion=selectmany&tabla=clientes
```

Ejemplo:

```bash
curl \
  -H "X-API-Key: TU_CLAVE_API" \
  "https://tudominio.com/io/es.php?accion=selectmany&tabla=clientes"
```

## Respuesta

```json
{
    "ok": true,
    "accion": "selectmany",
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

---

# 3. INSERT ONE

Inserta un registro.

## Petición

```text
POST io/es.php?accion=insertone&tabla=clientes
```

El cuerpo debe contener un objeto JSON:

```json
{
    "empresa_id": 1,
    "nombre": "Nuevo cliente",
    "cif": "B12345678",
    "email": "nuevo@ejemplo.com",
    "telefono": "960000000",
    "direccion": "Valencia"
}
```

No es necesario enviar `Identificador`.

SQLite lo genera automáticamente.

## Ejemplo curl

```bash
curl \
  -X POST \
  -H "Content-Type: application/json" \
  -H "X-API-Key: TU_CLAVE_API" \
  -d '{
    "empresa_id": 1,
    "nombre": "Nuevo cliente",
    "cif": "B12345678",
    "email": "nuevo@ejemplo.com",
    "telefono": "960000000",
    "direccion": "Valencia"
  }' \
  "https://tudominio.com/io/es.php?accion=insertone&tabla=clientes"
```

## Respuesta

```json
{
    "ok": true,
    "accion": "insertone",
    "tabla": "clientes",
    "Identificador": 27
}
```

---

# 4. INSERT MANY

Permite insertar varios registros mediante una sola petición.

## Petición

```text
POST io/es.php?accion=insertmany&tabla=clientes
```

El cuerpo contiene un array JSON:

```json
[
    {
        "empresa_id": 1,
        "nombre": "Cliente A",
        "email": "a@ejemplo.com"
    },
    {
        "empresa_id": 1,
        "nombre": "Cliente B",
        "email": "b@ejemplo.com"
    },
    {
        "empresa_id": 1,
        "nombre": "Cliente C",
        "email": "c@ejemplo.com"
    }
]
```

## Ejemplo curl

```bash
curl \
  -X POST \
  -H "Content-Type: application/json" \
  -H "X-API-Key: TU_CLAVE_API" \
  -d '[
    {
        "empresa_id": 1,
        "nombre": "Cliente A",
        "email": "a@ejemplo.com"
    },
    {
        "empresa_id": 1,
        "nombre": "Cliente B",
        "email": "b@ejemplo.com"
    }
  ]' \
  "https://tudominio.com/io/es.php?accion=insertmany&tabla=clientes"
```

## Respuesta

```json
{
    "ok": true,
    "accion": "insertmany",
    "tabla": "clientes",
    "total": 2,
    "Identificadores": [
        28,
        29
    ]
}
```

`insertmany` se ejecuta dentro de una transacción.

Si falla cualquiera de los registros, se cancela la operación completa.

---

# JavaScript

## SELECT MANY

```javascript
fetch("io/es.php?accion=selectmany&tabla=clientes", {
    headers: {
        "X-API-Key": "TU_CLAVE_API"
    }
})
.then(respuesta => respuesta.json())
.then(datos => console.log(datos));
```

## INSERT ONE

```javascript
fetch("io/es.php?accion=insertone&tabla=clientes", {

    method: "POST",

    headers: {
        "Content-Type": "application/json",
        "X-API-Key": "TU_CLAVE_API"
    },

    body: JSON.stringify({
        empresa_id: 1,
        nombre: "Nuevo cliente",
        email: "cliente@ejemplo.com"
    })

})
.then(respuesta => respuesta.json())
.then(datos => console.log(datos));
```

---

# PHP

```php
<?php

$url = "https://tudominio.com/io/es.php"
     . "?accion=selectmany"
     . "&tabla=clientes";

$opciones = [
    "http" => [
        "header" =>
            "X-API-Key: TU_CLAVE_API\r\n"
    ]
];

$contexto = stream_context_create($opciones);

$json = file_get_contents(
    $url,
    false,
    $contexto
);

$datos = json_decode($json, true);

print_r($datos);
```

---

# Códigos HTTP

| Código | Significado |
|---:|---|
| 200 | Consulta correcta |
| 201 | Inserción correcta |
| 400 | Petición incorrecta |
| 401 | API key incorrecta |
| 404 | Tabla o registro inexistente |
| 405 | Método HTTP incorrecto |
| 500 | Error interno |

---

# Seguridad

La API:

- requiere API key;
- compara la clave mediante `hash_equals`;
- no permite acceder a tablas `sqlite_*`;
- no permite acceder a tablas `__crimson_*`;
- comprueba que la tabla exista;
- comprueba que cada campo exista realmente;
- utiliza consultas preparadas para los valores;
- escapa identificadores SQL;
- no acepta SQL proporcionado por el cliente;
- utiliza transacciones para inserciones múltiples.

---

# Arquitectura

```text
Aplicación externa
       |
       | X-API-Key
       |
       v
   io/es.php
       |
       +----------------+
       |                |
       v                v
    SELECT            INSERT
       |                |
       +-------+--------+
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

---

# Resumen

```text
selectone
    GET
    ?accion=selectone&tabla=clientes&id=1

selectmany
    GET
    ?accion=selectmany&tabla=clientes

insertone
    POST
    ?accion=insertone&tabla=clientes

insertmany
    POST
    ?accion=insertmany&tabla=clientes
```
```` 