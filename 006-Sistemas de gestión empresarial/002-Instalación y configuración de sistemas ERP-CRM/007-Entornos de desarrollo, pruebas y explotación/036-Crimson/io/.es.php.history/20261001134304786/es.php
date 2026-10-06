<?php

/*
    Crimson ERP
    API de salida

    Uso:
    io/es.php?tabla=clientes
*/

header("Content-Type: application/json; charset=utf-8");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");

require_once __DIR__ . "/../api/auth_common.php";

try {

    // ============================================
    // 1. Comprobar método
    // ============================================

    if ($_SERVER["REQUEST_METHOD"] !== "GET") {
        http_response_code(405);

        echo json_encode([
            "ok" => false,
            "error" => "Método no permitido. Utiliza GET."
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        exit;
    }


    // ============================================
    // 2. Obtener tabla
    // ============================================

    $tabla = trim($_GET["tabla"] ?? "");

    if ($tabla === "") {
        http_response_code(400);

        echo json_encode([
            "ok" => false,
            "error" => "Debes indicar el parámetro tabla.",
            "ejemplo" => "io/es.php?tabla=clientes"
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        exit;
    }


    // ============================================
    // 3. Conectar con Crimson
    // ============================================

    $db = crimsonDb();


    // ============================================
    // 4. Comprobar que la tabla existe
    // ============================================

    $consulta = $db->prepare("
        SELECT name
        FROM sqlite_master
        WHERE type = 'table'
        AND name = :tabla
        AND name NOT LIKE 'sqlite_%'
        AND name NOT LIKE '__crimson_%'
    ");

    $consulta->execute([
        ":tabla" => $tabla
    ]);

    if (!$consulta->fetchColumn()) {
        http_response_code(404);

        echo json_encode([
            "ok" => false,
            "error" => "La tabla solicitada no existe."
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        exit;
    }


    // ============================================
    // 5. Escapar nombre de tabla
    // ============================================

    $tablaSQL = '"' . str_replace('"', '""', $tabla) . '"';


    // ============================================
    // 6. Recuperar registros
    // ============================================

    $consulta = $db->query(
        "SELECT * FROM " . $tablaSQL
    );

    $datos = $consulta->fetchAll(PDO::FETCH_ASSOC);


    // ============================================
    // 7. Devolver JSON
    // ============================================

    echo json_encode([
        "ok" => true,
        "tabla" => $tabla,
        "total" => count($datos),
        "datos" => $datos
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);


} catch (Throwable $error) {

    http_response_code(500);

    echo json_encode([
        "ok" => false,
        "error" => "Error interno de la API",
        "debug" => $error->getMessage()
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}