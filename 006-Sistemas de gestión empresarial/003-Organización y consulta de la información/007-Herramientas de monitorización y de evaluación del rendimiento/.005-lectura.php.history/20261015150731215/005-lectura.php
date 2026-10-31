<?php
$archivo = fopen("/home/josevicente/log/registros.csv", "r");

if ($archivo) {
    while (($fila = fgetcsv($archivo, 1000, ",")) !== false) {
        echo "Fecha: " . $fila[0] . "<br>";
        echo "CPU: " . $fila[1] . "%<br>";
        echo "RAM: " . $fila[2] . "%<br>";
        echo "Disco: " . $fila[3] . "%<br>";
        echo "<hr>";
    }

    fclose($archivo);
}
?>