<?php
class Subtitulo extends Texto {

    public function render() {

        return '<h2 class="componente-subtitulo">'
            . escapar($this->contenido)
            . '</h2>';

    }

}
