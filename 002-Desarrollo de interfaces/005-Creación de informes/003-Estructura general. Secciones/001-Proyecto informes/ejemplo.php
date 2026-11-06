<?php
require_once __DIR__ . '/motor-informes.php';
require_once __DIR__ . '/Informe.php';

$informe = new Informe('Informe de resultados empresariales 2026');
$informe->nuevaPagina()->nuevaSeccion('Presentación')
    ->titulo('Informe anual 2026')
    ->subtitulo('Análisis de resultados empresariales')
    ->encabezado('Resumen ejecutivo')
    ->parrafo('Durante el ejercicio 2026, la empresa ha experimentado una evolución positiva en sus principales indicadores.')
    ->parrafo('Este documento recoge la facturación, los clientes y la distribución de ingresos.');
$informe->nuevaSeccion('Indicadores principales')
    ->kpi('Facturación anual', '150.000 €', 'Ingresos totales del ejercicio')
    ->contador('Clientes activos', 125, 'Clientes registrados')
    ->porcentaje('Objetivos alcanzados', 78.5, 'Cumplimiento del plan anual');
$informe->nuevaPagina()->nuevaSeccion('Análisis de ventas')
    ->encabezado('Ventas trimestrales')
    ->parrafo('La tabla muestra las ventas durante los cuatro trimestres.')
    ->tabla(['Trimestre', 'Ventas', 'Clientes'], [
        ['T1', '25.000 €', 20],
        ['T2', '35.000 €', 28],
        ['T3', '40.000 €', 35],
        ['T4', '50.000 €', 42]
    ]);
$informe->nuevaPagina()->nuevaSeccion('Evolución de resultados')
    ->barras('Ventas por trimestre', ['T1', 'T2', 'T3', 'T4'], [25, 35, 40, 50])
    ->lineas('Evolución de clientes', ['T1', 'T2', 'T3', 'T4'], [20, 28, 35, 42]);
$informe->nuevaPagina()->nuevaSeccion('Distribución de ingresos')
    ->circular('Ingresos por línea de negocio', ['Desarrollo', 'Consultoría', 'Formación', 'Soporte'], [45, 30, 15, 10]);
$informe->nuevaSeccion('Conclusiones')
    ->parrafo('Los resultados muestran un crecimiento progresivo de las ventas y del número de clientes.')
    ->parrafo('La empresa mantiene una distribución diversificada de sus ingresos.');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= escapar($informe->obtenerDocumento()->titulo) ?></title>
    <link rel="stylesheet" href="estilos.css">
</head>
<body>
    <?= $informe->render() ?>
</body>
</html>
