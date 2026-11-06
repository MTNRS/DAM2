<?php
class Matriz extends TablaAvanzada {
    public function render(): string {
        $filas=$this->config['filas']??[];$cols=$this->columnas();$val=$this->config['valores']??[];$out=[];
        foreach($filas as $i=>$nombre)$out[]=array_merge([$nombre],array_slice(array_pad($val[$i]??[],count($cols),''),0,count($cols)));
        return $this->tabla(array_merge([$this->config['cabecera_filas']??''],$cols),$out);
    }
}
