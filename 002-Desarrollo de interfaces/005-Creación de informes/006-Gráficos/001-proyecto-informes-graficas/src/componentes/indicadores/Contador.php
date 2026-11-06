<?php
class Contador extends Indicador {

    public function render() {

        $html = '<div class="indicador contador">';

        $html .= '<div class="indicador-etiqueta">';
        $html .= escapar($this->etiqueta);
        $html .= '</div>';

        $html .= '<div class="indicador-valor">';
        $html .= escapar($this->valor);
        $html .= '</div>';

        $html .= '<div class="indicador-descripcion">';
        $html .= escapar($this->descripcion);
        $html .= '</div>';

        $html .= '</div>';

        return $html;

    }

}


// ============================================================
// PORCENTAJE
// ============================================================
