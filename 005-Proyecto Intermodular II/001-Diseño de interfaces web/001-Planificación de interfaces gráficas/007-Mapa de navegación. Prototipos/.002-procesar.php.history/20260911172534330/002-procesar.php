<?php

$xml = simplexml_load_file("https://jocarsa.com/sitemap.xml");

$array = json_decode(
    json_encode($xml),
    true
);

var_dump($array);

?>