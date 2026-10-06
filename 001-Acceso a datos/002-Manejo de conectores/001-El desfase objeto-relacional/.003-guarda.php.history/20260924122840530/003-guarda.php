<?php
  $datos = file_get_contents("php://input");

  $archivo = fopen("jugadores.json", "w");
  fwrite($archivo, $datos);
  fclose($archivo);
?>