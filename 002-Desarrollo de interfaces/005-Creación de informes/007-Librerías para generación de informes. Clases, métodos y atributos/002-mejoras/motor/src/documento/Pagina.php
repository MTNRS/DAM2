<?php
class Pagina {

    public $numero;
    public $secciones = [];

    public function __construct($numero) {

        $this->numero = $numero;

    }

    public function agregarSeccion(Seccion $seccion) {

        $this->secciones[] = $seccion;

    }

    public function render() {

        $html = '<article class="pagina">';

        foreach ($this->secciones as $seccion) {

            $html .= $seccion->render();

        }

        $html .= '<footer class="numero-pagina">';
        $html .= 'Página ' . escapar($this->numero);
        $html .= '</footer>';

        $html .= '</article>';

        return $html;

    }

}


// ============================================================
// DOCUMENTO
// ============================================================
