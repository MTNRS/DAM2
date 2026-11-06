<?php
abstract class Tabla extends Componente {

    public $columnas;
    public $filas;

    public function __construct($columnas, $filas) {

        parent::__construct();

        $this->columnas = $columnas;
        $this->filas = $filas;

    }

}
