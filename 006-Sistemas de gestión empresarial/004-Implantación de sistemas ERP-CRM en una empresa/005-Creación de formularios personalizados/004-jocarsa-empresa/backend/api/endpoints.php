
<?php
require_once __DIR__ . '/endpoints-common.php';

try {
    requireAdmin(true);

    $db = crimsonDb();
    ep_tables($db);

    $action = $_GET['action'] ?? 'list';
    $tableConfig = 'crimson_api_endpoints';

    if ($action === 'list') {
        $items = [];

        foreach ($db->rows($tableConfig) as $row) {
            unset($row['key_hash']);

            $row['campos'] = ep_decode_array(
                (string)($row['campos'] ?? '')
            );

            $row['metodos'] = ep_decode_array(
                (string)($row['metodos'] ?? '')
            );

            $items[] = $row;
        }

        ep_out(['items' => $items]);
    }

    if ($action === 'entities') {
        $entities = [];

        foreach ($db->tables(false) as $table) {
            if (ep_safe_table($db, $table)) {
                $entities[$table] = ep_fields($db, $table);
            }
        }

        ep_out(['entities' => $entities]);
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        ep_out(['error' => 'POST requerido'], 405);
    }

    $data = ep_body();
    $id = (int)($data['id'] ?? 0);

    if ($action === 'delete') {
        $existing = $db->find($tableConfig, $id);

        if (!$existing) {
            ep_out(['error' => 'Endpoint no encontrado'], 404);
        }

        $db->delete($tableConfig, $id);

        if ($db->find($tableConfig, $id)) {
            ep_out(['error' => 'No se ha eliminado el endpoint'], 500);
        }

        ep_out(['ok' => true]);
    }

    if ($action === 'rotate') {
        $existing = $db->find($tableConfig, $id);

        if (!$existing) {
            ep_out(['error' => 'Endpoint no encontrado'], 404);
        }

        $key = 'jca_' . bin2hex(random_bytes(32));
        $hash = password_hash($key, PASSWORD_DEFAULT);

        $db->update($tableConfig, $id, [
            'key_hash' => $hash,
            'key_prefix' => substr($key, 0, 12)
        ]);

        $saved = $db->find($tableConfig, $id);

        if (
            !$saved ||
            !password_verify($key, (string)($saved['key_hash'] ?? ''))
        ) {
            ep_out(['error' => 'No se pudo guardar la nueva clave'], 500);
        }

        ep_out([
            'ok' => true,
            'api_key' => $key
        ]);
    }

    if ($action !== 'save') {
        ep_out(['error' => 'Acción desconocida'], 400);
    }

    $table = (string)($data['entidad'] ?? '');
    $route = trim((string)($data['ruta'] ?? ''));
    $fields = $data['campos'] ?? [];
    $methods = $data['metodos'] ?? [];

    if (!ep_safe_table($db, $table)) {
        ep_out(['error' => 'Entidad no permitida'], 400);
    }

    if (!preg_match('/^[a-z][a-z0-9-]{0,79}$/D', $route)) {
        ep_out(['error' => 'Ruta inválida'], 400);
    }

    $availableFields = ep_fields($db, $table);

    if (
        !is_array($fields) ||
        !array_is_list($fields) ||
        !$fields ||
        array_diff($fields, $availableFields) ||
        count($fields) !== count(array_unique($fields))
    ) {
        ep_out(['error' => 'Campos inválidos'], 400);
    }

    $allowedMethods = ['GET', 'POST', 'PATCH', 'DELETE'];

    if (
        !is_array($methods) ||
        !array_is_list($methods) ||
        !$methods ||
        array_diff($methods, $allowedMethods)
    ) {
        ep_out(['error' => 'Métodos inválidos'], 400);
    }

    if (
        (
            in_array('PATCH', $methods, true) ||
            in_array('DELETE', $methods, true)
        ) &&
        !in_array('Identificador', $fields, true)
    ) {
        ep_out([
            'error' => 'PATCH y DELETE requieren publicar Identificador'
        ], 400);
    }

    foreach ($db->rows($tableConfig) as $row) {
        if (
            ($row['ruta'] ?? '') === $route &&
            (int)$row['Identificador'] !== $id
        ) {
            ep_out(['error' => 'Ruta ya ocupada'], 409);
        }
    }

    // Guardamos arrays como Base64 para evitar el fallo
    // de persistencia de cadenas JSON en JocarsaBBDD.
    $saveData = [
        'entidad' => $table,
        'ruta' => $route,
        'activo' => !empty($data['activo']) ? 1 : 0,
        'campos' => ep_encode_array($fields),
        'metodos' => ep_encode_array(
            array_values(array_unique($methods))
        )
    ];

    if ($id > 0) {
        $existing = $db->find($tableConfig, $id);

        if (!$existing) {
            ep_out(['error' => 'Endpoint no encontrado'], 404);
        }

        $db->update($tableConfig, $id, $saveData);

        $saved = $db->find($tableConfig, $id);

        if (
            !$saved ||
            ($saved['ruta'] ?? '') !== $route ||
            ($saved['campos'] ?? '') !== $saveData['campos'] ||
            ($saved['metodos'] ?? '') !== $saveData['metodos']
        ) {
            ep_out([
                'error' => 'No se ha confirmado la actualización'
            ], 500);
        }

        ep_out([
            'ok' => true,
            'id' => $id
        ]);
    }

    // Nueva publicación: desactivada por defecto,
    // salvo que el administrador la active expresamente.
    $key = 'jca_' . bin2hex(random_bytes(32));

    $saveData['key_hash'] = password_hash(
        $key,
        PASSWORD_DEFAULT
    );
    $saveData['key_prefix'] = substr($key, 0, 12);
    $saveData['creado'] = gmdate('c');

    $id = $db->insert($tableConfig, $saveData);

    $saved = $db->find($tableConfig, (int)$id);

    if (
        !$saved ||
        ($saved['ruta'] ?? '') !== $route ||
        ($saved['campos'] ?? '') !== $saveData['campos'] ||
        !password_verify(
            $key,
            (string)($saved['key_hash'] ?? '')
        )
    ) {
        ep_out([
            'error' => 'JocarsaBBDD no ha confirmado el guardado'
        ], 500);
    }

    ep_out([
        'ok' => true,
        'id' => (int)$id,
        'api_key' => $key
    ], 201);

} catch (Throwable $e) {
    error_log('[Crimson API endpoints] ' . $e->getMessage());

    ep_out([
        'error' => 'Error interno al gestionar endpoints'
    ], 500);
}
