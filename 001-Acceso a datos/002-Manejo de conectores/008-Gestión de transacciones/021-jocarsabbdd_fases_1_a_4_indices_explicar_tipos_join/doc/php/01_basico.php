<?php
// Ejecutar desde la raíz del proyecto:
// php -d extension=./jocarsabbdd.so doc/php/01_basico.php

jocarsabbdd_configurar(__DIR__ . '/../../config.json');
jocarsabbdd_crear_base('demo_conectores');
jocarsabbdd_usar_base('demo_conectores');
jocarsabbdd_crear_tabla_tipada('productos', [
    'nombre' => 'TEXTO',
    'stock' => 'ENTERO',
    'precio' => 'REAL',
    'disponible' => 'BOOLEANO',
]);

$id = jocarsabbdd_insertar('productos', ['Teclado', '10', '25.50', 'VERDADERO']);
echo "ID insertado: $id\n";

print_r(jocarsabbdd_listar('productos'));
print_r(jocarsabbdd_consultar(
    "SELECCIONAR nombre,precio DE productos DONDE precio > 20"
));
