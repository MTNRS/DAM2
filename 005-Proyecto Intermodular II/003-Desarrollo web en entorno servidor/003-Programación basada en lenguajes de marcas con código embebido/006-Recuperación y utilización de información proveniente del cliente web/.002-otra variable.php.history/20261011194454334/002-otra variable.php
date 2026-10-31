<?php
	
  $edad = $_GET['edad'];
  $nombre = $_GET['nombre'];
  
  echo "El usuario ha introducido: ".$edad." años<br>";
  $doble = $edad * 2;
  echo "El doble de la edad es de ".$doble." años<br>";
  
?>