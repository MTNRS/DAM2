<?php
class Valor extends TextoAvanzado { public function render(): string { return '<span class="texto-valor">'.$this->contenido().'</span>'; } }
