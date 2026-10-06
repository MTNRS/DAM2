<?php
jocarsabbdd_crear_base("ejemplo_fases14_php");
jocarsabbdd_usar_base("ejemplo_fases14_php");
jocarsabbdd_crear_tabla_tipada("clientes", [
    "nombre"=>"TEXTO", "email"=>"TEXTO", "nacimiento"=>"FECHA", "saldo"=>"DECIMAL"
]);
jocarsabbdd_crear_indice("idx_email", "clientes", "email");
jocarsabbdd_insertar("clientes", ["Ana","ana@x.es","2026-01-10","100.25"]);
print_r(jocarsabbdd_indices("clientes"));
echo jocarsabbdd_explicar("SELECCIONAR * DE clientes DONDE email = 'ana@x.es'");
print_r(jocarsabbdd_consultar("SELECCIONAR c.nombre DE clientes c"));
?>
