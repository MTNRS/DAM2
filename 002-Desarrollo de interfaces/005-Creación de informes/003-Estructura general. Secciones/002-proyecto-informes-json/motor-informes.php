
<?php

// ============================================================
// FUNCIONES AUXILIARES
// ============================================================

function escapar($valor) {
    return htmlspecialchars(
        (string) $valor,
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );
}


// ============================================================
// SUPERCLASE ABSTRACTA COMPONENTE
// ============================================================

abstract class Componente {

    public $claseCSS;

    public function __construct($claseCSS = "") {
        $this->claseCSS = $claseCSS;
    }

    abstract public function render();

}


// ============================================================
// FAMILIA TEXTO
// ============================================================

abstract class Texto extends Componente {

    public $contenido;

    public function __construct($contenido) {
        parent::__construct();

        $this->contenido = $contenido;
    }

}


class Titulo extends Texto {

    public function render() {

        return '<h1 class="componente-titulo">'
            . escapar($this->contenido)
            . '</h1>';

    }

}


class Subtitulo extends Texto {

    public function render() {

        return '<h2 class="componente-subtitulo">'
            . escapar($this->contenido)
            . '</h2>';

    }

}


class Encabezado extends Texto {

    public $nivel;

    public function __construct($contenido, $nivel = 3) {

        parent::__construct($contenido);

        $this->nivel = max(3, min(6, (int) $nivel));

    }

    public function render() {

        $etiqueta = 'h' . $this->nivel;

        return '<' . $etiqueta . ' class="componente-encabezado">'
            . escapar($this->contenido)
            . '</' . $etiqueta . '>';

    }

}


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

abstract class Tabla extends Componente {

    public $columnas;
    public $filas;

    public function __construct($columnas, $filas) {

        parent::__construct();

        $this->columnas = $columnas;
        $this->filas = $filas;

    }

}


class TablaSimple extends Tabla {

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

        return $html;

    }

}


// ============================================================
// FAMILIA GRAFICA
// ============================================================

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

abstract class Indicador extends Componente {

    public $etiqueta;
    public $valor;
    public $descripcion;

    public function __construct(
        $etiqueta,
        $valor,
        $descripcion = ""
    ) {

        parent::__construct();

        $this->etiqueta = $etiqueta;
        $this->valor = $valor;
        $this->descripcion = $descripcion;

    }

}


// ============================================================
// KPI
// ============================================================

class KPI extends Indicador {

    public function render() {

        $html = '<div class="indicador kpi">';

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
// CONTADOR
// ============================================================

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

class Contenedor {

    public $componentes = [];

    public function agregarComponente(Componente $componente) {

        $this->componentes[] = $componente;

    }

    public function render() {

        $html = '<div class="contenedor">';

        foreach ($this->componentes as $componente) {

            $html .= $componente->render();

        }

        $html .= '</div>';

        return $html;

    }

}


// ============================================================
// SECCION
// ============================================================

class Seccion {

    public $titulo;
    public $contenedores = [];

    public function __construct($titulo = "") {

        $this->titulo = $titulo;

    }

    public function agregarContenedor(Contenedor $contenedor) {

        $this->contenedores[] = $contenedor;

    }

    public function render() {

        $html = '<section class="seccion">';

        if ($this->titulo !== "") {

            $html .= '<h2 class="seccion-titulo">';
            $html .= escapar($this->titulo);
            $html .= '</h2>';

        }

        foreach ($this->contenedores as $contenedor) {

            $html .= $contenedor->render();

        }

        $html .= '</section>';

        return $html;

    }

}


// ============================================================
// PAGINA
// ============================================================

class Pagina {

    public $numero;
    public $secciones = [];

    public function __construct($numero) {

        $this->numero = $numero;

    }

    public function agregarSeccion(Seccion $seccion) {

        $this->secciones[] = $seccion;

    }

    public function render() {

        $html = '<article class="pagina">';

        foreach ($this->secciones as $seccion) {

            $html .= $seccion->render();

        }

        $html .= '<footer class="numero-pagina">';
        $html .= 'Página ' . escapar($this->numero);
        $html .= '</footer>';

        $html .= '</article>';

        return $html;

    }

}


// ============================================================
// DOCUMENTO
// ============================================================

class Documento {

    public $titulo;
    public $paginas = [];

    public function __construct($titulo) {

        $this->titulo = $titulo;

    }

    public function agregarPagina(Pagina $pagina) {

        $this->paginas[] = $pagina;

    }

    public function render() {

        $html = '<header class="documento-header">';

        $html .= '<h1>';
        $html .= escapar($this->titulo);
        $html .= '</h1>';

        $html .= '</header>';

        $html .= '<main class="documento">';

        foreach ($this->paginas as $pagina) {

            $html .= $pagina->render();

        }

        $html .= '</main>';

        return $html;

    }

}
