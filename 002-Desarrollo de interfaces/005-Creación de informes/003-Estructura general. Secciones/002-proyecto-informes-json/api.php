<?php
declare(strict_types=1);
require_once __DIR__ . '/motor-informes.php';
require_once __DIR__ . '/Informe.php';
require_once __DIR__ . '/ProcesadorJSON.php';
require_once __DIR__ . '/plantilla.php';
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Utiliza POST con Content-Type: application/json'], JSON_UNESCAPED_UNICODE);
    exit;
}
if (!str_starts_with(strtolower(trim(explode(';', $_SERVER['CONTENT_TYPE'] ?? '')[0])), 'application/json')) {
    http_response_code(415);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Content-Type debe ser application/json'], JSON_UNESCAPED_UNICODE);
    exit;
}
try {
    $maxBytes = 1024 * 1024;
    if (isset($_SERVER['CONTENT_LENGTH']) && (int)$_SERVER['CONTENT_LENGTH'] > $maxBytes) {
        http_response_code(413);
        throw new RuntimeException('JSON demasiado grande (máximo 1 MiB).');
    }
    $raw = file_get_contents('php://input', false, null, 0, $maxBytes + 1);
    if ($raw === false || strlen($raw) > $maxBytes) {
        http_response_code(413);
        throw new RuntimeException('JSON demasiado grande (máximo 1 MiB).');
    }
    $datos = json_decode($raw, true, 128, JSON_THROW_ON_ERROR);
    if (!is_array($datos) || array_is_list($datos)) throw new InvalidArgumentException('La raíz debe ser un objeto JSON.');
    $informe = (new ProcesadorJSON())->procesar($datos);
    header('Content-Type: text/html; charset=utf-8');
    header("Content-Security-Policy: default-src 'none'; style-src 'self'; img-src 'self' data:; base-uri 'none'; form-action 'none'");
    echo paginaHTML($informe);
} catch (JsonException|InvalidArgumentException $e) {
    http_response_code(400);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
} catch (RuntimeException $e) {
    if (http_response_code() < 400) http_response_code(413);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    error_log((string)$e);
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Error interno al generar el informe'], JSON_UNESCAPED_UNICODE);
}
