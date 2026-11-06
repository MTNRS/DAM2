
<?php
require_once __DIR__ . '/auth_common.php';

function ep_out($data, int $status = 200): never {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode(
        $data,
        JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
    );
    exit;
}

function ep_encode_array(array $values): string {
    return base64_encode(json_encode(
        array_values($values),
        JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
    ));
}

function ep_decode_array(string $value): array {
    $decoded = base64_decode($value, true);
    $result = $decoded === false ? null : json_decode($decoded, true);

    if (!is_array($result)) {
        $result = json_decode($value, true);
    }

    return is_array($result) && array_is_list($result)
        ? $result
        : [];
}

function ep_tables($db): void {
    if (!$db->tableExists('crimson_api_endpoints')) {
        $db->createTable('crimson_api_endpoints', [
            ['name' => 'entidad', 'type' => 'TEXT'],
            ['name' => 'ruta', 'type' => 'TEXT'],
            ['name' => 'activo', 'type' => 'INTEGER'],
            ['name' => 'campos', 'type' => 'TEXT'],
            ['name' => 'metodos', 'type' => 'TEXT'],
            ['name' => 'key_hash', 'type' => 'TEXT'],
            ['name' => 'key_prefix', 'type' => 'TEXT'],
            ['name' => 'creado', 'type' => 'TEXT']
        ]);
    }
}

function ep_safe_table($db, string $table): bool {
    return preg_match(
        '/^[A-Za-z_][A-Za-z0-9_]*$/D',
        $table
    ) === 1
        && !str_starts_with($table, 'crimson_')
        && !str_starts_with($table, '__')
        && $db->tableExists($table);
}

function ep_custom($db, string $table): array {
    if (!$db->tableExists('crimson_ui_config')) {
        return [];
    }

    foreach ($db->rows('crimson_ui_config') as $r) {
        if (
            ($r['entidad'] ?? '') === $table &&
            ($r['tipo'] ?? '') === 'config'
        ) {
            $c = json_decode($r['contenido'] ?? '{}', true);
            $names = array_column($c['customFields'] ?? [], 'name');

            return array_values(array_filter(
                $names,
                fn($x) => is_string($x) &&
                    preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/D', $x)
            ));
        }
    }

    return [];
}

function ep_fields($db, string $table): array {
    return array_values(array_unique(array_merge(
        array_column($db->schema($table), 'name'),
        ep_custom($db, $table)
    )));
}

function ep_config($db, string $route): ?array {
    if (!$db->tableExists('crimson_api_endpoints')) {
        return null;
    }

    foreach ($db->rows('crimson_api_endpoints') as $r) {
        if (($r['ruta'] ?? '') === $route) {
            return $r;
        }
    }

    return null;
}

function ep_row($db, string $table, array $row): array {
    if (!$db->tableExists('crimson_ui_values')) {
        return $row;
    }

    $id = (string)($row['Identificador'] ?? '');
    $custom = ep_custom($db, $table);

    if (!$custom) {
        return $row;
    }

    foreach ($custom as $name) {
        $row[$name] = null;
    }

    foreach ($db->rows('crimson_ui_values') as $v) {
        if (
            ($v['entidad'] ?? '') === $table &&
            (string)($v['registro'] ?? '') === $id &&
            in_array($v['campo'] ?? '', $custom, true)
        ) {
            $row[$v['campo']] = $v['valor'];
        }
    }

    return $row;
}

function ep_project(array $row, array $fields): array {
    return array_intersect_key(
        $row,
        array_fill_keys($fields, true)
    );
}

function ep_body(): array {
    $raw = file_get_contents('php://input');

    if (strlen($raw) > 1048576) {
        ep_out(['error' => 'Cuerpo demasiado grande'], 413);
    }

    $data = json_decode($raw, true);

    if (!is_array($data) || array_is_list($data)) {
        ep_out(['error' => 'Se requiere un objeto JSON'], 400);
    }

    return $data;
}

function ep_log($db, string $route, string $method, int $status): void {
    try {
        if (!$db->tableExists('crimson_api_logs')) {
            $db->createTable('crimson_api_logs', [
                ['name' => 'ruta', 'type' => 'TEXT'],
                ['name' => 'metodo', 'type' => 'TEXT'],
                ['name' => 'estado', 'type' => 'INTEGER'],
                ['name' => 'fecha', 'type' => 'TEXT']
            ]);
        }

        $db->insert('crimson_api_logs', [
            'ruta' => $route,
            'metodo' => $method,
            'estado' => $status,
            'fecha' => gmdate('c')
        ]);
    } catch (Throwable $ignored) {
        // El fallo del registro de auditoría no interrumpe la API.
    }
}
