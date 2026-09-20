<?php

    $archivo = fopen("agenda.txt", 'w');
    fwrite($archivo, "Este es un contenido que escribo desde PHP");
    fclose($archivo);
   
?>