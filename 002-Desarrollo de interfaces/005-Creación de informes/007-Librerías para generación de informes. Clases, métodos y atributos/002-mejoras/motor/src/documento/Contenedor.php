<?php
class Contenedor {

    public $componentes = [];

    public function agregarComponente(Componente $componente) {

        $this->componentes[] = $componente;

    }

    public function render() {

        $html = '<div class="contenedor">';

        foreach ($this->componentes as $componente) {

            $html .= $componente->render();

        }

        $html .= '</div>';

        return $html;

    }

}


// ============================================================
// SECCION
// ============================================================
