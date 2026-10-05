<?php
jocarsabbdd_configurar(__DIR__ . '/../../config.json');
jocarsabbdd_usar_base('demo_conectores');

jocarsabbdd_iniciar_transaccion();
jocarsabbdd_insertar('productos', ['Temporal', '1', '999', 'FALSO']);
print_r(jocarsabbdd_consultar("SELECCIONAR CONTAR(*) COMO total DE productos"));
jocarsabbdd_deshacer();

jocarsabbdd_crear_vista(
    'productos_caros',
    "SELECCIONAR nombre,precio DE productos DONDE precio > 20"
);
print_r(jocarsabbdd_consultar("SELECCIONAR * DE productos_caros"));
