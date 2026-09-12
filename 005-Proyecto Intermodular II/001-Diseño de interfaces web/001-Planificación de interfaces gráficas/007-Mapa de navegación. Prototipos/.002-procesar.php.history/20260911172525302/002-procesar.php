<?php

$xml = simplexml_load_file("archivo.xml");

$array = json_decode(
    json_encode($xml),
    true
);

var_dump($array);

?>