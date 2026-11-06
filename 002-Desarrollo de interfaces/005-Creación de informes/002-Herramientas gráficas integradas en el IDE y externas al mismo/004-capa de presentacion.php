
<?php

class Contenedor {
    public $contenido;

    public function __construct($contenido) {
        $this->contenido = $contenido;
    }

    public function render() {
        return '<div class="contenedor">'
            . htmlspecialchars($this->contenido)
            . '</div>';
    }
}

class Seccion {
    public $titulo;
    public $contenedores = [];

    public function __construct($titulo) {
        $this->titulo = $titulo;
    }

    public function agregarContenedor(Contenedor $contenedor) {
        $this->contenedores[] = $contenedor;
    }

    public function render() {
        $html = '<section class="seccion">';
        $html .= '<h2>' . htmlspecialchars($this->titulo) . '</h2>';

        foreach ($this->contenedores as $contenedor) {
            $html .= $contenedor->render();
        }

        $html .= '</section>';

        return $html;
    }
}

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
        $html .= 'Página ' . htmlspecialchars((string)$this->numero);
        $html .= '</footer>';

        $html .= '</article>';

        return $html;
    }
}

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
        $html .= '<h1>' . htmlspecialchars($this->titulo) . '</h1>';
        $html .= '</header>';

        foreach ($this->paginas as $pagina) {
            $html .= $pagina->render();
        }

        return $html;
    }
}

// =====================================
// CREAR DOCUMENTO
// =====================================

$documento = new Documento("Informe de empresa");

// Página 1
$pagina1 = new Pagina(1);

$seccion1 = new Seccion("Introducción");
$seccion1->agregarContenedor(
    new Contenedor("Este informe presenta los resultados de la empresa.")
);
$seccion1->agregarContenedor(
    new Contenedor("El objetivo es analizar su evolución.")
);

$seccion2 = new Seccion("Resultados");
$seccion2->agregarContenedor(
    new Contenedor("Facturación anual: 150.000 €")
);
$seccion2->agregarContenedor(
    new Contenedor("Clientes activos: 125")
);

$pagina1->agregarSeccion($seccion1);
$pagina1->agregarSeccion($seccion2);

$documento->agregarPagina($pagina1);

// Página 2
$pagina2 = new Pagina(2);

$seccion3 = new Seccion("Conclusiones");
$seccion3->agregarContenedor(
    new Contenedor("La empresa presenta una evolución positiva.")
);

$pagina2->agregarSeccion($seccion3);
$documento->agregarPagina($pagina2);

?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Visor de documentos</title>

<style>
* {
    box-sizing: border-box;
}

body {
    margin: 0;
    background: #e5e7eb;
    font-family: Arial, sans-serif;
    color: #1f2937;
}

.documento-header {
    text-align: center;
    padding: 25px;
    background: #1f2937;
    color: white;
    margin-bottom: 30px;
}

.documento-header h1 {
    margin: 0;
}

.pagina {
    width: 210mm;
    min-height: 297mm;
    margin: 0 auto 30px;
    padding: 25mm 20mm;
    background: white;
    box-shadow: 0 5px 25px #0002;
    position: relative;
}

.seccion {
    margin-bottom: 30px;
}

.seccion h2 {
    font-size: 20px;
    color: #2563eb;
    border-bottom: 2px solid #2563eb;
    padding-bottom: 8px;
    margin-bottom: 15px;
}

.contenedor {
    padding: 15px;
    margin-bottom: 10px;
    background: #f3f4f6;
    border-left: 4px solid #60a5fa;
    border-radius: 4px;
    line-height: 1.6;
}

.numero-pagina {
    position: absolute;
    bottom: 15mm;
    left: 0;
    right: 0;
    text-align: center;
    font-size: 12px;
    color: #6b7280;
}

@media screen and (max-width: 850px) {
    .pagina {
        width: 100%;
        min-height: auto;
        padding: 25px;
    }
}

@media print {
    @page {
        size: A4 portrait;
        margin: 0;
    }

    body {
        background: white;
    }

    .documento-header {
        display: none;
    }

    .pagina {
        width: 210mm;
        height: 297mm;
        min-height: 0;
        margin: 0;
        box-shadow: none;
        break-after: page;
        overflow: hidden;
    }

    .pagina:last-child {
        break-after: auto;
    }

    .seccion, .contenedor {
        break-inside: avoid;
    }
}
</style>
</head>

<body>

<?php echo $documento->render(); ?>

</body>
</html>
