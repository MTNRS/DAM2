<?php
class TablaTotales extends TablaAvanzada {
    public function render(): string {
        $c=$this->columnas();$r=$this->registros();$campo=$this->config['columna_total']??(count($c)-1);$idx=is_int($campo)?$campo:array_search($campo,$c,true);if($idx===false)$idx=count($c)-1;
        $total=0;foreach($r as $f)$total+=(float)($f[$idx]??0);
        $impuesto=(float)($this->config['impuesto_porcentaje']??0);$r[]=array_replace(array_fill(0,count($c),''),[0=>'Subtotal',$idx=>round($total,2)]);
        if($impuesto)$r[]=array_replace(array_fill(0,count($c),''),[0=>'Impuesto ('.$impuesto.'%)',$idx=>round($total*$impuesto/100,2)]);
        $r[]=array_replace(array_fill(0,count($c),''),[0=>'TOTAL',$idx=>round($total*(1+$impuesto/100),2)]);
        return $this->tabla($c,$r);
    }
}
