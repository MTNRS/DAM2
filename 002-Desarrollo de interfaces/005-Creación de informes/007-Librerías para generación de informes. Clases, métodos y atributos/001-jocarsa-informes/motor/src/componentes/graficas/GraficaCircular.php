<?php
class GraficaCircular extends Grafica {

    public function render() {

        $svg = $this->iniciarSVG();

        foreach ($this->valores as $valor) {

            if ($valor < 0) {

                throw new InvalidArgumentException(
                    "Una gráfica circular no admite valores negativos."
                );

            }

        }

        $total = array_sum($this->valores);

        if ($total <= 0) {

            $svg .= '<text x="50%" y="50%" '
                . 'text-anchor="middle" fill="#64748b">'
                . 'Sin datos</text>';

            $svg .= $this->finalizarSVG();

            return $this->envolverGrafica($svg);

        }

        $centroX = $this->ancho * 0.35;
        $centroY = $this->alto / 2;

        $radio = min(
            $this->alto * 0.38,
            $this->ancho * 0.25
        );

        $anguloActual = -90;

        foreach ($this->valores as $indice => $valor) {

            if ($valor == 0) {
                continue;
            }

            $porcentaje = $valor / $total;
            $angulo = $porcentaje * 360;

            $color = $this->colores[
                $indice % count($this->colores)
            ];

            if ($porcentaje >= 0.999999) {

                $svg .= '<circle '
                    . 'cx="' . $centroX . '" '
                    . 'cy="' . $centroY . '" '
                    . 'r="' . $radio . '" '
                    . 'fill="' . $color . '"/>';

            } else {

                $inicioRadianes = deg2rad($anguloActual);
                $finRadianes = deg2rad(
                    $anguloActual + $angulo
                );

                $x1 = $centroX
                    + $radio * cos($inicioRadianes);

                $y1 = $centroY
                    + $radio * sin($inicioRadianes);

                $x2 = $centroX
                    + $radio * cos($finRadianes);

                $y2 = $centroY
                    + $radio * sin($finRadianes);

                $arcoGrande = $angulo > 180 ? 1 : 0;

                $ruta = 'M ' . $centroX . ' ' . $centroY;
                $ruta .= ' L ' . $x1 . ' ' . $y1;
                $ruta .= ' A ' . $radio . ' ' . $radio;
                $ruta .= ' 0 ' . $arcoGrande . ' 1 ';
                $ruta .= $x2 . ' ' . $y2;
                $ruta .= ' Z';

                $svg .= '<path '
                    . 'd="' . $ruta . '" '
                    . 'fill="' . $color . '" '
                    . 'stroke="white" '
                    . 'stroke-width="2"/>';

            }

            $anguloActual += $angulo;

        }

        // Leyenda

        $leyendaX = $this->ancho * 0.65;
        $leyendaY = 35;

        foreach ($this->etiquetas as $indice => $etiqueta) {

            $color = $this->colores[
                $indice % count($this->colores)
            ];

            $porcentaje = round(
                $this->valores[$indice] / $total * 100,
                1
            );

            $y = $leyendaY + $indice * 26;

            $svg .= '<rect '
                . 'x="' . $leyendaX . '" '
                . 'y="' . ($y - 10) . '" '
                . 'width="12" '
                . 'height="12" '
                . 'rx="2" '
                . 'fill="' . $color . '"/>';

            $svg .= '<text '
                . 'x="' . ($leyendaX + 20) . '" '
                . 'y="' . $y . '" '
                . 'font-size="12" '
                . 'fill="#334155">'
                . escapar($etiqueta)
                . ' (' . $porcentaje . '%)'
                . '</text>';

        }

        $svg .= $this->finalizarSVG();

        return $this->envolverGrafica($svg);

    }

}


// ============================================================
// FAMILIA INDICADORES
// ============================================================
