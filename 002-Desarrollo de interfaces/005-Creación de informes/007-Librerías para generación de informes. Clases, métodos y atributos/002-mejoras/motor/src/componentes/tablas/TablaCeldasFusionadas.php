<?php
class TablaCeldasFusionadas extends TablaAvanzada {
    public function render(): string {
        $c=$this->columnas();$html='<div class="tabla-contenedor"><table class="tabla-simple tabla-avanzada"><thead><tr>';
        foreach($c as $x)$html.='<th>'.$this->e($x).'</th>';$html.='</tr></thead><tbody>';
        foreach($this->registros() as $fila){$html.='<tr>';foreach($fila as $celda){if(!is_array($celda))$celda=['valor'=>$celda];$cs=max(1,min(20,(int)($celda['colspan']??1)));$rs=max(1,min(100,(int)($celda['rowspan']??1)));$html.='<td colspan="'.$cs.'" rowspan="'.$rs.'">'.$this->e($celda['valor']??'').'</td>';}$html.='</tr>';}
        return PresentacionTabla::aplicar($html.'</tbody></table></div>', $this->config);
    }
}
