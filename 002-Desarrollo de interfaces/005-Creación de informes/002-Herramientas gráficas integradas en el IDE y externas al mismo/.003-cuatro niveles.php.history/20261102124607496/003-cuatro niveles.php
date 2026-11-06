
<?php

class Contenedor {
    public $contenido;

    public function __construct($contenido) {
        $this->contenido = $contenido;
    }
}

class Seccion {
    public $titulo;
    public $contenedores = [];

    public function __construct($titulo) {
        $this->titulo = $titulo;
    }

    public function agregarContenedor($contenedor) {
        $this->contenedores[] = $contenedor;
    }
}

class Pagina {
    public $numero;
    public $secciones = [];

    public function __construct($numero) {
        $this->numero = $numero;
    }

    public function agregarSeccion($seccion) {
        $this->secciones[] = $seccion;
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
$documento = new Documento("Mi documento");

// Crear primera página
$pagina1 = new Pagina(1);

// Crear primera sección
$seccion1 = new Seccion("Introducción");
$seccion1->agregarContenedor(
    new Contenedor("Primer párrafo")
);
$seccion1->agregarContenedor(
    new Contenedor("Segundo párrafo")
);

// Crear segunda sección
$seccion2 = new Seccion("Desarrollo");
$seccion2->agregarContenedor(
    new Contenedor("Contenido del desarrollo")
);

// Añadir secciones a la página
$pagina1->agregarSeccion($seccion1);
$pagina1->agregarSeccion($seccion2);

// Añadir página al documento
$documento->agregarPagina($pagina1);

// Mostrar toda la estructura
echo "<pre>";
print_r($documento);
echo "</pre>";

?>
