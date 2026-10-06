<?php
  $datos = file_get_contents("php://input");
  $actores = json_decode($datos);

  file_put_contents(
    "jugadores.json",
    json_encode($actores, JSON_PRETTY_PRINT)
  );
?>