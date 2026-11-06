<?php
class TablaAgrupada extends TablaAvanzada {
    public function render(): string {
        $grupo=$this->config['agrupar_por']; $c=$this->columnas(); $r=$this->registros(); $grupos=[];
        foreach($r as $fila) $grupos[(string)($fila[$grupo]??'Sin grupo')][]=$fila;
        $html='';
        foreach($grupos as $nombre=>$filas) $html.='<h4 class="tabla-grupo">'.$this->e($nombre).' ('.count($filas).')</h4>'.$this->tabla($c,array_map(fn($f)=>array_map(fn($k)=>$f[$k]??'',$c),$filas));
        return $html;
    }
}
