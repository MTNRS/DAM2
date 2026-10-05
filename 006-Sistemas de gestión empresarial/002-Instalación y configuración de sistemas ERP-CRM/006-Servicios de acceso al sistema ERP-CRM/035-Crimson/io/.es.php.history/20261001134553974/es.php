<?php

/*
    Crimson ERP
    API E/S

    Acciones:
    - selectone
    - selectmany
    - insertone
    - insertmany
*/

header("Content-Type: application/json; charset=utf-8");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");

require_once __DIR__ . "/../api/auth_common.php";


// ============================================================
// CONFIGURACIÓN
// ============================================================

// IMPORTANTE:
// Cambiar esta clave antes de usar la API en producción.
const CRIMSON_API_KEY = "cambia-esta-clave-por-una-clave-segura";


// ============================================================
// FUNCIONES
// ============================================================

function responder($datos, $codigo = 200)
{
    http_response_code($codigo);

    echo json_encode(
        $datos,
        JSON_UNESCAPED_UNICODE |
        JSON_PRETTY_PRINT |
        JSON_UNESCAPED_SLASHES
    );

    exit;
}


function obtenerApiKey()
{
    // Método recomendado:
    // X-API-Key: clave

    if (isset($_SERVER["HTTP_X_API_KEY"])) {
        return trim($_SERVER["HTTP_X_API_KEY"]);
    }

    // Alternativa:
    // Authorization: Bearer clave

    $authorization = $_SERVER["HTTP_AUTHORIZATION"] ?? "";

    if (preg_match('/^Bearer\s+(.+)$/i', $authorization, $coincidencias)) {
        return trim($coincidencias[1]);
    }

    return "";
}


function comprobarApiKey()
{
    $clave = obtenerApiKey();

    if (
        $clave === "" ||
        !hash_equals(CRIMSON_API_KEY, $clave)
    ) {
        responder([
            "ok" => false,
            "error" => "API key no válida."
        ], 401);
    }
}


function identificadorSQL($texto)
{
    return '"' . str_replace('"', '""', $texto) . '"';
}


function comprobarTabla($db, $tabla)
{
    if ($tabla === "") {
        responder([
            "ok" => false,
            "error" => "Debes indicar una tabla."
        ], 400);
    }

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
        responder([
            "ok" => false,
            "error" => "La tabla solicitada no existe."
        ], 404);
    }
}


function obtenerColumnas($db, $tabla)
{
    $consulta = $db->query(
        "PRAGMA table_info(" . identificadorSQL($tabla) . ")"
    );

    $columnas = [];

    foreach ($consulta->fetchAll(PDO::FETCH_ASSOC) as $columna) {
        $columnas[] = $columna["name"];
    }

    return $columnas;
}


function limpiarDatos($datos, $columnas)
{
    $resultado = [];

    foreach ($datos as $campo => $valor) {

        // Identificador es automático
        if ($campo === "Identificador") {
            continue;
        }

        if (!in_array($campo, $columnas, true)) {
            responder([
                "ok" => false,
                "error" => "El campo '" . $campo . "' no existe."
            ], 400);
        }

        $resultado[$campo] = $valor;
    }

    return $resultado;
}


function leerJSON()
{
    $contenido = file_get_contents("php://input");

    if ($contenido === false || trim($contenido) === "") {
        responder([
            "ok" => false,
            "error" => "No se han enviado datos JSON."
        ], 400);
    }

    $datos = json_decode($contenido, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        responder([
            "ok" => false,
            "error" => "JSON no válido.",
            "debug" => json_last_error_msg()
        ], 400);
    }

    return $datos;
}


// ============================================================
// AUTENTICACIÓN
// ============================================================

comprobarApiKey();


// ============================================================
// PARÁMETROS GENERALES
// ============================================================

$accion = strtolower(trim($_GET["accion"] ?? ""));
$tabla  = trim($_GET["tabla"] ?? "");


// ============================================================
// CONEXIÓN
// ============================================================

try {

    $db = crimsonDb();

    comprobarTabla($db, $tabla);

    $columnas = obtenerColumnas($db, $tabla);

    $tablaSQL = identificadorSQL($tabla);


    // ========================================================
    // SELECT ONE
    // ========================================================

    if ($accion === "selectone") {

        if ($_SERVER["REQUEST_METHOD"] !== "GET") {
            responder([
                "ok" => false,
                "error" => "selectone requiere GET."
            ], 405);
        }

        $id = $_GET["id"] ?? "";

        if ($id === "") {
            responder([
                "ok" => false,
                "error" => "Debes indicar id."
            ], 400);
        }

        $consulta = $db->prepare(
            "SELECT *
             FROM " . $tablaSQL . "
             WHERE " . identificadorSQL("Identificador") . " = :id
             LIMIT 1"
        );

        $consulta->execute([
            ":id" => $id
        ]);

        $registro = $consulta->fetch(PDO::FETCH_ASSOC);

        if (!$registro) {
            responder([
                "ok" => false,
                "error" => "Registro no encontrado."
            ], 404);
        }

        responder([
            "ok" => true,
            "accion" => "selectone",
            "tabla" => $tabla,
            "datos" => $registro
        ]);
    }


    // ========================================================
    // SELECT MANY
    // ========================================================

    if ($accion === "selectmany") {

        if ($_SERVER["REQUEST_METHOD"] !== "GET") {
            responder([
                "ok" => false,
                "error" => "selectmany requiere GET."
            ], 405);
        }

        $consulta = $db->query(
            "SELECT * FROM " . $tablaSQL
        );

        $datos = $consulta->fetchAll(PDO::FETCH_ASSOC);

        responder([
            "ok" => true,
            "accion" => "selectmany",
            "tabla" => $tabla,
            "total" => count($datos),
            "datos" => $datos
        ]);
    }


    // ========================================================
    // INSERT ONE
    // ========================================================

    if ($accion === "insertone") {

        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            responder([
                "ok" => false,
                "error" => "insertone requiere POST."
            ], 405);
        }

        $datos = leerJSON();

        if (!is_array($datos)) {
            responder([
                "ok" => false,
                "error" => "Debes enviar un objeto JSON."
            ], 400);
        }

        $datos = limpiarDatos($datos, $columnas);

        if (count($datos) === 0) {
            responder([
                "ok" => false,
                "error" => "No hay campos para insertar."
            ], 400);
        }

        $campos = [];
        $marcadores = [];
        $valores = [];

        foreach ($datos as $campo => $valor) {

            $campos[] = identificadorSQL($campo);
            $marcadores[] = ":" . $campo;
            $valores[":" . $campo] = $valor;
        }

        $sql =
            "INSERT INTO " . $tablaSQL .
            " (" . implode(",", $campos) . ")" .
            " VALUES (" . implode(",", $marcadores) . ")";

        $consulta = $db->prepare($sql);
        $consulta->execute($valores);

        responder([
            "ok" => true,
            "accion" => "insertone",
            "tabla" => $tabla,
            "Identificador" => (int)$db->lastInsertId()
        ], 201);
    }


    // ========================================================
    // INSERT MANY
    // ========================================================

    if ($accion === "insertmany") {

        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            responder([
                "ok" => false,
                "error" => "insertmany requiere POST."
            ], 405);
        }

        $registros = leerJSON();

        if (
            !is_array($registros) ||
            count($registros) === 0 ||
            !isset($registros[0]) ||
            !is_array($registros[0])
        ) {
            responder([
                "ok" => false,
                "error" => "Debes enviar un array de objetos JSON."
            ], 400);
        }

        $insertados = [];

        $db->beginTransaction();

        try {

            foreach ($registros as $numero => $registro) {

                if (!is_array($registro)) {
                    throw new Exception(
                        "El registro " . ($numero + 1) . " no es válido."
                    );
                }

                $datos = limpiarDatos($registro, $columnas);

                if (count($datos) === 0) {
                    throw new Exception(
                        "El registro " . ($numero + 1) . " no contiene campos."
                    );
                }

                $campos = [];
                $marcadores = [];
                $valores = [];

                foreach ($datos as $campo => $valor) {

                    $campos[] = identificadorSQL($campo);

                    $marcador = ":campo_" . count($marcadores);

                    $marcadores[] = $marcador;
                    $valores[$marcador] = $valor;
                }

                $sql =
                    "INSERT INTO " . $tablaSQL .
                    " (" . implode(",", $campos) . ")" .
                    " VALUES (" . implode(",", $marcadores) . ")";

                $consulta = $db->prepare($sql);
                $consulta->execute($valores);

                $insertados[] = (int)$db->lastInsertId();
            }

            $db->commit();

        } catch (Throwable $error) {

            if ($db->inTransaction()) {
                $db->rollBack();
            }

            throw $error;
        }

        responder([
            "ok" => true,
            "accion" => "insertmany",
            "tabla" => $tabla,
            "total" => count($insertados),
            "Identificadores" => $insertados
        ], 201);
    }


    // ========================================================
    // ACCIÓN DESCONOCIDA
    // ========================================================

    responder([
        "ok" => false,
        "error" => "Acción no válida.",
        "acciones" => [
            "selectone",
            "selectmany",
            "insertone",
            "insertmany"
        ]
    ], 400);


} catch (Throwable $error) {

    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }

    responder([
        "ok" => false,
        "error" => "Error interno de la API",
        "debug" => $error->getMessage()
    ], 500);
}
