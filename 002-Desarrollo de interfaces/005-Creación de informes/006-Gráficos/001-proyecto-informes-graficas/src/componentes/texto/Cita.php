<?php
class Cita extends TextoAvanzado { public function render(): string { return '<blockquote class="texto-cita">'.$this->contenido().(isset($this->config['autor'])?'<footer>— '.$this->interpolar($this->config['autor']).'</footer>':'').'</blockquote>'; } }
