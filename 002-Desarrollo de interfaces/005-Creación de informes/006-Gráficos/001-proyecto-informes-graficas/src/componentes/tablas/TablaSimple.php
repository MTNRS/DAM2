<?php
class TablaSimple extends Tabla {
    private array $opciones = [];
    public function configurarOpciones(array $opciones): void { $this->opciones = $opciones; }

    public function render() {

        $html = '<div class="tabla-contenedor">';
        $html .= '<table class="tabla-simple">';

        // Cabecera

        $html .= '<thead>';
        $html .= '<tr>';

        foreach ($this->columnas as $columna) {

            $html .= '<th>';
            $html .= escapar($columna);
            $html .= '</th>';

        }

        $html .= '</tr>';
        $html .= '</thead>';

        // Cuerpo

        $html .= '<tbody>';

        foreach ($this->filas as $fila) {

            $html .= '<tr>';

            foreach ($this->columnas as $indice => $columna) {

                $valor = $fila[$indice] ?? '';

                $html .= '<td>';
                $html .= escapar($valor);
                $html .= '</td>';

            }

            $html .= '</tr>';

        }

        $html .= '</tbody>';
        $html .= '</table>';
        $html .= '</div>';

        return PresentacionTabla::aplicar($html, $this->opciones);

    }

}


// ============================================================
// FAMILIA GRAFICA
// ============================================================
