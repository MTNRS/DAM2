<?php
class TablaJerarquica extends TablaAvanzada {
    public function render(): string {
        $c=$this->columnas(); $r=[];
        $walk=function(array $items,int $nivel)use(&$walk,&$r,$c){
            if($nivel>12)return;
            foreach($items as $f){$row=[];foreach($c as $i=>$k)$row[]=($i===0?str_repeat('— ',$nivel):'').($f[$k]??'');$r[]=$row;if(isset($f['hijos'])&&is_array($f['hijos']))$walk($f['hijos'],$nivel+1);}
        };
        $walk($this->registros(),0);return $this->tabla($c,$r);
    }
}
