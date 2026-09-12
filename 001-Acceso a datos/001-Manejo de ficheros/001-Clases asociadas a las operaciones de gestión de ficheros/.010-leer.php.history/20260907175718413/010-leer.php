<?php

    $archivo = fopen("agenda.txt", 'r');
    $lineas = fread($archivo, 1024); // Changed the second argument to 1024
    var_dump($archivo);
    fclose($archivo);
   
?>