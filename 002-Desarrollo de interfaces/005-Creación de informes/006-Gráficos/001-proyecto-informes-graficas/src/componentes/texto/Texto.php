<?php
abstract class Texto extends Componente {

    public $contenido;

    public function __construct($contenido) {
        parent::__construct();

        $this->contenido = $contenido;
    }

}
