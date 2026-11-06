<?php
class Titulo extends Texto {

    public function render() {

        return '<h1 class="componente-titulo">'
            . escapar($this->contenido)
            . '</h1>';

    }

}
