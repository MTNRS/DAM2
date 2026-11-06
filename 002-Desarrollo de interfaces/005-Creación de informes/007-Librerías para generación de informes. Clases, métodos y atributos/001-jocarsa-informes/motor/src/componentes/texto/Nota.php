<?php
class Nota extends TextoAvanzado { public function render(): string { return '<aside class="texto-nota">'.$this->contenido().'</aside>'; } }
