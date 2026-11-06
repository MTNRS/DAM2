<?php
abstract class Componente {

    public $claseCSS;

    public function __construct($claseCSS = "") {
        $this->claseCSS = $claseCSS;
    }

    abstract public function render();

}


// ============================================================
// FAMILIA TEXTO
// ============================================================
