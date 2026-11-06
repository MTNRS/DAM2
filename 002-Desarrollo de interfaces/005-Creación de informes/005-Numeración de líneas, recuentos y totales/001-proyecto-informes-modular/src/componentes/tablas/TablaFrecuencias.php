<?php
class TablaFrecuencias extends TablaAvanzada {
    public function render(): string {
        $campo=$this->config['campo'];$cuentas=[];foreach($this->registros() as $f){$k=(string)($f[$campo]??'Sin dato');$cuentas[$k]=($cuentas[$k]??0)+1;}
        $total=array_sum($cuentas);$r=[];foreach($cuentas as $k=>$v)$r[]=[$k,$v,$total?round(100*$v/$total,2).'%':'0%'];
        return $this->tabla([$campo,'Frecuencia','Porcentaje'],$r);
    }
}
