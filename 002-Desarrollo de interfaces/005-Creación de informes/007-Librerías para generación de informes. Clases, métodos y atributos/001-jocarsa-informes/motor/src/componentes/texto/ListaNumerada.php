<?php
class ListaNumerada extends TextoAvanzado { public function render(): string { return '<ol class="lista-texto">'.implode('',array_map(fn($v)=>'<li>'.$this->interpolar((string)$v).'</li>',$this->config['elementos'] ?? [])).'</ol>'; } }
