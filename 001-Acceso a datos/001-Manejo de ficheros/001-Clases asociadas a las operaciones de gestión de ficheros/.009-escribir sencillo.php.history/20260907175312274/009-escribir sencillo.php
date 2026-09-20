<?php

    $archivo = fopen($ruta, 'w');
    fwrite($archivo, "Este es un contenido que escribo desde PHP");
    fclose($archivo);
   
?>