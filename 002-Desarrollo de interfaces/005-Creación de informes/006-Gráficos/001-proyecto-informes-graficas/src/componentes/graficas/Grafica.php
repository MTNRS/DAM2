<?php
abstract class Grafica extends Componente {

    public $titulo;
    public $etiquetas;
    public $valores;
    public $ancho;
    public $alto;
    public $colores;

    public function __construct(
        $titulo,
        $etiquetas,
        $valores,
        $ancho = 600,
        $alto = 300
    ) {

        parent::__construct();

        if (count($etiquetas) !== count($valores)) {
            throw new InvalidArgumentException(
                "El número de etiquetas y valores debe coincidir."
            );
        }

        foreach ($valores as $valor) {

            if (!is_numeric($valor)) {
                throw new InvalidArgumentException(
                    "Todos los valores de la gráfica deben ser numéricos."
                );
            }

        }

        $this->titulo = $titulo;
        $this->etiquetas = array_values($etiquetas);
        $this->valores = array_map(
            'floatval',
            array_values($valores)
        );

        $this->ancho = max(300, (int) $ancho);
        $this->alto = max(200, (int) $alto);

        $this->colores = [
            '#2563eb',
            '#16a34a',
            '#f59e0b',
            '#dc2626',
            '#9333ea',
            '#0891b2',
            '#db2777',
            '#65a30d'
        ];

    }

    protected function iniciarSVG() {

        return '<svg '
            . 'xmlns="http://www.w3.org/2000/svg" '
            . 'viewBox="0 0 '
            . $this->ancho . ' '
            . $this->alto . '" '
            . 'role="img" '
            . 'aria-label="' . escapar($this->titulo) . '" '
            . 'class="grafica-svg">';

    }

    protected function finalizarSVG() {

        return '</svg>';

    }

    protected function envolverGrafica($svg) {

        $html = '<div class="grafica">';

        $html .= '<h3 class="grafica-titulo">';
        $html .= escapar($this->titulo);
        $html .= '</h3>';

        $html .= $svg;

        $html .= '</div>';

        return $html;

    }

    protected function calcularEscala($margenInferior = 45) {

        $minimo = min(0, ...$this->valores);
        $maximo = max(0, ...$this->valores);

        if ($minimo === $maximo) {
            $maximo = $minimo + 1;
        }

        $izquierda = 60;
        $derecha = 25;
        $arriba = 25;
        $abajo = $margenInferior;

        $anchoUtil = $this->ancho - $izquierda - $derecha;
        $altoUtil = $this->alto - $arriba - $abajo;

        return [
            'minimo' => $minimo,
            'maximo' => $maximo,
            'izquierda' => $izquierda,
            'arriba' => $arriba,
            'anchoUtil' => $anchoUtil,
            'altoUtil' => $altoUtil
        ];

    }

    protected function posicionY($valor, $escala) {

        $rango = $escala['maximo'] - $escala['minimo'];

        return $escala['arriba']
            + (
                ($escala['maximo'] - $valor)
                / $rango
            ) * $escala['altoUtil'];

    }

    protected function dibujarEjes($escala) {

        $svg = '';

        $minimo = $escala['minimo'];
        $maximo = $escala['maximo'];

        $izquierda = $escala['izquierda'];
        $arriba = $escala['arriba'];

        $anchoUtil = $escala['anchoUtil'];
        $altoUtil = $escala['altoUtil'];

        for ($i = 0; $i <= 5; $i++) {

            $valor = $minimo
                + ($maximo - $minimo) * $i / 5;

            $y = $this->posicionY($valor, $escala);

            $svg .= '<line '
                . 'x1="' . $izquierda . '" '
                . 'y1="' . $y . '" '
                . 'x2="' . ($izquierda + $anchoUtil) . '" '
                . 'y2="' . $y . '" '
                . 'stroke="#e5e7eb" '
                . 'stroke-width="1"/>';

            $svg .= '<text '
                . 'x="' . ($izquierda - 10) . '" '
                . 'y="' . ($y + 4) . '" '
                . 'text-anchor="end" '
                . 'font-size="11" '
                . 'fill="#64748b">'
                . escapar(round($valor, 1))
                . '</text>';

        }

        $yCero = $this->posicionY(0, $escala);

        $svg .= '<line '
            . 'x1="' . $izquierda . '" '
            . 'y1="' . $yCero . '" '
            . 'x2="' . ($izquierda + $anchoUtil) . '" '
            . 'y2="' . $yCero . '" '
            . 'stroke="#475569" '
            . 'stroke-width="1.5"/>';

        return $svg;

    }

}


// ============================================================
// GRAFICA DE BARRAS
// ============================================================
