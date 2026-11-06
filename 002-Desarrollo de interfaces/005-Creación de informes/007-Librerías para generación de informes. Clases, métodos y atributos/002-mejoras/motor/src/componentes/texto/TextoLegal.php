<?php
class TextoLegal extends TextoAvanzado { public function render(): string { return '<div class="texto-legal">'.$this->contenido().'</div>'; } }
