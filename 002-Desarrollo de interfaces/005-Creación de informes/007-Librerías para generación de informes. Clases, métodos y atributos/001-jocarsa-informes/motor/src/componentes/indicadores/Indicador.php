<?php
abstract class Indicador extends Componente {

    public $etiqueta;
    public $valor;
    public $descripcion;

    public function __construct(
        $etiqueta,
        $valor,
        $descripcion = ""
    ) {

        parent::__construct();

        $this->etiqueta = $etiqueta;
        $this->valor = $valor;
        $this->descripcion = $descripcion;

    }

}


// ============================================================
// KPI
// ============================================================
