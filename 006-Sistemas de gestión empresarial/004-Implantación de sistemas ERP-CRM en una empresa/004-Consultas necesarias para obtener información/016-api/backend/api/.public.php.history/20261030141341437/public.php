
<?php

require_once __DIR__ . '/endpoints-common.php';

/*
|--------------------------------------------------------------------------
| API PÚBLICA CRIMSON ERP
|--------------------------------------------------------------------------
|
| - Autenticación mediante X-API-Key
| - Métodos GET, POST, PATCH y DELETE
| - Campos configurables por endpoint
| - Resolución automática de claves foráneas
| - Exportación completa por defecto
| - Paginación opcional
|
*/


/*
|--------------------------------------------------------------------------
| OBTENER ETIQUETA DESCRIPTIVA DE UN REGISTRO
|--------------------------------------------------------------------------
*/

function ep_fk_label(array $row, string $key): string {

    $preferred = [
        'nombre',
        'razon_social',
        'titulo',
        'descripcion',
        'codigo',
        'referencia',
        'numero',
        'username',
        'email'
    ];

    foreach ($preferred as $field) {

        if (
            isset($row[$field]) &&
            is_scalar($row[$field]) &&
            trim((string)$row[$field]) !== ''
        ) {
            return (string)$row[$field];
        }
    }

    foreach ($row as $field => $value) {

        if (
            $field !== $key &&
            $field !== 'Identificador' &&
            $value !== null &&
            is_scalar($value) &&
            trim((string)$value) !== ''
        ) {
            return (string)$value;
        }
    }

    return (string)($row[$key] ?? '');
}


/*
|--------------------------------------------------------------------------
| PREPARAR LAS RELACIONES FK
|--------------------------------------------------------------------------
|
| Se cargan una sola vez por petición.
| Los registros relacionados se almacenan en caché.
|
*/

function ep_fk_context($db, string $table): array {

    $relations = $db->foreignKeys($table);
    $result = [];

    foreach ($relations as $column => $fk) {

        $targetTable = (string)($fk['tabla'] ?? '');
        $targetField = (string)($fk['campo'] ?? '');

        if (
            $targetField !== 'Identificador' ||
            !ep_safe_table($db, $targetTable)
        ) {
            continue;
        }

        $result[$column] = [
            'tabla' => $targetTable,
            'campo' => $targetField
        ];
    }

    return $result;
}


/*
|--------------------------------------------------------------------------
| RESOLVER LAS CLAVES FORÁNEAS
|--------------------------------------------------------------------------
|
| Ejemplo:
|
| categoria_id: 3
|
| Se transforma en:
|
| categoria_id: "Informática"
|
| Si no existe el registro relacionado, se conserva el ID.
|
*/

function ep_resolve_fk(
    $db,
    array $row,
    array $relations,
    array &$cache
): array {

    foreach ($relations as $column => $fk) {

        if (!array_key_exists($column, $row)) {
            continue;
        }

        $value = $row[$column];

        if ($value === null || $value === '') {
            continue;
        }

        $targetTable = $fk['tabla'];
        $cacheKey = $targetTable . ':' . (string)$value;

        if (!array_key_exists($cacheKey, $cache)) {

            $related = $db->find(
                $targetTable,
                $value
            );

            $cache[$cacheKey] = $related
                ? ep_fk_label($related, $fk['campo'])
                : null;
        }

        if ($cache[$cacheKey] !== null) {
            $row[$column] = $cache[$cacheKey];
        }
    }

    return $row;
}


/*
|--------------------------------------------------------------------------
| PREPARAR UN REGISTRO PARA LA API
|--------------------------------------------------------------------------
|
| 1. Recuperar campos personalizados.
| 2. Aplicar selección de campos publicados.
| 3. Resolver claves foráneas.
|
*/

function ep_prepare_row(
    $db,
    string $table,
    array $row,
    array $allowed,
    array $relations,
    array &$cache
): array {

    $row = ep_row(
        $db,
        $table,
        $row
    );

    $row = ep_project(
        $row,
        $allowed
    );

    $row = ep_resolve_fk(
        $db,
        $row,
        $relations,
        $cache
    );

    return $row;
}


/*
|--------------------------------------------------------------------------
| PROCESAMIENTO PRINCIPAL
|--------------------------------------------------------------------------
*/

try {

    $db = crimsonDb();

    $route = (string)($_GET['ruta'] ?? '');
    $method = $_SERVER['REQUEST_METHOD'];


    /*
    |--------------------------------------------------------------------------
    | VALIDAR RUTA
    |--------------------------------------------------------------------------
    */

    if (
        !preg_match(
            '/^[a-z][a-z0-9-]{0,79}$/D',
            $route
        )
    ) {
        ep_out([
            'error' => 'No encontrado'
        ], 404);
    }


    /*
    |--------------------------------------------------------------------------
    | CARGAR CONFIGURACIÓN DEL ENDPOINT
    |--------------------------------------------------------------------------
    */

    $config = ep_config(
        $db,
        $route
    );

    if (
        !$config ||
        (string)($config['activo'] ?? '') !== '1'
    ) {
        ep_out([
            'error' => 'No encontrado'
        ], 404);
    }


    /*
    |--------------------------------------------------------------------------
    | AUTENTICACIÓN MEDIANTE API KEY
    |--------------------------------------------------------------------------
    */

    $key = $_SERVER['HTTP_X_API_KEY'] ?? '';

    if (
        !is_string($key) ||
        strlen($key) > 256 ||
        !password_verify(
            $key,
            (string)($config['key_hash'] ?? '')
        )
    ) {

        ep_log(
            $db,
            $route,
            $method,
            401
        );

        ep_out([
            'error' => 'Clave API inválida'
        ], 401);
    }


    /*
    |--------------------------------------------------------------------------
    | MÉTODOS AUTORIZADOS
    |--------------------------------------------------------------------------
    */

    $methods = ep_decode_array(
        (string)($config['metodos'] ?? '')
    );

    if (!in_array($method, $methods, true)) {

        if ($methods) {
            header(
                'Allow: ' . implode(', ', $methods)
            );
        }

        ep_log(
            $db,
            $route,
            $method,
            405
        );

        ep_out([
            'error' => 'Método no permitido'
        ], 405);
    }


    /*
    |--------------------------------------------------------------------------
    | VALIDAR ENTIDAD
    |--------------------------------------------------------------------------
    */

    $table = (string)($config['entidad'] ?? '');

    if (!ep_safe_table($db, $table)) {

        ep_out([
            'error' => 'Entidad no disponible'
        ], 404);
    }


    /*
    |--------------------------------------------------------------------------
    | CAMPOS PUBLICADOS
    |--------------------------------------------------------------------------
    */

    $configuredFields = ep_decode_array(
        (string)($config['campos'] ?? '')
    );

    $allowed = array_values(
        array_intersect(
            $configuredFields,
            ep_fields($db, $table)
        )
    );

    if (!$allowed) {

        ep_out([
            'error' => 'Sin campos publicados'
        ], 503);
    }


    /*
    |--------------------------------------------------------------------------
    | IDENTIFICADOR DEL REGISTRO
    |--------------------------------------------------------------------------
    */

    $id = filter_var(
        $_GET['id'] ?? null,
        FILTER_VALIDATE_INT,
        [
            'options' => [
                'min_range' => 1
            ]
        ]
    );


    /*
    |--------------------------------------------------------------------------
    | GET - CONSULTAR REGISTROS
    |--------------------------------------------------------------------------
    */

    if ($method === 'GET') {

        if (
            isset($_GET['id']) &&
            !$id
        ) {
            ep_out([
                'error' => 'ID inválido'
            ], 400);
        }

        /*
        | Preparar claves foráneas y caché.
        */

        $relations = ep_fk_context(
            $db,
            $table
        );

        $cache = [];


        /*
        |--------------------------------------------------------------------------
        | GET DE UN ÚNICO REGISTRO
        |--------------------------------------------------------------------------
        */

        if ($id) {

            $row = $db->find(
                $table,
                $id
            );

            if (!$row) {

                ep_out([
                    'error' => 'No encontrado'
                ], 404);
            }

            $row = ep_prepare_row(
                $db,
                $table,
                $row,
                $allowed,
                $relations,
                $cache
            );

            ep_log(
                $db,
                $route,
                $method,
                200
            );

            ep_out([
                'data' => $row
            ]);
        }


        /*
        |--------------------------------------------------------------------------
        | GET DE TODOS LOS REGISTROS
        |--------------------------------------------------------------------------
        |
        | Sin page ni limit:
        | devuelve todos los registros.
        |
        | Con page o limit:
        | activa paginación.
        |
        */

        $rows = $db->rows($table);

        $total = count($rows);

        $paginate =
            isset($_GET['page']) ||
            isset($_GET['limit']);


        /*
        |--------------------------------------------------------------------------
        | PAGINACIÓN OPCIONAL
        |--------------------------------------------------------------------------
        */

        if ($paginate) {

            $page = max(
                1,
                min(
                    100000,
                    (int)($_GET['page'] ?? 1)
                )
            );

            $limit = max(
                1,
                min(
                    1000,
                    (int)($_GET['limit'] ?? 100)
                )
            );

            $rows = array_slice(
                $rows,
                ($page - 1) * $limit,
                $limit
            );
        }


        /*
        |--------------------------------------------------------------------------
        | PROCESAR TODOS LOS REGISTROS
        |--------------------------------------------------------------------------
        */

        $result = [];

        foreach ($rows as $row) {

            $result[] = ep_prepare_row(
                $db,
                $table,
                $row,
                $allowed,
                $relations,
                $cache
            );
        }


        /*
        |--------------------------------------------------------------------------
        | RESPUESTA JSON
        |--------------------------------------------------------------------------
        */

        $response = [
            'data' => $result,
            'total' => $total
        ];

        if ($paginate) {

            $response['pagination'] = [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => (int)ceil(
                    $total / $limit
                )
            ];
        }

        ep_log(
            $db,
            $route,
            $method,
            200
        );

        ep_out($response);
    }


    /*
    |--------------------------------------------------------------------------
    | DELETE - ELIMINAR REGISTRO
    |--------------------------------------------------------------------------
    */

    if ($method === 'DELETE') {

        if (!$id) {

            ep_out([
                'error' => 'ID requerido'
            ], 400);
        }

        $existing = $db->find(
            $table,
            $id
        );

        if (!$existing) {

            ep_out([
                'error' => 'No encontrado'
            ], 404);
        }

        $db->delete(
            $table,
            $id
        );

        if ($db->find($table, $id)) {

            ep_out([
                'error' => 'No se ha confirmado la eliminación'
            ], 500);
        }

        ep_log(
            $db,
            $route,
            $method,
            200
        );

        ep_out([
            'ok' => true
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | POST / PATCH - LEER DATOS
    |--------------------------------------------------------------------------
    */

    $body = ep_body();


    /*
    |--------------------------------------------------------------------------
    | DETERMINAR CAMPOS EDITABLES
    |--------------------------------------------------------------------------
    */

    $schemaFields = array_column(
        $db->schema($table),
        'name'
    );

    $writable = array_values(
        array_diff(
            array_intersect(
                $allowed,
                $schemaFields
            ),
            ['Identificador']
        )
    );


    /*
    |--------------------------------------------------------------------------
    | VALIDAR LOS DATOS RECIBIDOS
    |--------------------------------------------------------------------------
    */

    foreach ($body as $field => $value) {

        if (
            !in_array(
                $field,
                $writable,
                true
            ) ||
            (
                !is_scalar($value) &&
                $value !== null
            )
        ) {

            ep_out([
                'error' => 'Campo no autorizado: ' . $field
            ], 400);
        }
    }

    if (!$body) {

        ep_out([
            'error' => 'Sin campos'
        ], 400);
    }


    /*
    |--------------------------------------------------------------------------
    | POST - CREAR REGISTRO
    |--------------------------------------------------------------------------
    */

    if ($method === 'POST') {

        $newId = $db->insert(
            $table,
            $body
        );

        $saved = $db->find(
            $table,
            (int)$newId
        );

        if (!$saved) {

            ep_out([
                'error' => 'No se ha confirmado la inserción'
            ], 500);
        }

        ep_log(
            $db,
            $route,
            $method,
            201
        );

        ep_out([
            'ok' => true,
            'Identificador' => $newId
        ], 201);
    }


    /*
    |--------------------------------------------------------------------------
    | PATCH - ACTUALIZAR REGISTRO
    |--------------------------------------------------------------------------
    */

    if ($method === 'PATCH') {

        if (!$id) {

            ep_out([
                'error' => 'ID requerido'
            ], 400);
        }

        $existing = $db->find(
            $table,
            $id
        );

        if (!$existing) {

            ep_out([
                'error' => 'No encontrado'
            ], 404);
        }

        $db->update(
            $table,
            $id,
            $body
        );

        $updated = $db->find(
            $table,
            $id
        );

        if (!$updated) {

            ep_out([
                'error' => 'No se ha confirmado la actualización'
            ], 500);
        }

        ep_log(
            $db,
            $route,
            $method,
            200
        );

        ep_out([
            'ok' => true
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | MÉTODO NO RECONOCIDO
    |--------------------------------------------------------------------------
    */

    ep_out([
        'error' => 'Método no permitido'
    ], 405);

} catch (Throwable $e) {

    error_log(
        '[Crimson API public] ' .
        $e->getMessage()
    );

    ep_out([
        'error' => 'Error interno de la API'
    ], 500);
}
