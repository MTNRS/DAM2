<?php
class TablaFormatoCondicional extends TablaAvanzada {
    public function render(): string {
        $r=$this->registros();$cl=[];
        foreach($r as $i=>$fila)foreach($fila as $j=>$v)foreach($this->config['reglas']??[] as $reg){
            if($j!==$reg['columna'])continue;$n=$reg['valor'];$ok=match($reg['operador']){'mayor'=>is_numeric($v)&&$v>$n,'menor'=>is_numeric($v)&&$v<$n,'igual'=>$v==$n,default=>false};
            if($ok)$cl[$i][$j]=$reg['clase'];
        }
        return $this->tabla($this->columnas(),$r,$cl);
    }
}
