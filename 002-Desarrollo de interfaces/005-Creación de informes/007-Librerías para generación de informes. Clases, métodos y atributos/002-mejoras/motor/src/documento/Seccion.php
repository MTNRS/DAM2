<?php
class Seccion {

    public $titulo;
    public $contenedores = [];

    public function __construct($titulo = "") {

        $this->titulo = $titulo;

    }

    public function agregarContenedor(Contenedor $contenedor) {

        $this->contenedores[] = $contenedor;

    }

    public function render() {

        $html = '<section class="seccion">';

        if ($this->titulo !== "") {

            $html .= '<h2 class="seccion-titulo">';
            $html .= escapar($this->titulo);
            $html .= '</h2>';

        }

        foreach ($this->contenedores as $contenedor) {

            $html .= $contenedor->render();

        }

        $html .= '</section>';

        return $html;

    }

}


// ============================================================
// PAGINA
// ============================================================
