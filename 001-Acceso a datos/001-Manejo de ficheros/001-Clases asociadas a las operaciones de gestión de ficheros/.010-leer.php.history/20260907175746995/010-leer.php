<?php

    $archivo = fopen("agenda.txt", 'r');
    $lineas = fread($archivo, 1024); // Changed the second argument to 1024
    echo $lineas; // Changed var_dump to echo
    fclose($archivo);
   
?>