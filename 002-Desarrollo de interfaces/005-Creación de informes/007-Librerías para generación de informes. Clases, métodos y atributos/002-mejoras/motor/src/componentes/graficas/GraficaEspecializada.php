<?php
/** Gráficas SVG declarativas. Sin dependencias JavaScript. */
abstract class GraficaEspecializada extends Grafica {
    protected array $config;
    public function __construct(array $config) {
        $this->config = $config;
        parent::__construct($config['titulo'], [], [], $config['ancho'] ?? 600, $config['alto'] ?? 300);
    }
    protected function rect(float $x,float $y,float $w,float $h,string $fill,string $extra=''): string {
        return '<rect x="'.$x.'" y="'.$y.'" width="'.max(0,$w).'" height="'.max(0,$h).'" fill="'.$fill.'" '.$extra.'/>';
    }
    protected function line(float $x,float $y,float $xx,float $yy,string $color='#94a3b8',float $width=1): string {
        return '<line x1="'.$x.'" y1="'.$y.'" x2="'.$xx.'" y2="'.$yy.'" stroke="'.$color.'" stroke-width="'.$width.'"/>';
    }
    protected function txt(float $x,float $y,string $value,int $size=11,string $anchor='middle'): string {
        return '<text x="'.$x.'" y="'.$y.'" font-size="'.$size.'" text-anchor="'.$anchor.'" fill="#334155">'.escapar($value).'</text>';
    }
    protected function circle(float $x,float $y,float $r,string $fill): string {
        return '<circle cx="'.$x.'" cy="'.$y.'" r="'.max(0,$r).'" fill="'.$fill.'"/>';
    }
    protected function poly(array $pts,string $color='#2563eb',bool $fill=false): string {
        $pairs=array_map(fn($p)=>round($p[0],2).','.round($p[1],2),$pts);
        return '<polygon points="'.implode(' ',$pairs).'" fill="'.($fill?$color:'none').'" fill-opacity="0.25" stroke="'.$color.'" stroke-width="2"/>';
    }
    protected function color(int $i): string {return $this->colores[$i%count($this->colores)];}
    protected function maxAbs(array $v): float {return max(1,...array_map('abs',$v));}
    protected function vals(string $key='valores'): array {return array_map('floatval',$this->config[$key]??[]);}
    protected function labels(string $key='etiquetas'): array {return $this->config[$key]??[];}
    protected function xy(float $x,float $y,float $xmin,float $xmax,float $ymin,float $ymax): array {
        return [55+($x-$xmin)/max(1e-9,$xmax-$xmin)*($this->ancho-85), $this->alto-40-($y-$ymin)/max(1e-9,$ymax-$ymin)*($this->alto-75)];
    }
    protected function points(array $rows,string $x='x',string $y='y'): array {
        $xs=array_column($rows,$x);$ys=array_column($rows,$y);
        $xmin=min(0,...$xs);$xmax=max(1,...$xs);$ymin=min(0,...$ys);$ymax=max(1,...$ys);
        return array_map(fn($r)=>$this->xy($r[$x],$r[$y],$xmin,$xmax,$ymin,$ymax),$rows);
    }
    public function render(): string {
        $c=$this->config;$type=$c['tipo'];$w=$this->ancho;$h=$this->alto;$svg=$this->iniciarSVG();
        $labels=$this->labels();$v=$this->vals();$n=count($v);$left=55;$top=25;$pw=$w-85;$ph=$h-75;
        switch($type) {
            case 'area':
            case 'sparkline':
                $max=max(1,...$v);$min=min(0,...$v);$pts=[];
                foreach($v as $i=>$val){$pts[]=[($type==='sparkline'?12:$left)+$i*($type==='sparkline'?$w-24:$pw)/max(1,$n-1),($type==='sparkline'?$h-12:$h-40)-($val-$min)/max(1e-9,$max-$min)*($type==='sparkline'?$h-24:$ph)];}
                if($type==='area'&&$pts){$area=array_merge([[$left,$h-40]],$pts,[[$pts[count($pts)-1][0],$h-40]]);$svg.=$this->poly($area,'#93c5fd',true);}
                $svg.=$this->poly($pts);break;
            case 'barras_horizontales':
                $max=max(1,...$v);foreach($v as $i=>$val){$y=25+$i*($h-45)/max(1,$n);$svg.=$this->txt(100,$y+13,(string)($labels[$i]??$i),11,'end');$svg.=$this->rect(110,$y,max(0,$val)/$max*($w-135),min(25,($h-45)/max(1,$n)*.72),$this->color($i));}break;
            case 'barras_apiladas':
                $series=$c['series'];$totals=[];foreach($labels as $i=>$_){$total=0;foreach($series as $s){$total+=$s['valores'][$i];}$totals[]=$total;}$max=max(1,...$totals);
                foreach($labels as $i=>$lab){$x=$left+$i*$pw/count($labels)+8;$bottom=$h-40;foreach($series as $j=>$s){$val=$s['valores'][$i];$bh=$val/$max*$ph;$bottom-=$bh;$svg.=$this->rect($x,$bottom,$pw/count($labels)-16,$bh,$this->color($j));}$svg.=$this->txt($x+($pw/count($labels)-16)/2,$h-22,$lab);}break;
            case 'anillos':
                $sum=array_sum($v);$r=min($w*.27,$h*.36);$cx=$w*.34;$cy=$h*.5;$angle=-90;
                foreach($v as $i=>$val){if($val<=0)continue;$part=$val/$sum*360;$start=deg2rad($angle);$end=deg2rad($angle+$part);$large=$part>180?1:0;
                    if($part>=359.999){$svg.=$this->circle($cx,$cy,$r,$this->color($i));}
                    else{$svg.='<path d="M '.($cx+$r*cos($start)).' '.($cy+$r*sin($start)).' A '.$r.' '.$r.' 0 '.$large.' 1 '.($cx+$r*cos($end)).' '.($cy+$r*sin($end)).'" stroke="'.$this->color($i).'" stroke-width="'.($r*.46).'" fill="none"/>';}$angle+=$part;
                    $svg.=$this->txt($w*.7,35+$i*23,($labels[$i]??$i).' '.round($val/$sum*100,1).'%',11,'start');}
                $svg.=$this->circle($cx,$cy,$r*.72,'#fff');break;
            case 'histograma':
                $bins=$c['intervalos']??8;$min=min(...$v);$max=max(...$v);$step=max(1e-9,($max-$min)/$bins);$counts=array_fill(0,$bins,0);foreach($v as $val){$counts[min($bins-1,(int)floor(($val-$min)/$step))]++;}$highest=max(1,...$counts);
                foreach($counts as $i=>$count){$bw=$pw/$bins;$bh=$count/$highest*$ph;$svg.=$this->rect($left+$i*$bw,$h-40-$bh,$bw-1,$bh,'#2563eb');$svg.=$this->txt($left+($i+.5)*$bw,$h-20,(string)round($min+$i*$step,1),9);}break;
            case 'radar':
                $count=count($labels);$cx=$w/2;$cy=$h/2;$radius=min($w,$h)*.32;$max=max(1,...$v);$pts=[];
                foreach($labels as $i=>$lab){$a=-M_PI/2+$i*2*M_PI/$count;$svg.=$this->line($cx,$cy,$cx+$radius*cos($a),$cy+$radius*sin($a));$svg.=$this->txt($cx+($radius+19)*cos($a),$cy+($radius+19)*sin($a),$lab,10);$pts[]=[$cx+$radius*$v[$i]/$max*cos($a),$cy+$radius*$v[$i]/$max*sin($a)];}$svg.=$this->poly($pts,'#2563eb',true);break;
            case 'dispersion':case 'burbujas':
                $rows=$c['puntos'];$pts=$this->points($rows);$zs=array_column($rows,'z');$max=$zs?max(1,...$zs):1;foreach($pts as $i=>$p){$r=$type==='burbujas'?4+sqrt($rows[$i]['z']/$max)*18:5;$svg.=$this->circle($p[0],$p[1],$r,$this->color($i));}break;
            case 'mapa_calor':
                $matrix=$c['matriz'];$rows=count($matrix);$cols=count($matrix[0]);$flat=array_merge(...$matrix);$min=min(...$flat);$max=max(...$flat);$cw=$pw/$cols;$ch=$ph/$rows;
                foreach($matrix as $i=>$row)foreach($row as $j=>$val){$t=($val-$min)/max(1e-9,$max-$min);$red=(int)(240-205*$t);$green=(int)(247-135*$t);$blue=(int)(255-20*$t);$svg.=$this->rect($left+$j*$cw,$top+$i*$ch,$cw-2,$ch-2,sprintf('#%02x%02x%02x',$red,$green,$blue));$svg.=$this->txt($left+($j+.5)*$cw,$top+($i+.5)*$ch+4,(string)$val);}break;
            case 'caja':
                $sorted=$v;sort($sorted);$q=fn(float $p)=>$sorted[(int)floor((count($sorted)-1)*$p)]+($sorted[(int)ceil((count($sorted)-1)*$p)]-$sorted[(int)floor((count($sorted)-1)*$p)])*(($p*(count($sorted)-1))-floor($p*(count($sorted)-1)));
                $lo=$q(0);$q1=$q(.25);$med=$q(.5);$q3=$q(.75);$hi=$q(1);$xx=fn($v)=>$left+($v-$lo)/max(1e-9,$hi-$lo)*$pw;$mid=$h/2;
                $svg.=$this->line($xx($lo),$mid,$xx($hi),$mid,'#334155',2);$svg.=$this->rect($xx($q1),$mid-35,$xx($q3)-$xx($q1),70,'#bfdbfe','stroke="#2563eb"');$svg.=$this->line($xx($med),$mid-35,$xx($med),$mid+35,'#1d4ed8',3);foreach([$lo,$q1,$med,$q3,$hi] as $num)$svg.=$this->txt($xx($num),$mid+58,(string)round($num,2));break;
            case 'cascada':
                $current=0;$levels=[0];foreach($v as $val){$current+=$val;$levels[]=$current;}$min=min(0,...$levels);$max=max(1,...$levels);
                foreach($v as $i=>$val){$a=$levels[$i];$b=$levels[$i+1];$y1=$top+($max-max($a,$b))/max(1e-9,$max-$min)*$ph;$y2=$top+($max-min($a,$b))/max(1e-9,$max-$min)*$ph;$bw=$pw/max(1,$n);$svg.=$this->rect($left+$i*$bw+6,$y1,$bw-12,max(2,$y2-$y1),$val>=0?'#16a34a':'#dc2626');$svg.=$this->txt($left+($i+.5)*$bw,$h-20,(string)($labels[$i]??$i),10);}break;
            case 'embudo':
                $max=max(1,...$v);foreach($v as $i=>$val){$barw=$pw*$val/$max;$bh=$ph/max(1,$n);$x=$w/2-$barw/2;$y=$top+$i*$bh;$svg.=$this->rect($x,$y,$barw,$bh-3,$this->color($i));$svg.=$this->txt($w/2,$y+$bh/2+4,($labels[$i]??$i).' · '.$val,11);}break;
            case 'gantt':
                $tasks=$c['tareas'];$starts=array_map(fn($r)=>strtotime($r['inicio']),$tasks);$ends=array_map(fn($r)=>strtotime($r['fin']),$tasks);$min=min(...$starts);$max=max(...$ends);$range=max(1,$max-$min);
                foreach($tasks as $i=>$t){$y=$top+$i*$ph/count($tasks);$svg.=$this->txt(110,$y+16,$t['nombre'],10,'end');$x=125+($starts[$i]-$min)/$range*($w-150);$bw=max(3,($ends[$i]-$starts[$i])/$range*($w-150));$svg.=$this->rect($x,$y,$bw,min(22,$ph/count($tasks)*.7),$this->color($i));}break;
            case 'linea_temporal':
                $events=$c['eventos'];$dates=array_map(fn($r)=>strtotime($r['fecha']),$events);$min=min(...$dates);$max=max(...$dates);$cy=$h/2;$svg.=$this->line(40,$cy,$w-40,$cy,'#64748b',3);
                foreach($events as $i=>$e){$x=40+($dates[$i]-$min)/max(1,$max-$min)*($w-80);$svg.=$this->circle($x,$cy,6,$this->color($i));$svg.=$this->txt($x,$cy+($i%2?35:-25),$e['texto'],10);$svg.=$this->txt($x,$cy+($i%2?50:-40),$e['fecha'],9);}break;
            case 'indicador_grafico':
                $value=$c['valor'];$min=$c['minimo']??0;$max=$c['maximo']??100;$ratio=max(0,min(1,($value-$min)/max(1e-9,$max-$min)));$svg.=$this->rect(50,$h/2, $w-100,24,'#e2e8f0','rx="12"');$svg.=$this->rect(50,$h/2,($w-100)*$ratio,24,'#16a34a','rx="12"');$svg.=$this->txt($w/2,$h/2-20,(string)$value,32);break;
        }
        return $this->envolverGrafica($svg.$this->finalizarSVG());
    }
}
