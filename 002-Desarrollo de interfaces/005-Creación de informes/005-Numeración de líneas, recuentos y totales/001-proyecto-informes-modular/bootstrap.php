<?php
/** Autocarga de clases mediante include/require_once y estructura jerárquica. */
require_once __DIR__ . '/src/util/escapar.php';
require_once __DIR__ . '/src/componentes/tablas/PresentacionTabla.php';
$mapaClases = [
    'Componente' => 'src/componentes/Componente.php',
    'Texto' => 'src/componentes/texto/Texto.php',
    'Titulo' => 'src/componentes/texto/Titulo.php',
    'Subtitulo' => 'src/componentes/texto/Subtitulo.php',
    'Encabezado' => 'src/componentes/texto/Encabezado.php',
    'Parrafo' => 'src/componentes/texto/Parrafo.php',
    'Tabla' => 'src/componentes/tablas/Tabla.php',
    'TablaSimple' => 'src/componentes/tablas/TablaSimple.php',
    'Grafica' => 'src/componentes/graficas/Grafica.php',
    'GraficaBarras' => 'src/componentes/graficas/GraficaBarras.php',
    'GraficaLineas' => 'src/componentes/graficas/GraficaLineas.php',
    'GraficaCircular' => 'src/componentes/graficas/GraficaCircular.php',
    'Indicador' => 'src/componentes/indicadores/Indicador.php',
    'KPI' => 'src/componentes/indicadores/KPI.php',
    'Contador' => 'src/componentes/indicadores/Contador.php',
    'Porcentaje' => 'src/componentes/indicadores/Porcentaje.php',
    'Contenedor' => 'src/documento/Contenedor.php',
    'Seccion' => 'src/documento/Seccion.php',
    'Pagina' => 'src/documento/Pagina.php',
    'Documento' => 'src/documento/Documento.php',
    'TextoAvanzado' => 'src/componentes/texto/TextoAvanzado.php',
    'TextoEnriquecido' => 'src/componentes/texto/TextoEnriquecido.php',
    'Etiqueta' => 'src/componentes/texto/Etiqueta.php',
    'Valor' => 'src/componentes/texto/Valor.php',
    'EtiquetaValor' => 'src/componentes/texto/EtiquetaValor.php',
    'ListaVinetas' => 'src/componentes/texto/ListaVinetas.php',
    'ListaNumerada' => 'src/componentes/texto/ListaNumerada.php',
    'Cita' => 'src/componentes/texto/Cita.php',
    'Nota' => 'src/componentes/texto/Nota.php',
    'NotaPie' => 'src/componentes/texto/NotaPie.php',
    'TextoLegal' => 'src/componentes/texto/TextoLegal.php',
    'TextoDinamico' => 'src/componentes/texto/TextoDinamico.php',
    'TextoCondicional' => 'src/componentes/texto/TextoCondicional.php',
    'TablaAvanzada' => 'src/componentes/tablas/TablaAvanzada.php',
    'TablaDinamica' => 'src/componentes/tablas/TablaDinamica.php',
    'TablaAgrupada' => 'src/componentes/tablas/TablaAgrupada.php',
    'TablaJerarquica' => 'src/componentes/tablas/TablaJerarquica.php',
    'TablaPivote' => 'src/componentes/tablas/TablaPivote.php',
    'Matriz' => 'src/componentes/tablas/Matriz.php',
    'TablaComparativa' => 'src/componentes/tablas/TablaComparativa.php',
    'TablaTotales' => 'src/componentes/tablas/TablaTotales.php',
    'TablaFrecuencias' => 'src/componentes/tablas/TablaFrecuencias.php',
    'TablaCalendario' => 'src/componentes/tablas/TablaCalendario.php',
    'TablaDobleEntrada' => 'src/componentes/tablas/TablaDobleEntrada.php',
    'TablaCeldasFusionadas' => 'src/componentes/tablas/TablaCeldasFusionadas.php',
    'TablaFormatoCondicional' => 'src/componentes/tablas/TablaFormatoCondicional.php',
    'TablaFormulas' => 'src/componentes/tablas/TablaFormulas.php',
];
spl_autoload_register(static function (string $clase) use ($mapaClases): void {
    if (isset($mapaClases[$clase])) require_once __DIR__ . '/' . $mapaClases[$clase];
});
require_once __DIR__ . '/Informe.php';
require_once __DIR__ . '/ProcesadorJSON.php';
require_once __DIR__ . '/plantilla.php';
