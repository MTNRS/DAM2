<?php
jocarsabbdd_configurar(__DIR__ . '/../../config.json');
jocarsabbdd_usar_base('demo_conectores');

$resultado = jocarsabbdd_consultar(
    "SELECCIONAR nombre,precio DE productos " .
    "DONDE precio > (SELECCIONAR MEDIA(precio) DE productos)"
);
print_r($resultado);
