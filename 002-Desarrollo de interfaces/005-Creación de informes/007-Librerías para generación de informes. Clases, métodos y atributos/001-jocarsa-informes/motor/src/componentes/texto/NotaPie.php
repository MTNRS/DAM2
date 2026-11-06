<?php
class NotaPie extends TextoAvanzado { public function render(): string { return '<div class="texto-nota-pie"><sup>*</sup> '.$this->contenido().'</div>'; } }
