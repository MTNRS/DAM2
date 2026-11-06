<?php
class TablaPivote extends TablaAvanzada {
    public function render(): string {
        $r=$this->registros();$fil=$this->config['campo_fila'];$col=$this->config['campo_columna'];$val=$this->config['campo_valor'];$op=$this->config['operacion']??'suma';$mat=[];$cols=[];
        foreach($r as $f){$a=(string)($f[$fil]??'');$b=(string)($f[$col]??'');$mat[$a][$b][]=$f[$val]??0;$cols[$b]=true;}
        $cab=array_keys($cols);sort($cab);$out=[];
        foreach($mat as $a=>$m){$row=[$a];foreach($cab as $b)$row[]=round($this->agregado($m[$b]??[],$op),2);$out[]=$row;}
        return $this->tabla(array_merge([$fil],$cab),$out);
    }
}
