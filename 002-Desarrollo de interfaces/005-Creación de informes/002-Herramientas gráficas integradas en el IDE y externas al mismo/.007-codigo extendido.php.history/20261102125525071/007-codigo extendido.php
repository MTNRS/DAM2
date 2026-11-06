
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


// ============================================================
// CREACION DEL DOCUMENTO DE EJEMPLO
// ============================================================

$documento = new Documento(
    "Informe anual de empresa"
);


// ============================================================
// PAGINA 1: INTRODUCCION E INDICADORES
// ============================================================

$pagina1 = new Pagina(1);

$seccion1 = new Seccion("Presentación");

$contenedor1 = new Contenedor();

$contenedor1->agregarComponente(
    new Titulo("Informe anual 2026")
);

$contenedor1->agregarComponente(
    new Subtitulo("Resultados y evolución empresarial")
);

$contenedor1->agregarComponente(
    new Encabezado("Introducción")
);

$contenedor1->agregarComponente(
    new Parrafo(
        "Este documento presenta los principales "
        . "resultados de la empresa durante el ejercicio 2026."
    )
);

$contenedor1->agregarComponente(
    new Parrafo(
        "Se incluyen indicadores de rendimiento, "
        . "tablas de datos y representaciones gráficas."
    )
);

$seccion1->agregarContenedor($contenedor1);

$pagina1->agregarSeccion($seccion1);


// Indicadores

$seccion2 = new Seccion("Indicadores principales");

$contenedor2 = new Contenedor();

$contenedor2->agregarComponente(
    new KPI(
        "Facturación anual",
        "150.000 €",
        "Ingresos totales del ejercicio"
    )
);

$contenedor2->agregarComponente(
    new Contador(
        "Clientes activos",
        125,
        "Número total de clientes"
    )
);

$contenedor2->agregarComponente(
    new Porcentaje(
        "Objetivos alcanzados",
        78.5,
        "Porcentaje de cumplimiento"
    )
);

$seccion2->agregarContenedor($contenedor2);

$pagina1->agregarSeccion($seccion2);

$documento->agregarPagina($pagina1);


// ============================================================
// PAGINA 2: TABLAS
// ============================================================

$pagina2 = new Pagina(2);

$seccion3 = new Seccion("Datos de ventas");

$contenedor3 = new Contenedor();

$contenedor3->agregarComponente(
    new Encabezado("Ventas por trimestre")
);

$contenedor3->agregarComponente(
    new Parrafo(
        "La siguiente tabla muestra las ventas "
        . "registradas durante cada trimestre."
    )
);

$tabla = new TablaSimple(
    [
        "Trimestre",
        "Ventas",
        "Clientes"
    ],
    [
        ["T1", "25.000 €", 20],
        ["T2", "35.000 €", 28],
        ["T3", "40.000 €", 35],
        ["T4", "50.000 €", 42]
    ]
);

$contenedor3->agregarComponente($tabla);

$seccion3->agregarContenedor($contenedor3);

$pagina2->agregarSeccion($seccion3);

$documento->agregarPagina($pagina2);


// ============================================================
// PAGINA 3: GRAFICAS
// ============================================================

$pagina3 = new Pagina(3);

$seccion4 = new Seccion("Representaciones gráficas");

$contenedor4 = new Contenedor();

$contenedor4->agregarComponente(
    new GraficaBarras(
        "Ventas trimestrales",
        ["T1", "T2", "T3", "T4"],
        [25, 35, 40, 50]
    )
);

$contenedor4->agregarComponente(
    new GraficaLineas(
        "Evolución de clientes",
        ["T1", "T2", "T3", "T4"],
        [20, 28, 35, 42]
    )
);

$seccion4->agregarContenedor($contenedor4);

$pagina3->agregarSeccion($seccion4);

$documento->agregarPagina($pagina3);


// ============================================================
// PAGINA 4: GRAFICA CIRCULAR Y CONCLUSIONES
// ============================================================

$pagina4 = new Pagina(4);

$seccion5 = new Seccion("Distribución de ingresos");

$contenedor5 = new Contenedor();

$contenedor5->agregarComponente(
    new GraficaCircular(
        "Ingresos por línea de negocio",
        [
            "Desarrollo",
            "Consultoría",
            "Formación",
            "Soporte"
        ],
        [
            45,
            30,
            15,
            10
        ]
    )
);

$seccion5->agregarContenedor($contenedor5);

$pagina4->agregarSeccion($seccion5);


$seccion6 = new Seccion("Conclusiones");

$contenedor6 = new Contenedor();

$contenedor6->agregarComponente(
    new Parrafo(
        "Los resultados muestran una evolución "
        . "positiva durante el ejercicio."
    )
);

$contenedor6->agregarComponente(
    new Parrafo(
        "La facturación ha aumentado progresivamente "
        . "y el número de clientes mantiene una "
        . "tendencia creciente."
    )
);

$seccion6->agregarContenedor($contenedor6);

$pagina4->agregarSeccion($seccion6);

$documento->agregarPagina($pagina4);

?>

<!DOCTYPE html>
<html lang="es">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>
    <?php echo escapar($documento->titulo); ?>
</title>

<style>

/* ============================================================
   ESTILOS GENERALES
   ============================================================ */

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    padding: 0;
    font-family: Arial, Helvetica, sans-serif;
    background: #e5e7eb;
    color: #1f2937;
    line-height: 1.6;
}

.documento-header {
    background: #111827;
    color: white;
    padding: 25px;
    text-align: center;
    margin-bottom: 30px;
}

.documento-header h1 {
    margin: 0;
    font-size: 24px;
}

.documento {
    padding-bottom: 30px;
}


/* ============================================================
   PAGINAS A4
   ============================================================ */

.pagina {
    width: 210mm;
    min-height: 297mm;
    margin: 0 auto 30px auto;
    padding: 20mm;
    background: white;
    box-shadow: 0 5px 25px rgba(0,0,0,0.12);
    position: relative;
}

.numero-pagina {
    position: absolute;
    bottom: 12mm;
    left: 0;
    right: 0;
    text-align: center;
    color: #94a3b8;
    font-size: 11px;
}


/* ============================================================
   SECCIONES
   ============================================================ */

.seccion {
    margin-bottom: 30px;
}

.seccion-titulo {
    font-size: 20px;
    font-weight: 700;
    color: #1d4ed8;
    padding-bottom: 8px;
    margin: 0 0 20px 0;
    border-bottom: 2px solid #2563eb;
}


/* ============================================================
   CONTENEDORES
   ============================================================ */

.contenedor {
    display: flex;
    flex-direction: column;
    gap: 16px;
    margin-bottom: 20px;
}


/* ============================================================
   COMPONENTES DE TEXTO
   ============================================================ */

.componente-titulo {
    font-size: 30px;
    line-height: 1.2;
    font-weight: 800;
    color: #0f172a;
    margin: 0;
}

.componente-subtitulo {
    font-size: 20px;
    font-weight: 400;
    color: #64748b;
    margin: 0;
}

.componente-encabezado {
    font-size: 17px;
    font-weight: 700;
    color: #334155;
    margin: 10px 0 0 0;
}

.componente-parrafo {
    font-size: 14px;
    line-height: 1.8;
    color: #475569;
    margin: 0;
    text-align: justify;
}


/* ============================================================
   TABLAS
   ============================================================ */

.tabla-contenedor {
    width: 100%;
    overflow-x: auto;
}

.tabla-simple {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
}

.tabla-simple thead {
    background: #1e40af;
    color: white;
}

.tabla-simple th {
    text-align: left;
    padding: 12px;
    font-weight: 600;
}

.tabla-simple td {
    padding: 12px;
    border-bottom: 1px solid #e2e8f0;
}

.tabla-simple tbody tr:nth-child(even) {
    background: #f8fafc;
}

.tabla-simple tbody tr:hover {
    background: #eff6ff;
}


/* ============================================================
   GRAFICAS
   ============================================================ */

.grafica {
    width: 100%;
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 15px;
    margin-bottom: 15px;
}

.grafica-titulo {
    font-size: 15px;
    font-weight: 700;
    color: #334155;
    margin: 0 0 15px 0;
}

.grafica-svg {
    width: 100%;
    height: auto;
    display: block;
}


/* ============================================================
   INDICADORES
   ============================================================ */

.indicador {
    width: 100%;
    padding: 20px;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    background: #f8fafc;
}

.indicador-etiqueta {
    font-size: 13px;
    font-weight: 600;
    color: #64748b;
    margin-bottom: 8px;
}

.indicador-valor {
    font-size: 32px;
    font-weight: 800;
    color: #0f172a;
    line-height: 1.2;
    margin-bottom: 8px;
}

.indicador-descripcion {
    font-size: 12px;
    color: #94a3b8;
}


/* ============================================================
   KPI
   ============================================================ */

.kpi {
    border-left: 5px solid #2563eb;
}

.kpi .indicador-valor {
    color: #2563eb;
}


/* ============================================================
   CONTADOR
   ============================================================ */

.contador {
    border-left: 5px solid #16a34a;
}

.contador .indicador-valor {
    color: #16a34a;
}


/* ============================================================
   PORCENTAJE
   ============================================================ */

.porcentaje {
    border-left: 5px solid #f59e0b;
}

.porcentaje .indicador-valor {
    color: #d97706;
}

.porcentaje-barra {
    width: 100%;
    height: 12px;
    background: #e2e8f0;
    border-radius: 20px;
    overflow: hidden;
    margin-bottom: 12px;
}

.porcentaje-progreso {
    height: 100%;
    background: linear-gradient(
        90deg,
        #f59e0b,
        #fbbf24
    );
    border-radius: 20px;
}


/* ============================================================
   RESPONSIVE
   ============================================================ */

@media screen and (max-width: 850px) {

    .pagina {
        width: 100%;
        min-height: auto;
        padding: 25px;
    }

    .componente-titulo {
        font-size: 24px;
    }

    .indicador-valor {
        font-size: 26px;
    }

}


/* ============================================================
   IMPRESION
   ============================================================ */

@media print {

    @page {
        size: A4 portrait;
        margin: 0;
    }

    body {
        background: white;
        print-color-adjust: exact;
        -webkit-print-color-adjust: exact;
    }

    .documento-header {
        display: none;
    }

    .documento {
        padding: 0;
    }

    .pagina {
        width: 210mm;
        height: 297mm;
        min-height: 0;
        margin: 0;
        padding: 20mm;
        box-shadow: none;
        break-after: page;
        overflow: hidden;
    }

    .pagina:last-child {
        break-after: auto;
    }

    .seccion,
    .contenedor,
    .grafica,
    .indicador {
        break-inside: avoid;
    }

    .tabla-simple tbody tr {
        break-inside: avoid;
    }

}

</style>

</head>

<body>

<?php

echo $documento->render();

?>

</body>

</html>
