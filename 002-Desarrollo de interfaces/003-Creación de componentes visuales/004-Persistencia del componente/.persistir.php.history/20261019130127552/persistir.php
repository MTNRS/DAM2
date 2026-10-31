<?php
	$archivo = fopen("guarda.txt",'w');
  fwrite($archivo,$_GET['clave']);
?>