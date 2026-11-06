<?php
require_once __DIR__ . '/motor-informes.php';
require_once __DIR__ . '/Informe.php';
require_once __DIR__ . '/ComponentesExtendidos.php';
require_once __DIR__ . '/ProcesadorJSON.php';
require_once __DIR__ . '/plantilla.php';
$datos = json_decode(file_get_contents(__DIR__ . '/ejemplo-completo.json'), true, 128, JSON_THROW_ON_ERROR);
echo paginaHTML((new ProcesadorJSON())->procesar($datos));
