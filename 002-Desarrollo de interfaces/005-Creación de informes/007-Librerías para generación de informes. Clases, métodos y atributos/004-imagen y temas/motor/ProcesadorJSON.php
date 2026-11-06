<?php
/** Convierte un documento JSON validado en un objeto Informe. */
final class ProcesadorJSON {
    private array $datos = [];
    private const TIPOS = ['imagen','titulo','subtitulo','encabezado','parrafo','tabla','barras','lineas','circular','kpi','contador','porcentaje','texto_enriquecido','etiqueta','valor','etiqueta_valor','lista_vinetas','lista_numerada','cita','nota','nota_pie','texto_legal','texto_dinamico','texto_condicional','tabla_simple','tabla_dinamica','tabla_agrupada','tabla_jerarquica','tabla_pivote','matriz','tabla_comparativa','tabla_totales','tabla_frecuencias','tabla_calendario','tabla_doble_entrada','tabla_celdas_fusionadas','tabla_formato_condicional','tabla_formulas','area','dispersion','indicador_grafico','barras_horizontales','barras_apiladas','anillos','histograma','radar','burbujas','mapa_calor','caja','cascada','embudo','gantt','linea_temporal','sparkline'];

    public function procesar(array $datos): Informe {
        $this->claves($datos, ['version','titulo','paginas','datos'], 'documento');
        if (($datos['version'] ?? 1) !== 1) {
            throw new InvalidArgumentException('Versión de JSON no compatible. Utiliza version: 1.');
        }
        $this->datos = $datos['datos'] ?? [];
        if (!is_array($this->datos)) throw new InvalidArgumentException('datos debe ser un objeto.');
        $informe = new Informe($this->cadena($datos, 'titulo', 'documento'));
        $paginas = $this->lista($datos, 'paginas', 'documento');
        if (count($paginas) < 1 || count($paginas) > 100) {
            throw new InvalidArgumentException('El documento debe tener entre 1 y 100 páginas.');
        }
        foreach ($paginas as $i => $pagina) {
            $ruta = 'paginas[' . $i . ']';
            $this->objeto($pagina, $ruta);
            $this->claves($pagina, ['secciones'], $ruta);
            $informe->nuevaPagina();
            foreach ($this->lista($pagina, 'secciones', $ruta) as $j => $seccion) {
                $sr = $ruta . '.secciones[' . $j . ']';
                $this->objeto($seccion, $sr);
                $this->claves($seccion, ['titulo','contenedores'], $sr);
                $informe->nuevaSeccion($this->cadena($seccion, 'titulo', $sr, ''));
                foreach ($this->lista($seccion, 'contenedores', $sr) as $k => $contenedor) {
                    $cr = $sr . '.contenedores[' . $k . ']';
                    $this->objeto($contenedor, $cr);
                    $this->claves($contenedor, ['componentes'], $cr);
                    $informe->nuevoContenedor();
                    foreach ($this->lista($contenedor, 'componentes', $cr) as $m => $componente) {
                        $this->objeto($componente, $cr . '.componentes[' . $m . ']');
                        $this->componente($informe, $componente, $cr . '.componentes[' . $m . ']');
                    }
                }
            }
        }
        return $informe;
    }

    private function componente(Informe $informe, array $c, string $ruta): void {
        $tipo = $this->cadena($c, 'tipo', $ruta);
        if (!in_array($tipo, self::TIPOS, true)) {
            throw new InvalidArgumentException($ruta . ': tipo desconocido: ' . $tipo);
        }
        $extendidos = [
            'texto_enriquecido'=>TextoEnriquecido::class,'etiqueta'=>Etiqueta::class,'valor'=>Valor::class,
            'etiqueta_valor'=>EtiquetaValor::class,'lista_vinetas'=>ListaVinetas::class,'lista_numerada'=>ListaNumerada::class,
            'cita'=>Cita::class,'nota'=>Nota::class,'nota_pie'=>NotaPie::class,'texto_legal'=>TextoLegal::class,
            'texto_dinamico'=>TextoDinamico::class,'texto_condicional'=>TextoCondicional::class,
            'tabla_dinamica'=>TablaDinamica::class,'tabla_agrupada'=>TablaAgrupada::class,
            'tabla_jerarquica'=>TablaJerarquica::class,'tabla_pivote'=>TablaPivote::class,'matriz'=>Matriz::class,
            'tabla_comparativa'=>TablaComparativa::class,'tabla_totales'=>TablaTotales::class,
            'tabla_frecuencias'=>TablaFrecuencias::class,'tabla_calendario'=>TablaCalendario::class,
            'tabla_doble_entrada'=>TablaDobleEntrada::class,'tabla_celdas_fusionadas'=>TablaCeldasFusionadas::class,
            'tabla_formato_condicional'=>TablaFormatoCondicional::class,'tabla_formulas'=>TablaFormulas::class
        ];
        $graficasNuevas = ['area'=>GraficaArea::class,'dispersion'=>GraficaDispersion::class,'indicador_grafico'=>GraficaIndicador::class,'barras_horizontales'=>GraficaBarrasHorizontales::class,'barras_apiladas'=>GraficaBarrasApiladas::class,'anillos'=>GraficaAnillos::class,'histograma'=>Histograma::class,'radar'=>GraficaRadar::class,'burbujas'=>GraficaBurbujas::class,'mapa_calor'=>MapaCalor::class,'caja'=>DiagramaCaja::class,'cascada'=>DiagramaCascada::class,'embudo'=>DiagramaEmbudo::class,'gantt'=>DiagramaGantt::class,'linea_temporal'=>LineaTemporal::class,'sparkline'=>Sparkline::class];
        if (isset($graficasNuevas[$tipo])) {
            $this->validarGraficaNueva($c, $ruta);
            $informe->agregar(new $graficasNuevas[$tipo]($c));
            return;
        }
        if ($tipo === 'imagen') {
            $this->claves($c,['tipo','src','alt','pie','ancho'],$ruta);
            $informe->agregar(new Imagen($c));
            return;
        }
        if ($tipo === 'tabla_simple') $tipo = 'tabla';
        if (isset($extendidos[$tipo])) {
            $this->validarExtendido($tipo, $c, $ruta);
            if (str_starts_with($tipo, 'tabla_') || $tipo === 'matriz') {
                $this->opcionesTabla($c, count($c['columnas'] ?? []), $ruta);
            }
            $informe->agregar(new $extendidos[$tipo]($c, $this->datos));
            return;
        }
        $campos = match ($tipo) {
            'titulo','subtitulo','parrafo' => ['tipo','texto'],
            'encabezado' => ['tipo','texto','nivel'],
            'tabla' => ['tipo','columnas','filas','numerar_filas','mostrar_recuento','totales'],
            'barras','lineas','circular' => ['tipo','titulo','etiquetas','valores','ancho','alto'],
            default => ['tipo','etiqueta','valor','descripcion']
        };
        $this->claves($c, $campos, $ruta);
        switch ($tipo) {
            case 'titulo': $informe->titulo($this->cadena($c,'texto',$ruta)); break;
            case 'subtitulo': $informe->subtitulo($this->cadena($c,'texto',$ruta)); break;
            case 'parrafo': $informe->parrafo($this->cadena($c,'texto',$ruta)); break;
            case 'encabezado':
                $nivel = $this->entero($c,'nivel',$ruta,3);
                if ($nivel < 3 || $nivel > 6) throw new InvalidArgumentException($ruta . ': nivel debe estar entre 3 y 6.');
                $informe->encabezado($this->cadena($c,'texto',$ruta),$nivel); break;
            case 'tabla':
                $columnas = $this->lista($c,'columnas',$ruta);
                $filas = $this->lista($c,'filas',$ruta);
                if (!$columnas) throw new InvalidArgumentException($ruta . ': columnas no puede estar vacío.');
                foreach ($columnas as $v) if (!is_string($v)) throw new InvalidArgumentException($ruta . ': columnas debe contener textos.');
                foreach ($filas as $fila) {
                    if (!is_array($fila) || !array_is_list($fila) || count($fila) !== count($columnas)) throw new InvalidArgumentException($ruta . ': cada fila debe tener tantas celdas como columnas.');
                    foreach ($fila as $v) if (!is_scalar($v) && $v !== null) throw new InvalidArgumentException($ruta . ': las celdas deben ser escalares.');
                }
                $tabla = new TablaSimple($columnas, $filas);
                $tabla->configurarOpciones($this->opcionesTabla($c, count($columnas), $ruta));
                $informe->agregar($tabla); break;
            case 'barras': case 'lineas': case 'circular':
                $etiquetas = $this->lista($c,'etiquetas',$ruta);
                $valores = $this->lista($c,'valores',$ruta);
                if (count($etiquetas) !== count($valores)) throw new InvalidArgumentException($ruta . ': etiquetas y valores deben tener la misma longitud.');
                if (count($valores) > 50) throw new InvalidArgumentException($ruta . ': máximo 50 valores.');
                foreach ($etiquetas as $v) if (!is_string($v)) throw new InvalidArgumentException($ruta . ': etiquetas deben ser textos.');
                foreach ($valores as $v) if (!is_int($v) && !is_float($v)) throw new InvalidArgumentException($ruta . ': valores deben ser números JSON.');
                if ($tipo === 'circular' && min([0,...$valores]) < 0) throw new InvalidArgumentException($ruta . ': circular no admite negativos.');
                $ancho = $this->entero($c,'ancho',$ruta,600);
                $alto = $this->entero($c,'alto',$ruta,300);
                if ($ancho < 300 || $ancho > 1600 || $alto < 200 || $alto > 1200) throw new InvalidArgumentException($ruta . ': dimensiones fuera de rango.');
                $informe->{$tipo}($this->cadena($c,'titulo',$ruta),$etiquetas,$valores,$ancho,$alto); break;
            case 'kpi':
                $informe->kpi($this->cadena($c,'etiqueta',$ruta),$this->valorTexto($c,'valor',$ruta),$this->cadena($c,'descripcion',$ruta,'')); break;
            case 'contador':
                $informe->contador($this->cadena($c,'etiqueta',$ruta),$this->entero($c,'valor',$ruta),$this->cadena($c,'descripcion',$ruta,'')); break;
            case 'porcentaje':
                $v = $this->numero($c,'valor',$ruta);
                if ($v < 0 || $v > 100) throw new InvalidArgumentException($ruta . ': porcentaje debe estar entre 0 y 100.');
                $informe->porcentaje($this->cadena($c,'etiqueta',$ruta),$v,$this->cadena($c,'descripcion',$ruta,'')); break;
        }
    }
    private function validarGraficaNueva(array $c, string $ruta): void {
        $tipo=$c['tipo'];
        $campos=['tipo','titulo','ancho','alto'];
        $campos=array_merge($campos,match($tipo){
            'area','barras_horizontales','anillos','radar','cascada','embudo','sparkline'=>['etiquetas','valores'],
            'histograma','caja'=>['valores','intervalos'],
            'barras_apiladas'=>['etiquetas','series'],
            'dispersion','burbujas'=>['puntos'],
            'mapa_calor'=>['matriz'],
            'gantt'=>['tareas'],
            'linea_temporal'=>['eventos'],
            'indicador_grafico'=>['valor','minimo','maximo'],default=>[]});
        $this->claves($c,$campos,$ruta);
        $this->cadena($c,'titulo',$ruta);
        foreach(['ancho'=>[300,1600],'alto'=>[200,1200]] as $key=>$bounds){$v=$this->entero($c,$key,$ruta,$key==='ancho'?600:300);if($v<$bounds[0]||$v>$bounds[1])throw new InvalidArgumentException("$ruta.$key fuera de rango.");}
        $checkNums=function(array $arr,string $where) {if(count($arr)<1||count($arr)>200)throw new InvalidArgumentException("$where debe tener entre 1 y 200 elementos.");foreach($arr as $v)if(!is_int($v)&&!is_float($v))throw new InvalidArgumentException("$where contiene valores no numéricos.");};
        if(isset($c['valores'])){$checkNums($this->lista($c,'valores',$ruta),"$ruta.valores");}
        if(isset($c['etiquetas'])){ $labels=$this->lista($c,'etiquetas',$ruta);foreach($labels as $lab)if(!is_string($lab))throw new InvalidArgumentException("$ruta.etiquetas debe contener textos.");if(isset($c['valores'])&&count($labels)!==count($c['valores']))throw new InvalidArgumentException("$ruta: etiquetas y valores no coinciden.");}
        if(in_array($tipo,['anillos','embudo','barras_horizontales','radar'],true)&&min(...$c['valores'])<0)throw new InvalidArgumentException("$ruta: no admite negativos.");
        if($tipo==='radar'&&count($c['valores'])<3)throw new InvalidArgumentException("$ruta: radar requiere tres ejes.");
        if($tipo==='anillos'&&array_sum($c['valores'])<=0)throw new InvalidArgumentException("$ruta: suma debe ser positiva.");
        if($tipo==='histograma'&&($this->entero($c,'intervalos',$ruta,8)<1||($c['intervalos']??8)>50))throw new InvalidArgumentException("$ruta: intervalos entre 1 y 50.");
        if($tipo==='barras_apiladas'){$labels=$this->lista($c,'etiquetas',$ruta);$series=$this->lista($c,'series',$ruta);if(!$labels||!$series)throw new InvalidArgumentException("$ruta: etiquetas y series obligatorias.");foreach($series as $s){if(!is_array($s)||!isset($s['nombre'],$s['valores'])||!is_string($s['nombre'])||!is_array($s['valores'])||count($s['valores'])!==count($labels))throw new InvalidArgumentException("$ruta: serie inválida.");$checkNums($s['valores'],"$ruta.series.valores");if(min(...$s['valores'])<0)throw new InvalidArgumentException("$ruta: apiladas no admite negativos.");}}
        if(in_array($tipo,['dispersion','burbujas'],true)){ $rows=$this->lista($c,'puntos',$ruta);if(!$rows||count($rows)>200)throw new InvalidArgumentException("$ruta.puntos inválido.");foreach($rows as $r){foreach($tipo==='burbujas'?['x','y','z']:['x','y'] as $key){if(!isset($r[$key])||!is_numeric($r[$key])||!is_int($r[$key])&&!is_float($r[$key]))throw new InvalidArgumentException("$ruta.puntos.$key inválido.");}if($tipo==='burbujas'&&$r['z']<0)throw new InvalidArgumentException("$ruta: z no negativo.");}}
        if($tipo==='mapa_calor'){$matrix=$this->lista($c,'matriz',$ruta);if(!$matrix||count($matrix)>40)throw new InvalidArgumentException("$ruta.matriz inválida.");$width=count($matrix[0]??[]);if($width<1||$width>40)throw new InvalidArgumentException("$ruta.matriz ancho inválido.");foreach($matrix as $row){if(!is_array($row)||count($row)!==$width)throw new InvalidArgumentException("$ruta.matriz no rectangular.");$checkNums($row,"$ruta.matriz");}}
        foreach(['gantt'=>['tareas','inicio','fin','nombre'],'linea_temporal'=>['eventos','fecha',null,'texto']] as $kind=>$spec){if($tipo!==$kind)continue;$rows=$this->lista($c,$spec[0],$ruta);if(!$rows||count($rows)>40)throw new InvalidArgumentException("$ruta: lista vacía o demasiado grande.");foreach($rows as $r){foreach(array_filter(array_slice($spec,1)) as $key){if(!isset($r[$key])||!is_string($r[$key]))throw new InvalidArgumentException("$ruta.$key obligatorio.");}foreach(array_filter([$spec[1],$spec[2]]) as $key){$dt=DateTimeImmutable::createFromFormat('!Y-m-d',$r[$key]);if(!$dt||$dt->format('Y-m-d')!==$r[$key])throw new InvalidArgumentException("$ruta.$key fecha inválida.");}if($kind==='gantt'&&$r['fin']<$r['inicio'])throw new InvalidArgumentException("$ruta: fin anterior a inicio.");}}
        if($tipo==='indicador_grafico'){$v=$this->numero($c,'valor',$ruta);$min=$c['minimo']??0;$max=$c['maximo']??100;if(!is_numeric($min)||!is_numeric($max)||$max<=$min||$v<$min||$v>$max)throw new InvalidArgumentException("$ruta: rango de indicador inválido.");}
    }

    private function opcionesTabla(array $c, int $cantidadColumnas, string $ruta): array {
        foreach (['numerar_filas', 'mostrar_recuento'] as $clave) {
            if (isset($c[$clave]) && !is_bool($c[$clave])) {
                throw new InvalidArgumentException("$ruta.$clave debe ser booleano.");
            }
        }
        if (isset($c['totales'])) {
            if (!is_array($c['totales']) || !array_is_list($c['totales'])) {
                throw new InvalidArgumentException("$ruta.totales debe ser una lista de índices de columna.");
            }
            foreach ($c['totales'] as $indice) {
                if (!is_int($indice) || $indice < 0 || ($cantidadColumnas > 0 && $indice >= $cantidadColumnas)) {
                    throw new InvalidArgumentException("$ruta.totales contiene un índice de columna inválido.");
                }
            }
        }
        return array_intersect_key($c, array_flip(['numerar_filas', 'mostrar_recuento', 'totales']));
    }

    private function validarExtendido(string $tipo,array $c,string $ruta): void {
        $requeridos = match($tipo) {
            'texto_enriquecido'=>['fragmentos'], 'etiqueta_valor'=>['etiqueta','valor'],
            'lista_vinetas','lista_numerada'=>['elementos'], 'texto_condicional'=>['texto','regla'],
            'tabla_agrupada'=>['agrupar_por','columnas'], 'tabla_jerarquica'=>['columnas'],
            'tabla_pivote','tabla_doble_entrada'=>['campo_fila','campo_columna','campo_valor'],
            'matriz'=>['filas','columnas','valores'],
            'tabla_frecuencias'=>['campo'], 'tabla_calendario'=>['mes'],
            'tabla_formato_condicional'=>['columnas','reglas'],
            'tabla_formulas'=>['columnas','formulas'],
            default=>str_starts_with($tipo,'tabla_')?['columnas']:['texto']
        };
        foreach ($requeridos as $campo) if(!array_key_exists($campo,$c)) throw new InvalidArgumentException("$ruta.$campo: campo obligatorio.");
        $arrays=['fragmentos','elementos','columnas','filas','valores','reglas','formulas'];
        foreach($arrays as $campo) if(isset($c[$campo]) && (!is_array($c[$campo]) || !array_is_list($c[$campo]))) throw new InvalidArgumentException("$ruta.$campo: se espera array.");
        if(isset($c['origen']) && !preg_match('/^[a-zA-Z0-9_.]+$/D',$c['origen'])) throw new InvalidArgumentException("$ruta.origen inválido.");
        if(isset($c['regla'])) {
            if(!is_array($c['regla']) || !in_array($c['regla']['operador']??'igual',['igual','distinto','mayor','menor','mayor_igual','menor_igual','existe'],true)) throw new InvalidArgumentException("$ruta.regla inválida.");
        }
        foreach($c['reglas']??[] as $r) if(!in_array($r['clase']??'', ['celda-positiva','celda-negativa','celda-alerta'],true) || !in_array($r['operador']??'', ['mayor','menor','igual'],true)) throw new InvalidArgumentException("$ruta.reglas inválidas.");
        foreach($c['formulas']??[] as $f) if(!in_array($f['operador']??'', ['sumar','restar','multiplicar','dividir'],true)) throw new InvalidArgumentException("$ruta.formulas inválidas.");
        foreach($c['fragmentos']??[] as $f) if(isset($f['enlace']) && !filter_var($f['enlace'], FILTER_VALIDATE_URL)) throw new InvalidArgumentException('Enlace inválido.');
    }
    private function objeto(mixed $valor, string $ruta): void {
        if (!is_array($valor) || array_is_list($valor)) throw new InvalidArgumentException($ruta . ': se espera objeto JSON.');
    }
    private function claves(array $obj, array $permitidas, string $ruta): void {
        foreach ($obj as $clave => $_) if (!in_array($clave,$permitidas,true)) throw new InvalidArgumentException($ruta . ': propiedad desconocida: ' . $clave);
    }
    private function lista(array $obj, string $campo, string $ruta): array {
        if (!isset($obj[$campo]) || !is_array($obj[$campo]) || !array_is_list($obj[$campo])) throw new InvalidArgumentException($ruta . '.' . $campo . ': se espera array JSON.');
        return $obj[$campo];
    }
    private function cadena(array $obj, string $campo, string $ruta, ?string $defecto = null): string {
        if (!array_key_exists($campo,$obj) && $defecto !== null) return $defecto;
        if (!isset($obj[$campo]) || !is_string($obj[$campo])) throw new InvalidArgumentException($ruta . '.' . $campo . ': se espera texto.');
        return $obj[$campo];
    }
    private function entero(array $obj, string $campo, string $ruta, ?int $defecto = null): int {
        if (!array_key_exists($campo,$obj) && $defecto !== null) return $defecto;
        if (!isset($obj[$campo]) || !is_int($obj[$campo])) throw new InvalidArgumentException($ruta . '.' . $campo . ': se espera entero.');
        return $obj[$campo];
    }
    private function numero(array $obj, string $campo, string $ruta): float {
        if (!isset($obj[$campo]) || (!is_int($obj[$campo]) && !is_float($obj[$campo]))) throw new InvalidArgumentException($ruta . '.' . $campo . ': se espera número.');
        return (float)$obj[$campo];
    }
    private function valorTexto(array $obj, string $campo, string $ruta): string {
        if (!isset($obj[$campo]) || (!is_string($obj[$campo]) && !is_int($obj[$campo]) && !is_float($obj[$campo]))) throw new InvalidArgumentException($ruta . '.' . $campo . ': se espera texto o número.');
        return (string)$obj[$campo];
    }
}
