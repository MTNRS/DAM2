<?php
class TablaComparativa extends TablaAvanzada {
    public function render(): string {return $this->tabla($this->columnas(),$this->registros());}
}
