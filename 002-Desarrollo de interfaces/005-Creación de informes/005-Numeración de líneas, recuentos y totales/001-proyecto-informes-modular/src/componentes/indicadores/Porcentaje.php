<?php
class Porcentaje extends Indicador {

    public function render() {

        $valor = (float) $this->valor;

        $valorBarra = max(0, min(100, $valor));

        $html = '<div class="indicador porcentaje">';

        $html .= '<div class="indicador-etiqueta">';
        $html .= escapar($this->etiqueta);
        $html .= '</div>';

        $html .= '<div class="indicador-valor">';
        $html .= escapar($valor);
        $html .= '%</div>';

        $html .= '<div class="porcentaje-barra">';

        $html .= '<div class="porcentaje-progreso" '
            . 'style="width:'
            . $valorBarra
            . '%"></div>';

        $html .= '</div>';

        $html .= '<div class="indicador-descripcion">';
        $html .= escapar($this->descripcion);
        $html .= '</div>';

        $html .= '</div>';

        return $html;

    }

}


// ============================================================
// CONTENEDOR
// ============================================================
