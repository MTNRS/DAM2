<?php
class Parrafo extends Texto {

    public function render() {

        return '<p class="componente-parrafo">'
            . nl2br(escapar($this->contenido))
            . '</p>';

    }

}


// ============================================================
// FAMILIA TABLA
// ============================================================
