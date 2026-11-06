<?php
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
