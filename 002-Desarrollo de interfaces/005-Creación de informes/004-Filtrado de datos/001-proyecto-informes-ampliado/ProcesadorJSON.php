<?php
/** Convierte un documento JSON validado en un objeto Informe. */
final class ProcesadorJSON {
    private array $datos = [];
    private const TIPOS = ['titulo','subtitulo','encabezado','parrafo','tabla','barras','lineas','circular','kpi','contador','porcentaje','texto_enriquecido','etiqueta','valor','etiqueta_valor','lista_vinetas','lista_numerada','cita','nota','nota_pie','texto_legal','texto_dinamico','texto_condicional','tabla_simple','tabla_dinamica','tabla_agrupada','tabla_jerarquica','tabla_pivote','matriz','tabla_comparativa','tabla_totales','tabla_frecuencias','tabla_calendario','tabla_doble_entrada','tabla_celdas_fusionadas','tabla_formato_condicional','tabla_formulas'];

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
        if ($tipo === 'tabla_simple') $tipo = 'tabla';
        if (isset($extendidos[$tipo])) {
            $this->validarExtendido($tipo, $c, $ruta);
            $informe->agregar(new $extendidos[$tipo]($c, $this->datos));
            return;
        }
        $campos = match ($tipo) {
            'titulo','subtitulo','parrafo' => ['tipo','texto'],
            'encabezado' => ['tipo','texto','nivel'],
            'tabla' => ['tipo','columnas','filas'],
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
                $informe->tabla($columnas,$filas); break;
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
