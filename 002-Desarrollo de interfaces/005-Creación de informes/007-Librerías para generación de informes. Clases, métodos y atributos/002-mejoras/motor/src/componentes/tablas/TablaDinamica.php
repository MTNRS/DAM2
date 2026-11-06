<?php
class TablaDinamica extends TablaAvanzada {
    public function render(): string {
        $r=$this->registros(); $c=$this->columnas() ?: (isset($r[0])&&is_array($r[0])?array_keys($r[0]):[]);
        return $this->tabla($c,array_map(fn($fila)=>array_map(fn($k)=>$fila[$k]??'', $c),$r));
    }
}
