<?php
	$frutas = ['manzana','pera','platano'];
  var_dump($frutas);
  array_push($frutas,"fresa");
  echo "\n";
  var_dump($frutas);
  echo "\n";
  array_pop($frutas);
  var_dump($frutas);
  echo "\n";
  array_splice($frutas,1);
  var_dump($frutas);
  echo "\n";
  echo "\n";
?>