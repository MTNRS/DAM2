<?php
class TablaFormulas extends TablaAvanzada {
    public function render(): string {
        $c=$this->columnas();$r=$this->registros();
        foreach($r as &$fila)foreach($this->config['formulas']??[] as $f){
            $a=(float)($fila[$f['a']]??0);$b=isset($f['b'])?(float)($fila[$f['b']]??0):(float)($f['constante']??0);
            $resultado=match($f['operador']){'sumar'=>$a+$b,'restar'=>$a-$b,'multiplicar'=>$a*$b,'dividir'=>$b!=0?$a/$b:null,default=>null};
            $fila[$f['destino']]=$resultado===null?'N/D':round($resultado,2);
        }
        unset($fila);return $this->tabla($c,$r);
    }
}
