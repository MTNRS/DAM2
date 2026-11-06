<?php
class TextoDinamico extends TextoAvanzado { public function render(): string { return '<p class="texto-dinamico">'.$this->contenido().'</p>'; } }
