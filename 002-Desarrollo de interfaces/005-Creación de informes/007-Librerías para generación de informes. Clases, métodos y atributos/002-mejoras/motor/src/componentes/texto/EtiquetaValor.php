<?php
class EtiquetaValor extends TextoAvanzado { public function render(): string { return '<div class="etiqueta-valor"><strong>'.$this->interpolar((string)($this->config['etiqueta'] ?? '')).':</strong> '.$this->interpolar((string)($this->config['valor'] ?? '')).'</div>'; } }
