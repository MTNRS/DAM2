<?php
class GraficaLineas extends Grafica {

    public function render() {

        $svg = $this->iniciarSVG();

        $cantidad = count($this->valores);

        if ($cantidad === 0) {

            $svg .= '<text x="50%" y="50%" '
                . 'text-anchor="middle" fill="#64748b">'
                . 'Sin datos</text>';

            $svg .= $this->finalizarSVG();

            return $this->envolverGrafica($svg);

        }

        $escala = $this->calcularEscala();

        $svg .= $this->dibujarEjes($escala);

        $puntos = [];

        foreach ($this->valores as $indice => $valor) {

            if ($cantidad === 1) {
                $x = $escala['izquierda']
                    + $escala['anchoUtil'] / 2;
            } else {
                $x = $escala['izquierda']
                    + $indice
                    * $escala['anchoUtil']
                    / ($cantidad - 1);
            }

            $y = $this->posicionY($valor, $escala);

            $puntos[] = [
                'x' => $x,
                'y' => $y,
                'valor' => $valor,
                'etiqueta' => $this->etiquetas[$indice]
            ];

        }

        $cadenaPuntos = '';

        foreach ($puntos as $punto) {

            $cadenaPuntos .= $punto['x']
                . ','
                . $punto['y']
                . ' ';

        }

        $svg .= '<polyline '
            . 'points="' . trim($cadenaPuntos) . '" '
            . 'fill="none" '
            . 'stroke="#2563eb" '
            . 'stroke-width="3" '
            . 'stroke-linecap="round" '
            . 'stroke-linejoin="round"/>';

        foreach ($puntos as $punto) {

            $svg .= '<circle '
                . 'cx="' . $punto['x'] . '" '
                . 'cy="' . $punto['y'] . '" '
                . 'r="5" '
                . 'fill="#ffffff" '
                . 'stroke="#2563eb" '
                . 'stroke-width="3"/>';

            $svg .= '<text '
                . 'x="' . $punto['x'] . '" '
                . 'y="' . ($punto['y'] - 12) . '" '
                . 'text-anchor="middle" '
                . 'font-size="11" '
                . 'fill="#334155">'
                . escapar($punto['valor'])
                . '</text>';

            $svg .= '<text '
                . 'x="' . $punto['x'] . '" '
                . 'y="' . ($this->alto - 15) . '" '
                . 'text-anchor="middle" '
                . 'font-size="11" '
                . 'fill="#475569">'
                . escapar($punto['etiqueta'])
                . '</text>';

        }

        $svg .= $this->finalizarSVG();

        return $this->envolverGrafica($svg);

    }

}


// ============================================================
// GRAFICA CIRCULAR
// ============================================================
