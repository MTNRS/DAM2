<?php
/** Componentes adicionales. Todo contenido externo se escapa antes de renderizar. */
abstract class TextoAvanzado extends Componente {
    public function __construct(protected array $config, protected array $datos = []) { parent::__construct(); }
    protected function e(mixed $v): string { return escapar(is_scalar($v) || $v === null ? (string)$v : json_encode($v, JSON_UNESCAPED_UNICODE)); }
    protected function resolver(string $ruta): mixed {
        $v = $this->datos;
        foreach (explode('.', $ruta) as $parte) { if (!is_array($v) || !array_key_exists($parte,$v)) return null; $v=$v[$parte]; }
        return $v;
    }
    protected function interpolar(string $s): string {
        return preg_replace_callback('/\{\{\s*([a-zA-Z0-9_.]+)\s*\}\}/', fn($m) => $this->e($this->resolver($m[1]) ?? ''), $s);
    }
    protected function contenido(): string { return $this->interpolar((string)($this->config['texto'] ?? '')); }
}
class TextoEnriquecido extends TextoAvanzado {
    public function render(): string {
        $html='<div class="texto-enriquecido">';
        foreach ($this->config['fragmentos'] ?? [] as $f) {
            $txt=$this->interpolar((string)($f['texto'] ?? ''));
            $estilos=[];
            if (!empty($f['negrita'])) $estilos[]='font-weight:bold';
            if (!empty($f['cursiva'])) $estilos[]='font-style:italic';
            if (!empty($f['subrayado'])) $estilos[]='text-decoration:underline';
            if (isset($f['color']) && preg_match('/^#[0-9a-fA-F]{6}$/D',$f['color'])) $estilos[]='color:'.$f['color'];
            $txt='<span style="'.implode(';',$estilos).'">'.$txt.'</span>';
            if (isset($f['enlace']) && preg_match('~^https?://[^\s"<>]+$~i',$f['enlace'])) $txt='<a rel="noopener noreferrer" href="'.$this->e($f['enlace']).'">'.$txt.'</a>';
            $html.=$txt;
        }
        return $html.'</div>';
    }
}
class Etiqueta extends TextoAvanzado { public function render(): string { return '<span class="texto-etiqueta">'.$this->contenido().'</span>'; } }
class Valor extends TextoAvanzado { public function render(): string { return '<span class="texto-valor">'.$this->contenido().'</span>'; } }
class EtiquetaValor extends TextoAvanzado { public function render(): string { return '<div class="etiqueta-valor"><strong>'.$this->interpolar((string)($this->config['etiqueta'] ?? '')).':</strong> '.$this->interpolar((string)($this->config['valor'] ?? '')).'</div>'; } }
class ListaVinetas extends TextoAvanzado { public function render(): string { return '<ul class="lista-texto">'.implode('',array_map(fn($v)=>'<li>'.$this->interpolar((string)$v).'</li>',$this->config['elementos'] ?? [])).'</ul>'; } }
class ListaNumerada extends TextoAvanzado { public function render(): string { return '<ol class="lista-texto">'.implode('',array_map(fn($v)=>'<li>'.$this->interpolar((string)$v).'</li>',$this->config['elementos'] ?? [])).'</ol>'; } }
class Cita extends TextoAvanzado { public function render(): string { return '<blockquote class="texto-cita">'.$this->contenido().(isset($this->config['autor'])?'<footer>— '.$this->interpolar($this->config['autor']).'</footer>':'').'</blockquote>'; } }
class Nota extends TextoAvanzado { public function render(): string { return '<aside class="texto-nota">'.$this->contenido().'</aside>'; } }
class NotaPie extends TextoAvanzado { public function render(): string { return '<div class="texto-nota-pie"><sup>*</sup> '.$this->contenido().'</div>'; } }
class TextoLegal extends TextoAvanzado { public function render(): string { return '<div class="texto-legal">'.$this->contenido().'</div>'; } }
class TextoDinamico extends TextoAvanzado { public function render(): string { return '<p class="texto-dinamico">'.$this->contenido().'</p>'; } }
class TextoCondicional extends TextoAvanzado {
    public function render(): string {
        $r=$this->config['regla'] ?? [];
        $actual=$this->resolver((string)($r['campo'] ?? ''));
        $esperado=$r['valor'] ?? null;
        $op=$r['operador'] ?? 'igual';
        $ok=match($op) {
            'igual'=>$actual === $esperado, 'distinto'=>$actual !== $esperado,
            'mayor'=>is_numeric($actual)&&is_numeric($esperado)&&(float)$actual>(float)$esperado,
            'menor'=>is_numeric($actual)&&is_numeric($esperado)&&(float)$actual<(float)$esperado,
            'mayor_igual'=>is_numeric($actual)&&is_numeric($esperado)&&(float)$actual>=(float)$esperado,
            'menor_igual'=>is_numeric($actual)&&is_numeric($esperado)&&(float)$actual<=(float)$esperado,
            'existe'=>$actual !== null,
            default=>false
        };
        return $ok?'<p class="texto-condicional">'.$this->contenido().'</p>':'';
    }
}

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
        return $html.'</tbody></table></div>';
    }
    protected function columnas(): array { return $this->config['columnas'] ?? []; }
    protected function campo(array $fila,string $nombre): mixed { return $fila[$nombre]??null; }
    protected function agregado(array $valores,string $op): float {
        $v=array_values(array_filter($valores,'is_numeric'));
        return match($op) { 'conteo'=>(float)count($valores),'promedio'=>$v?array_sum($v)/count($v):0.0,'minimo'=>$v?min($v):0.0,'maximo'=>$v?max($v):0.0,default=>array_sum($v) };
    }
}
class TablaDinamica extends TablaAvanzada {
    public function render(): string {
        $r=$this->registros(); $c=$this->columnas() ?: (isset($r[0])&&is_array($r[0])?array_keys($r[0]):[]);
        return $this->tabla($c,array_map(fn($fila)=>array_map(fn($k)=>$fila[$k]??'', $c),$r));
    }
}
class TablaAgrupada extends TablaAvanzada {
    public function render(): string {
        $grupo=$this->config['agrupar_por']; $c=$this->columnas(); $r=$this->registros(); $grupos=[];
        foreach($r as $fila) $grupos[(string)($fila[$grupo]??'Sin grupo')][]=$fila;
        $html='';
        foreach($grupos as $nombre=>$filas) $html.='<h4 class="tabla-grupo">'.$this->e($nombre).' ('.count($filas).')</h4>'.$this->tabla($c,array_map(fn($f)=>array_map(fn($k)=>$f[$k]??'',$c),$filas));
        return $html;
    }
}
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
class TablaPivote extends TablaAvanzada {
    public function render(): string {
        $r=$this->registros();$fil=$this->config['campo_fila'];$col=$this->config['campo_columna'];$val=$this->config['campo_valor'];$op=$this->config['operacion']??'suma';$mat=[];$cols=[];
        foreach($r as $f){$a=(string)($f[$fil]??'');$b=(string)($f[$col]??'');$mat[$a][$b][]=$f[$val]??0;$cols[$b]=true;}
        $cab=array_keys($cols);sort($cab);$out=[];
        foreach($mat as $a=>$m){$row=[$a];foreach($cab as $b)$row[]=round($this->agregado($m[$b]??[],$op),2);$out[]=$row;}
        return $this->tabla(array_merge([$fil],$cab),$out);
    }
}
class Matriz extends TablaAvanzada {
    public function render(): string {
        $filas=$this->config['filas']??[];$cols=$this->columnas();$val=$this->config['valores']??[];$out=[];
        foreach($filas as $i=>$nombre)$out[]=array_merge([$nombre],array_slice(array_pad($val[$i]??[],count($cols),''),0,count($cols)));
        return $this->tabla(array_merge([$this->config['cabecera_filas']??''],$cols),$out);
    }
}
class TablaComparativa extends TablaAvanzada {
    public function render(): string {return $this->tabla($this->columnas(),$this->registros());}
}
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
class TablaFrecuencias extends TablaAvanzada {
    public function render(): string {
        $campo=$this->config['campo'];$cuentas=[];foreach($this->registros() as $f){$k=(string)($f[$campo]??'Sin dato');$cuentas[$k]=($cuentas[$k]??0)+1;}
        $total=array_sum($cuentas);$r=[];foreach($cuentas as $k=>$v)$r[]=[$k,$v,$total?round(100*$v/$total,2).'%':'0%'];
        return $this->tabla([$campo,'Frecuencia','Porcentaje'],$r);
    }
}
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
class TablaDobleEntrada extends TablaPivote {}
class TablaCeldasFusionadas extends TablaAvanzada {
    public function render(): string {
        $c=$this->columnas();$html='<div class="tabla-contenedor"><table class="tabla-simple tabla-avanzada"><thead><tr>';
        foreach($c as $x)$html.='<th>'.$this->e($x).'</th>';$html.='</tr></thead><tbody>';
        foreach($this->registros() as $fila){$html.='<tr>';foreach($fila as $celda){if(!is_array($celda))$celda=['valor'=>$celda];$cs=max(1,min(20,(int)($celda['colspan']??1)));$rs=max(1,min(100,(int)($celda['rowspan']??1)));$html.='<td colspan="'.$cs.'" rowspan="'.$rs.'">'.$this->e($celda['valor']??'').'</td>';}$html.='</tr>';}
        return $html.'</tbody></table></div>';
    }
}
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
