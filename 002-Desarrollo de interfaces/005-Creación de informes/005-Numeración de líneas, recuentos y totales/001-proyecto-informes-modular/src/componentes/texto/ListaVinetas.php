<?php
class ListaVinetas extends TextoAvanzado { public function render(): string { return '<ul class="lista-texto">'.implode('',array_map(fn($v)=>'<li>'.$this->interpolar((string)$v).'</li>',$this->config['elementos'] ?? [])).'</ul>'; } }
