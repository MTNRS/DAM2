<?php
/** Fachada para construir informes sobre el motor existente. */
class Informe {
    private Documento $documento;
    private ?Pagina $paginaActual = null;
    private ?Seccion $seccionActual = null;
    private ?Contenedor $contenedorActual = null;
    private int $numeroPagina = 0;

    public function __construct(string $titulo) {
        $this->documento = new Documento($titulo);
    }

    public function nuevaPagina(): self {
        $this->numeroPagina++;
        $this->paginaActual = new Pagina($this->numeroPagina);
        $this->documento->agregarPagina($this->paginaActual);
        $this->seccionActual = null;
        $this->contenedorActual = null;
        return $this;
    }

    public function nuevaSeccion(string $titulo = ""): self {
        if ($this->paginaActual === null) {
            $this->nuevaPagina();
        }
        $this->seccionActual = new Seccion($titulo);
        $this->paginaActual->agregarSeccion($this->seccionActual);
        $this->contenedorActual = null;
        return $this;
    }

    public function nuevoContenedor(): self {
        if ($this->seccionActual === null) {
            $this->nuevaSeccion();
        }
        $this->contenedorActual = new Contenedor();
        $this->seccionActual->agregarContenedor($this->contenedorActual);
        return $this;
    }

    public function agregar(Componente $componente): self {
        if ($this->contenedorActual === null) {
            $this->nuevoContenedor();
        }
        $this->contenedorActual->agregarComponente($componente);
        return $this;
    }

    public function titulo(string $texto): self {
        return $this->agregar(new Titulo($texto));
    }

    public function subtitulo(string $texto): self {
        return $this->agregar(new Subtitulo($texto));
    }

    public function encabezado(string $texto, int $nivel = 3): self {
        return $this->agregar(new Encabezado($texto, $nivel));
    }

    public function parrafo(string $texto): self {
        return $this->agregar(new Parrafo($texto));
    }

    public function tabla(array $columnas, array $filas): self {
        return $this->agregar(new TablaSimple($columnas, $filas));
    }

    public function barras(string $titulo, array $etiquetas, array $valores, int $ancho = 600, int $alto = 300): self {
        return $this->agregar(new GraficaBarras($titulo, $etiquetas, $valores, $ancho, $alto));
    }

    public function lineas(string $titulo, array $etiquetas, array $valores, int $ancho = 600, int $alto = 300): self {
        return $this->agregar(new GraficaLineas($titulo, $etiquetas, $valores, $ancho, $alto));
    }

    public function circular(string $titulo, array $etiquetas, array $valores, int $ancho = 600, int $alto = 300): self {
        return $this->agregar(new GraficaCircular($titulo, $etiquetas, $valores, $ancho, $alto));
    }

    public function kpi(string $etiqueta, string $valor, string $descripcion = ""): self {
        return $this->agregar(new KPI($etiqueta, $valor, $descripcion));
    }

    public function contador(string $etiqueta, int $valor, string $descripcion = ""): self {
        return $this->agregar(new Contador($etiqueta, $valor, $descripcion));
    }

    public function porcentaje(string $etiqueta, float $valor, string $descripcion = ""): self {
        return $this->agregar(new Porcentaje($etiqueta, $valor, $descripcion));
    }

    public function obtenerDocumento(): Documento {
        return $this->documento;
    }

    public function render(): string {
        return $this->documento->render();
    }
}
