<?php

$log = '/tmp/jocarsa-apache-debug.log';

function paso($texto)
{
    global $log;

    file_put_contents(
        $log,
        date('H:i:s') . ' ' . $texto . PHP_EOL,
        FILE_APPEND
    );
}

file_put_contents($log, "===== NUEVA PRUEBA =====\n");

paso('0 INICIO');

paso('1 antes extension_loaded');
$resultado = extension_loaded('jocarsabbdd');
paso('1 despues extension_loaded = ' . ($resultado ? 'true' : 'false'));

paso('2 antes configuracion');
$resultado = jocarsabbdd_configuracion();
paso('2 despues configuracion ' . json_encode($resultado));

paso('3 antes listar_bases');
$resultado = jocarsabbdd_listar_bases();
paso('3 despues listar_bases ' . json_encode($resultado));

paso('4 antes crear_base');
$resultado = jocarsabbdd_crear_base('crimson_debug');
paso('4 despues crear_base = ' . var_export($resultado, true));

paso('5 antes usar_base');
$resultado = jocarsabbdd_usar_base('crimson_debug');
paso('5 despues usar_base = ' . var_export($resultado, true));

paso('6 antes listar_tablas');
$resultado = jocarsabbdd_listar_tablas();
paso('6 despues listar_tablas ' . json_encode($resultado));

paso('7 antes crear_tabla_tipada');

$resultado = jocarsabbdd_crear_tabla_tipada(
    'clientes_debug',
    [
        'nombre' => 'TEXTO',
        'email' => 'TEXTO'
    ]
);

paso('7 despues crear_tabla_tipada = ' . var_export($resultado, true));

paso('8 antes listar_tablas');
$resultado = jocarsabbdd_listar_tablas();
paso('8 despues listar_tablas ' . json_encode($resultado));

paso('9 antes insertar');

$resultado = jocarsabbdd_insertar_columnas(
    'clientes_debug',
    ['nombre', 'email'],
    ['Jose Vicente', 'info@jocarsa.com']
);

paso('9 despues insertar = ' . var_export($resultado, true));

paso('10 antes listar');
$resultado = jocarsabbdd_listar('clientes_debug');
paso('10 despues listar ' . json_encode($resultado));

paso('11 FIN');

echo "PRUEBA TERMINADA";