<?php

    $archivo = fopen("agenda.txt", 'r');
    $lineas = fread($archivo);
    var_dump($archivo);
    fclose($archivo);
   
?>