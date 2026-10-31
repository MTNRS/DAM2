<?php
header('Content-Type: text/plain; charset=utf-8');
$required=['jocarsabbdd_listar_bases','jocarsabbdd_usar_base','jocarsabbdd_listar_tablas','jocarsabbdd_crear_tabla_tipada','jocarsabbdd_esquema','jocarsabbdd_tipos','jocarsabbdd_claves_foraneas','jocarsabbdd_insertar_columnas','jocarsabbdd_seleccionar','jocarsabbdd_listar','jocarsabbdd_buscar','jocarsabbdd_actualizar_donde','jocarsabbdd_eliminar','jocarsabbdd_iniciar_transaccion','jocarsabbdd_confirmar','jocarsabbdd_deshacer'];
$missing=array_values(array_filter($required,fn($f)=>!function_exists($f)));
echo $missing?'FALTAN: '.implode(', ',$missing)."\n":"[OK] Conector JocarsaBBDD disponible\n";
if(!$missing){$bases=jocarsabbdd_listar_bases();echo '[INFO] Base crimson: '.(in_array('crimson',$bases,true)?'instalada':'no instalada')."\n";if(in_array('crimson',$bases,true)){jocarsabbdd_usar_base('crimson');echo '[INFO] Tablas: '.implode(', ',jocarsabbdd_listar_tablas())."\n";}}
