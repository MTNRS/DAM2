<?php
  $datos = file_get_contents("php://input");
  $actores = json_decode($datos, true);

  $db = new SQLite3("jugadores.db");

  $db->exec("
    CREATE TABLE IF NOT EXISTS jugadores (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      x REAL,
      y REAL
    )
  ");

  foreach($actores as $actor){
    $x = $actor["x"];
    $y = $actor["y"];

    $db->exec("
      INSERT INTO jugadores (x,y)
      VALUES ($x,$y)
    ");
  }

  $db->close();
?>