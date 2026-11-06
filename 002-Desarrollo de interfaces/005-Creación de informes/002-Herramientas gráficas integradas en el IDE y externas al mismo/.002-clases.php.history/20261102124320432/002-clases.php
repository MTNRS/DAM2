
<?php

class Pagina {
    public $numero;
    public $contenido;

    public function __construct($numero, $contenido) {
        $this->numero = $numero;
        $this->contenido = $contenido;
    }
}

class Documento {
    public $titulo;
    public $paginas = [];

    public function __construct($titulo) {
        $this->titulo = $titulo;
    }

    public function agregarPagina($pagina) {
        $this->paginas[] = $pagina;
    }
}

// Crear documento
$documento = new Documento("Mi primer documento");

// Agregar páginas
$documento->agregarPagina(new Pagina(1, "Contenido de la primera página"));
$documento->agregarPagina(new Pagina(2, "Contenido de la segunda página"));
$documento->agregarPagina(new Pagina(3, "Contenido de la tercera página"));

// Mostrar documento
echo "Documento: " . $documento->titulo . "\n";

foreach ($documento->paginas as $pagina) {
    echo "Página " . $pagina->numero . ": ";
    echo $pagina->contenido . "\n";
}

?>
