<?php
/**
 * Cliente de prueba para jocarsa | informes.
 * Coloca este archivo junto a ejemplo-completo.json.
 * AVISO: contiene una API key privada; úsalo localmente o restringe el acceso.
 */
declare(strict_types=1);

$apiBase = 'http://localhost/tame2627dam2/002-Desarrollo%20de%20interfaces/005-Creaci%C3%B3n%20de%20informes/007-Librer%C3%ADas%20para%20generaci%C3%B3n%20de%20informes.%20Clases,%20m%C3%A9todos%20y%20atributos/002-mejoras/api.php';
$apiKey = 'jca_03e6918790a5f6e7340ec3382e6a18e7e5426360b97d0ce4d0f1c8bfd493d8c1';
$ruta = 'informe-prueba'; // Sustituye por la ruta real creada en «Mis APIs».
$jsonFile = __DIR__ . '/ejemplo-completo.json';

function errorPage(string $title, string $message, int $status = 500): never
{
    http_response_code($status);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><html lang="es"><meta charset="utf-8"><title>Error API</title>';
    echo '<style>body{font:16px system-ui;margin:40px;max-width:850px}pre{white-space:pre-wrap;background:#f3f4f6;padding:20px;border-left:4px solid #c33}</style>';
    echo '<h1>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</h1>';
    echo '<pre>' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</pre></html>';
    exit;
}

if (!extension_loaded('curl')) {
    errorPage('Extensión cURL no disponible', 'Instala php-curl y reinicia Apache.');
}
if (!is_readable($jsonFile)) {
    errorPage('JSON no encontrado', 'No se puede leer: ' . $jsonFile);
}
$json = file_get_contents($jsonFile);
if ($json === false) {
    errorPage('Error de lectura', 'No se ha podido abrir el archivo JSON.');
}
try {
    json_decode($json, true, 512, JSON_THROW_ON_ERROR);
} catch (JsonException $e) {
    errorPage('JSON inválido', $e->getMessage());
}

$url = $apiBase . '?ruta=' . rawurlencode($ruta);
$ch = curl_init($url);
if ($ch === false) {
    errorPage('Error de cURL', 'No se pudo inicializar la conexión.');
}
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $json,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json; charset=utf-8',
        'Accept: text/html',
        'X-API-Key: ' . $apiKey,
    ],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_TIMEOUT => 90,
    CURLOPT_FOLLOWLOCATION => false,
]);
$respuesta = curl_exec($ch);
$httpStatus = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
$contentType = (string) curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
$error = curl_error($ch);
curl_close($ch);

if ($respuesta === false) {
    errorPage('Error de conexión', $error);
}
if ($httpStatus < 200 || $httpStatus >= 300) {
    errorPage('La API devolvió HTTP ' . $httpStatus,
        "URL: " . $url . "\n\nRespuesta:\n" . $respuesta,
        $httpStatus >= 400 && $httpStatus <= 599 ? $httpStatus : 502);
}
if (stripos($contentType, 'text/html') === false) {
    errorPage('Respuesta inesperada', 'Se esperaba HTML, pero la API devolvió ' . $contentType . "\n\n" . $respuesta, 502);
}

// Mostrar directamente el informe generado por la API.
header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-store');
echo $respuesta;
