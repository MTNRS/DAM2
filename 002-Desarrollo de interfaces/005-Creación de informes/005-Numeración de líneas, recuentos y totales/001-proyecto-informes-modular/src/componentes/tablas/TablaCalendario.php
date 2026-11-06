<?php
class TablaCalendario extends TablaAvanzada {
    public function render(): string {
        $mes=$this->config['mes'];if(!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/D',$mes))throw new InvalidArgumentException('mes inválido');
        $inicio=new DateTimeImmutable($mes.'-01');$offset=(int)$inicio->format('N')-1;$dias=(int)$inicio->format('t');$map=[];
        foreach($this->registros() as $f)$map[$f['fecha']??''][]=(string)($f['texto']??'');
        $r=[];$fila=[];for($i=0;$i<$offset;$i++)$fila[]='';
        for($d=1;$d<=$dias;$d++){$fecha=$mes.'-'.str_pad((string)$d,2,'0',STR_PAD_LEFT);$fila[]=$d.(isset($map[$fecha])?' · '.implode(' / ',$map[$fecha]):'');if(count($fila)===7){$r[]=$fila;$fila=[];}}
        if($fila)$r[]=array_pad($fila,7,'');return $this->tabla(['Lun','Mar','Mié','Jue','Vie','Sáb','Dom'],$r);
    }
}
