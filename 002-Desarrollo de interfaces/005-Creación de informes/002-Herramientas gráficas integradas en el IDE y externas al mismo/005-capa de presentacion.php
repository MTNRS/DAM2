<?php
function esc(mixed $value): string { return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
abstract class Componente { abstract public function render(): string; }
abstract class Texto extends Componente {
    public function __construct(public string $contenido) {}
    protected function etiqueta(string $tag, string $clase): string { return '<'.$tag.' class="'.esc($clase).'">'.esc($this->contenido).'</'.$tag.'>'; }
}
class Titulo extends Texto { public function render(): string { return $this->etiqueta('h1','titulo'); } }
class Subtitulo extends Texto { public function render(): string { return $this->etiqueta('h2','subtitulo'); } }
class Encabezado extends Texto { public function render(): string { return $this->etiqueta('h3','encabezado'); } }
class Parrafo extends Texto { public function render(): string { return $this->etiqueta('p','parrafo'); } }
class Tabla extends Componente {
    public function __construct(public array $columnas, public array $filas) {}
    public function render(): string {
        $html='<div class="tabla-scroll"><table><thead><tr>';
        foreach($this->columnas as $col) $html.='<th>'.esc($col).'</th>';
        $html.='</tr></thead><tbody>';
        foreach($this->filas as $fila) { $html.='<tr>'; foreach(array_values($fila) as $celda) $html.='<td>'.esc($celda).'</td>'; $html.='</tr>'; }
        return $html.'</tbody></table></div>';
    }
}
abstract class Grafica extends Componente {
    protected array $colores=['#2563eb','#14b8a6','#f59e0b','#a855f7','#ef4444','#0ea5e9'];
    public function __construct(public array $etiquetas, public array $valores) {
        if(count($etiquetas)!==count($valores)) throw new InvalidArgumentException('Las etiquetas y valores deben tener la misma longitud');
        foreach($valores as $valor) if(!is_numeric($valor)||!is_finite((float)$valor)||$valor<0) throw new InvalidArgumentException('Los valores deben ser números finitos no negativos');
    }
    protected function abrirSvg(string $titulo): string { return '<svg class="grafica" viewBox="0 0 640 300" role="img" aria-label="'.esc($titulo).'" xmlns="http://www.w3.org/2000/svg">'; }
    protected function cerrarSvg(): string { return '</svg>'; }
    protected function texto(float $x,float $y,string $valor,string $anchor='middle'): string { return '<text x="'.$x.'" y="'.$y.'" text-anchor="'.$anchor.'" font-size="12" fill="#475569">'.esc($valor).'</text>'; }
    protected function maximo(): float { return max(1, ...array_map('floatval',$this->valores)); }
}
class GraficaBarras extends Grafica {
    public function render(): string {
        $n=count($this->valores); $s=$this->abrirSvg('Gráfica de barras'); if(!$n) return $s.$this->texto(320,150,'Sin datos').$this->cerrarSvg();
        $max=$this->maximo(); $paso=540/$n; $ancho=min(65,$paso*.65);
        $s.='<line x1="60" y1="245" x2="610" y2="245" stroke="#94a3b8"/>';
        foreach($this->valores as $i=>$v) { $h=190*(float)$v/$max; $x=65+$i*$paso+($paso-$ancho)/2; $y=245-$h; $color=$this->colores[$i%count($this->colores)];
            $s.='<rect x="'.$x.'" y="'.$y.'" width="'.$ancho.'" height="'.$h.'" rx="4" fill="'.$color.'"/>';
            $s.=$this->texto($x+$ancho/2,max(16,$y-8),(string)$v).$this->texto($x+$ancho/2,267,(string)$this->etiquetas[$i]); }
        return $s.$this->cerrarSvg();
    }
}
class GraficaLineas extends Grafica {
    public function render(): string {
        $n=count($this->valores); $s=$this->abrirSvg('Gráfica de líneas'); if(!$n) return $s.$this->texto(320,150,'Sin datos').$this->cerrarSvg();
        $max=$this->maximo(); $pts=[]; $s.='<line x1="60" y1="245" x2="610" y2="245" stroke="#94a3b8"/>';
        foreach($this->valores as $i=>$v) { $x=($n===1?320:65+$i*535/($n-1)); $y=245-190*(float)$v/$max; $pts[]=[$x,$y]; $s.=$this->texto($x,270,(string)$this->etiquetas[$i]); }
        $coords=implode(' ',array_map(fn($p)=>implode(',',$p),$pts)); $s.='<polyline points="'.$coords.'" fill="none" stroke="#2563eb" stroke-width="4" stroke-linejoin="round"/>';
        foreach($pts as $i=>$p) { $s.='<circle cx="'.$p[0].'" cy="'.$p[1].'" r="5" fill="#2563eb"/>'.$this->texto($p[0],max(15,$p[1]-12),(string)$this->valores[$i]); }
        return $s.$this->cerrarSvg();
    }
}
class GraficaCircular extends Grafica {
    public function render(): string {
        $s=$this->abrirSvg('Gráfica circular'); $total=array_sum($this->valores); if($total<=0) return $s.$this->texto(320,150,'Sin datos').$this->cerrarSvg();
        $inicio=-M_PI/2; $cx=205; $cy=150; $r=105;
        foreach($this->valores as $i=>$valor) {
            if($valor<=0) continue;
            $porcion=(float)$valor/$total; $fin=$inicio+2*M_PI*$porcion; $color=$this->colores[$i%count($this->colores)];
            if($porcion>=.999999) $s.='<circle cx="'.$cx.'" cy="'.$cy.'" r="'.$r.'" fill="'.$color.'"/>';
            else { $x1=$cx+$r*cos($inicio); $y1=$cy+$r*sin($inicio); $x2=$cx+$r*cos($fin); $y2=$cy+$r*sin($fin); $large=$porcion>.5?1:0;
                $s.='<path d="M '.$cx.' '.$cy.' L '.$x1.' '.$y1.' A '.$r.' '.$r.' 0 '.$large.' 1 '.$x2.' '.$y2.' Z" fill="'.$color.'" stroke="white" stroke-width="2"/>'; }
            $s.='<rect x="365" y="'.(45+$i*26).'" width="12" height="12" rx="2" fill="'.$color.'"/>';
            $s.=$this->texto(385,56+$i*26, (string)$this->etiquetas[$i].' ('.round($porcion*100,1).'%)','start'); $inicio=$fin;
        }
        return $s.$this->cerrarSvg();
    }
}
abstract class Indicador extends Componente {
    public function __construct(public string $descripcion, public float|int $valor) {}
    protected function tarjeta(string $valor,string $clase=''): string { return '<div class="indicador '.esc($clase).'"><div class="indicador-valor">'.esc($valor).'</div><div class="indicador-descripcion">'.esc($this->descripcion).'</div></div>'; }
}
class KPI extends Indicador { public function render(): string { return $this->tarjeta(number_format($this->valor,2,',','.')); } }
class Contador extends Indicador { public function render(): string { return $this->tarjeta(number_format($this->valor,0,',','.')); } }
class Porcentaje extends Indicador {
    public function render(): string { $v=max(0,min(100,$this->valor)); return '<div class="indicador"><div class="indicador-valor">'.esc(number_format($v,1,',','.')).'%</div><div class="indicador-descripcion">'.esc($this->descripcion).'</div><div class="progreso"><div style="width:'.$v.'%"></div></div></div>'; }
}
class Contenedor {
    public array $componentes=[];
    public function agregarComponente(Componente $componente): self { $this->componentes[]=$componente; return $this; }
    public function render(): string { $s='<div class="contenedor">'; foreach($this->componentes as $c) $s.=$c->render(); return $s.'</div>'; }
}
class Seccion {
    public array $contenedores=[];
    public function __construct(public string $titulo) {}
    public function agregarContenedor(Contenedor $contenedor): self { $this->contenedores[]=$contenedor; return $this; }
    public function render(): string { $s='<section class="seccion"><h2>'.esc($this->titulo).'</h2>'; foreach($this->contenedores as $c) $s.=$c->render(); return $s.'</section>'; }
}
class Pagina {
    public array $secciones=[];
    public function __construct(public int $numero) {}
    public function agregarSeccion(Seccion $seccion): self { $this->secciones[]=$seccion; return $this; }
    public function render(): string { $s='<article class="pagina">'; foreach($this->secciones as $seccion) $s.=$seccion->render(); return $s.'<footer>Página '.esc($this->numero).'</footer></article>'; }
}
class Documento {
    public array $paginas=[];
    public function __construct(public string $titulo) {}
    public function agregarPagina(Pagina $pagina): self { $this->paginas[]=$pagina; return $this; }
    public function render(): string { $s='<header class="documento-header"><h1>'.esc($this->titulo).'</h1></header>'; foreach($this->paginas as $p) $s.=$p->render(); return $s; }
}

$documento=new Documento('Informe comercial 2026');
$pagina1=new Pagina(1);
$intro=new Seccion('Presentación');
$intro->agregarContenedor((new Contenedor())->agregarComponente(new Titulo('Resultados del ejercicio'))->agregarComponente(new Subtitulo('Resumen ejecutivo'))->agregarComponente(new Encabezado('Objetivos'))->agregarComponente(new Parrafo('Análisis de ventas, evolución trimestral e indicadores clave del negocio.')));
$indicadores=new Seccion('Indicadores principales');
$indicadores->agregarContenedor((new Contenedor())->agregarComponente(new KPI('Ingresos (EUR)',150000))->agregarComponente(new Contador('Clientes activos',125))->agregarComponente(new Porcentaje('Objetivo alcanzado',78.5)));
$datos=new Seccion('Datos de ventas');
$datos->agregarContenedor((new Contenedor())->agregarComponente(new Tabla(['Trimestre','Ventas (€)','Clientes'],[['T1','25.000','30'],['T2','35.000','45'],['T3','40.000','50'],['T4','50.000','65']])));
$pagina1->agregarSeccion($intro)->agregarSeccion($indicadores)->agregarSeccion($datos);
$documento->agregarPagina($pagina1);
$pagina2=new Pagina(2);
$graficas=new Seccion('Gráficas');
$graficas->agregarContenedor((new Contenedor())->agregarComponente(new Encabezado('Ventas trimestrales'))->agregarComponente(new GraficaBarras(['T1','T2','T3','T4'],[25,35,40,50])));
$graficas->agregarContenedor((new Contenedor())->agregarComponente(new Encabezado('Evolución de clientes'))->agregarComponente(new GraficaLineas(['T1','T2','T3','T4'],[30,45,50,65])));
$pagina2->agregarSeccion($graficas); $documento->agregarPagina($pagina2);
$pagina3=new Pagina(3);
$distribucion=new Seccion('Distribución por categoría');
$distribucion->agregarContenedor((new Contenedor())->agregarComponente(new GraficaCircular(['Software','Servicios','Formación'],[55,30,15])));
$pagina3->agregarSeccion($distribucion); $documento->agregarPagina($pagina3);
?>
<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?=esc($documento->titulo)?></title>
<style>
*{box-sizing:border-box}body{margin:0;background:#e5e7eb;color:#1e293b;font:15px/1.5 Arial,sans-serif}.documento-header{background:#0f172a;color:white;padding:18px 24px;text-align:center}.documento-header h1{margin:0;font-size:22px}.pagina{width:210mm;min-height:297mm;margin:25px auto;padding:20mm 18mm 24mm;background:white;box-shadow:0 5px 25px #0002;position:relative}.pagina footer{position:absolute;bottom:12mm;left:0;right:0;text-align:center;color:#64748b;font-size:12px}.seccion{margin:0 0 25px}.seccion>h2{font-size:18px;color:#1d4ed8;border-bottom:2px solid #dbeafe;padding-bottom:8px}.contenedor{margin:14px 0;padding:12px;border-radius:8px;background:#f8fafc}.titulo{font-size:26px;margin:0 0 8px}.subtitulo{font-size:19px;color:#64748b;margin:0 0 12px}.encabezado{font-size:16px;margin:10px 0}.parrafo{margin:8px 0}.tabla-scroll{overflow-x:auto}table{width:100%;border-collapse:collapse;background:white}th,td{border:1px solid #cbd5e1;padding:9px;text-align:left}th{background:#dbeafe}tbody tr:nth-child(even){background:#f1f5f9}.grafica{display:block;width:100%;height:auto;max-height:280px}.indicador{display:inline-block;vertical-align:top;background:white;border:1px solid #e2e8f0;border-radius:10px;padding:14px;margin:4px;width:calc(33.333% - 12px);min-width:130px}.indicador-valor{font-size:24px;font-weight:bold;color:#1d4ed8}.indicador-descripcion{font-size:12px;color:#64748b}.progreso{background:#e2e8f0;height:9px;border-radius:9px;margin-top:10px;overflow:hidden}.progreso>div{background:#2563eb;height:100%}@media(max-width:850px){.pagina{width:100%;min-height:auto;padding:25px}.indicador{width:100%;margin:4px 0}}@media print{@page{size:A4;margin:0}body{background:white}.documento-header{display:none}.pagina{margin:0;width:210mm;height:297mm;min-height:0;box-shadow:none;break-after:page;overflow:hidden}.pagina:last-child{break-after:auto}.contenedor,.indicador,.seccion{break-inside:avoid}}
</style></head><body><?=$documento->render()?></body></html>
