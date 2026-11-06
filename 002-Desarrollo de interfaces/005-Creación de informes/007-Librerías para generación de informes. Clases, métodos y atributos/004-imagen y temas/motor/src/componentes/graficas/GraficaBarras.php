<?php
class GraficaBarras extends Grafica {

    public function render() {

        $svg = $this->iniciarSVG();

        if (count($this->valores) === 0) {

            $svg .= '<text x="50%" y="50%" '
                . 'text-anchor="middle" fill="#64748b">'
                . 'Sin datos</text>';

            $svg .= $this->finalizarSVG();

            return $this->envolverGrafica($svg);

        }

        $escala = $this->calcularEscala();

        $svg .= $this->dibujarEjes($escala);

        $cantidad = count($this->valores);

        $anchoGrupo = $escala['anchoUtil'] / $cantidad;
        $anchoBarra = $anchoGrupo * 0.65;

        $yCero = $this->posicionY(0, $escala);

        foreach ($this->valores as $indice => $valor) {

            $x = $escala['izquierda']
                + $indice * $anchoGrupo
                + ($anchoGrupo - $anchoBarra) / 2;

            $yValor = $this->posicionY($valor, $escala);

            $y = min($yValor, $yCero);
            $alto = max(1, abs($yCero - $yValor));

            $color = $this->colores[
                $indice % count($this->colores)
            ];

            $svg .= '<rect '
                . 'x="' . $x . '" '
                . 'y="' . $y . '" '
                . 'width="' . $anchoBarra . '" '
                . 'height="' . $alto . '" '
                . 'rx="3" '
                . 'fill="' . $color . '"/>';

            $centroX = $x + $anchoBarra / 2;

            $svg .= '<text '
                . 'x="' . $centroX . '" '
                . 'y="' . ($this->alto - 15) . '" '
                . 'text-anchor="middle" '
                . 'font-size="11" '
                . 'fill="#475569">'
                . escapar($this->etiquetas[$indice])
                . '</text>';

            $posicionValor = $valor >= 0
                ? $y - 7
                : $y + $alto + 15;

            $svg .= '<text '
                . 'x="' . $centroX . '" '
                . 'y="' . $posicionValor . '" '
                . 'text-anchor="middle" '
                . 'font-size="11" '
                . 'font-weight="bold" '
                . 'fill="#334155">'
                . escapar($valor)
                . '</text>';

        }

        $svg .= $this->finalizarSVG();

        return $this->envolverGrafica($svg);

    }

}


// ============================================================
// GRAFICA DE LINEAS
// ============================================================
