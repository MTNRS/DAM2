
<?php
require_once __DIR__ . '/endpoints-common.php';

try {
    $db = crimsonDb();

    $route = (string)($_GET['ruta'] ?? '');
    $method = $_SERVER['REQUEST_METHOD'];

    if (!preg_match('/^[a-z][a-z0-9-]{0,79}$/D', $route)) {
        ep_out(['error' => 'No encontrado'], 404);
    }

    $config = ep_config($db, $route);

    if (!$config || (string)$config['activo'] !== '1') {
        ep_out(['error' => 'No encontrado'], 404);
    }

    // Autenticación mediante clave API.
    $key = $_SERVER['HTTP_X_API_KEY'] ?? '';

    if (
        !is_string($key) ||
        strlen($key) > 256 ||
        !password_verify($key, (string)$config['key_hash'])
    ) {
        ep_log($db, $route, $method, 401);
        ep_out(['error' => 'Clave API inválida'], 401);
    }

    // Métodos permitidos, recuperados desde Base64.
    $methods = ep_decode_array(
        (string)($config['metodos'] ?? '')
    );

    if (!in_array($method, $methods, true)) {
        if ($methods) {
            header('Allow: ' . implode(', ', $methods));
        }

        ep_log($db, $route, $method, 405);
        ep_out(['error' => 'Método no permitido'], 405);
    }

    $table = (string)$config['entidad'];

    if (!ep_safe_table($db, $table)) {
        ep_out(['error' => 'Entidad no disponible'], 404);
    }

    // Campos publicados.
    $configuredFields = ep_decode_array(
        (string)($config['campos'] ?? '')
    );

    $allowed = array_values(array_intersect(
        $configuredFields,
        ep_fields($db, $table)
    ));

    if (!$allowed) {
        ep_out(['error' => 'Sin campos publicados'], 503);
    }

    $id = filter_var(
        $_GET['id'] ?? null,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    );

    // GET: consultar registros.
    if ($method === 'GET') {
        if (isset($_GET['id']) && !$id) {
            ep_out(['error' => 'ID inválido'], 400);
        }

        if ($id) {
            $row = $db->find($table, $id);

            if (!$row) {
                ep_out(['error' => 'No encontrado'], 404);
            }

            ep_log($db, $route, $method, 200);

            ep_out([
                'data' => ep_project(
                    ep_row($db, $table, $row),
                    $allowed
                )
            ]);
        }

        $page = max(1, min(100000, (int)($_GET['page'] ?? 1)));
        $limit = max(1, min(100, (int)($_GET['limit'] ?? 25)));

        $rows = $db->rows($table);
        $total = count($rows);

        $slice = array_slice(
            $rows,
            ($page - 1) * $limit,
            $limit
        );

        $result = [];

        foreach ($slice as $row) {
            $result[] = ep_project(
                ep_row($db, $table, $row),
                $allowed
            );
        }

        ep_log($db, $route, $method, 200);

        ep_out([
            'data' => $result,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total
            ]
        ]);
    }

    // DELETE: eliminar registro.
    if ($method === 'DELETE') {
        if (!$id) {
            ep_out(['error' => 'ID requerido'], 400);
        }

        if (!$db->find($table, $id)) {
            ep_out(['error' => 'No encontrado'], 404);
        }

        $db->delete($table, $id);

        ep_log($db, $route, $method, 200);
        ep_out(['ok' => true]);
    }

    // POST y PATCH: validar campos de escritura.
    $body = ep_body();

    $schemaFields = array_column(
        $db->schema($table),
        'name'
    );

    $writable = array_values(array_diff(
        array_intersect($allowed, $schemaFields),
        ['Identificador']
    ));

    foreach ($body as $field => $value) {
        if (
            !in_array($field, $writable, true) ||
            (!is_scalar($value) && $value !== null)
        ) {
            ep_out([
                'error' => 'Campo no autorizado: ' . $field
            ], 400);
        }
    }

    if (!$body) {
        ep_out(['error' => 'Sin campos'], 400);
    }

    // POST: crear registro.
    if ($method === 'POST') {
        $newId = $db->insert($table, $body);

        if (!$db->find($table, (int)$newId)) {
            ep_out([
                'error' => 'No se ha confirmado la inserción'
            ], 500);
        }

        ep_log($db, $route, $method, 201);

        ep_out([
            'ok' => true,
            'Identificador' => $newId
        ], 201);
    }

    // PATCH: actualizar registro.
    if ($method === 'PATCH') {
        if (!$id) {
            ep_out(['error' => 'ID requerido'], 400);
        }

        if (!$db->find($table, $id)) {
            ep_out(['error' => 'No encontrado'], 404);
        }

        $db->update($table, $id, $body);

        ep_log($db, $route, $method, 200);

        ep_out(['ok' => true]);
    }

    ep_out(['error' => 'Método no permitido'], 405);

} catch (Throwable $e) {
    error_log('[Crimson API public] ' . $e->getMessage());

    ep_out(['error' => 'Error interno de la API'], 500);
}
