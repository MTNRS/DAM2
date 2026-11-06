<?php
class Documento {

    public $titulo;
    public $paginas = [];

    public function __construct($titulo) {

        $this->titulo = $titulo;

    }

    public function agregarPagina(Pagina $pagina) {

        $this->paginas[] = $pagina;

    }

    public function render() {

        $html = '<header class="documento-header">';

        $html .= '<h1>';
        $html .= escapar($this->titulo);
        $html .= '</h1>';

        $html .= '</header>';

        $html .= '<main class="documento">';

        foreach ($this->paginas as $pagina) {

            $html .= $pagina->render();

        }

        $html .= '</main>';

        return $html;

    }

}
