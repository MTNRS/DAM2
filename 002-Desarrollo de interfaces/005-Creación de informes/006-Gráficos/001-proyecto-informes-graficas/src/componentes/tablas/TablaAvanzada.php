<?php
abstract class TablaAvanzada extends Componente {
    public function __construct(protected array $config, protected array $datos = []) { parent::__construct(); }
    protected function e(mixed $v): string { return escapar(is_scalar($v)||$v===null?(string)$v:json_encode($v,JSON_UNESCAPED_UNICODE)); }
    protected function registros(): array {
        if (isset($this->config['origen'])) {
            $v=$this->datos;
            foreach(explode('.',$this->config['origen']) as $parte) $v=is_array($v)?($v[$parte]??[]):[];
            return is_array($v)&&array_is_list($v)?$v:[];
        }
        return $this->config['filas'] ?? [];
    }
    protected function tabla(array $columnas,array $filas,array $clases=[]): string {
        $html='<div class="tabla-contenedor"><table class="tabla-simple tabla-avanzada"><thead><tr>';
        foreach($columnas as $c) $html.='<th>'.$this->e($c).'</th>';
        $html.='</tr></thead><tbody>';
        foreach($filas as $i=>$fila) {
            $html.='<tr>';
            foreach(array_values($fila) as $j=>$celda) {
                $attrs=$clases[$i][$j]??'';
                $html.='<td'.($attrs?' class="'.$this->e($attrs).'"':'').'>'.$this->e($celda).'</td>';
            }
            $html.='</tr>';
        }
        return PresentacionTabla::aplicar($html.'</tbody></table></div>', $this->config);
    }
    protected function columnas(): array { return $this->config['columnas'] ?? []; }
    protected function campo(array $fila,string $nombre): mixed { return $fila[$nombre]??null; }
    protected function agregado(array $valores,string $op): float {
        $v=array_values(array_filter($valores,'is_numeric'));
        return match($op) { 'conteo'=>(float)count($valores),'promedio'=>$v?array_sum($v)/count($v):0.0,'minimo'=>$v?min($v):0.0,'maximo'=>$v?max($v):0.0,default=>array_sum($v) };
    }
}
