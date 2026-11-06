<?php
/** CLI: php generar.php archivo.json [salida.html] */
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
require_once __DIR__ . '/motor-informes.php';
require_once __DIR__ . '/Informe.php';
require_once __DIR__ . '/ComponentesExtendidos.php';
require_once __DIR__ . '/ProcesadorJSON.php';
require_once __DIR__ . '/plantilla.php';
if ($argc < 2 || $argc > 3) { fwrite(STDERR, "Uso: php generar.php archivo.json [salida.html]\n"); exit(2); }
try {
    $raw = file_get_contents($argv[1]);
    if ($raw === false) throw new RuntimeException('No se puede leer el archivo.');
    $datos = json_decode($raw, true, 128, JSON_THROW_ON_ERROR);
    if (!is_array($datos) || array_is_list($datos)) throw new InvalidArgumentException('La raíz debe ser un objeto JSON.');
    $html = paginaHTML((new ProcesadorJSON())->procesar($datos));
    if ($argc === 3) {
        if (file_put_contents($argv[2], $html) === false) throw new RuntimeException('No se puede escribir el HTML.');
        fwrite(STDERR, "Generado: {$argv[2]}\n");
    } else echo $html;
} catch (Throwable $e) { fwrite(STDERR, 'Error: ' . $e->getMessage() . "\n"); exit(1); }
