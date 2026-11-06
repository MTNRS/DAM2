<?php
class Etiqueta extends TextoAvanzado { public function render(): string { return '<span class="texto-etiqueta">'.$this->contenido().'</span>'; } }
