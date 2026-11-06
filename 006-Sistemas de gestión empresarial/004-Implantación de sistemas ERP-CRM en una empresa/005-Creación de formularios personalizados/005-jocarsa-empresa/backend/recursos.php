
<?php
require_once __DIR__."/api/auth_common.php";
requireAdmin(false);
header("Cache-Control: no-store");
$archivo = "/home/josevicente/log/registros.csv";

$fechas = [];
$cpu = [];
$ram = [];
$disco = [];

if (is_readable($archivo) && ($f = fopen($archivo, "r")) !== false) {

    // Saltar cabecera
    fgetcsv($f, 1000, ",");

    while (($fila = fgetcsv($f, 1000, ",")) !== false) {
        if (count($fila) < 4) continue;

        $fechas[] = $fila[0];
        $cpu[] = (float)$fila[1];
        $ram[] = (float)$fila[2];
        $disco[] = (float)$fila[3];
    }

    fclose($f);
}

$actualCPU = count($cpu) ? end($cpu) : 0;
$actualRAM = count($ram) ? end($ram) : 0;
$actualDisco = count($disco) ? end($disco) : 0;
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<link rel="icon" type="image/svg+xml" href="https://static.jocarsa.com/logos/crimson.svg">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta http-equiv="refresh" content="60">
<title>Monitor del sistema</title>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
* {
    box-sizing: border-box;
}

body {
    margin: 0;
    padding: 20px;
    background: #10151e;
    color: #e8edf5;
    font-family: Arial, sans-serif;
}

h1 {
    margin: 0 0 20px;
    font-size: 24px;
}

.grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    grid-template-rows: repeat(2, minmax(0, 1fr));
    gap: 16px;
    height: calc(100vh - 90px);
}

.panel {
    background: #1b2432;
    border: 1px solid #303b4d;
    border-radius: 12px;
    padding: 18px;
    min-height: 0;
    display: flex;
    flex-direction: column;
}

.panel h2 {
    margin: 0 0 12px;
    font-size: 17px;
    font-weight: 500;
}

.grafica {
    position: relative;
    flex: 1;
    min-height: 0;
}

canvas {
    width: 100% !important;
    height: 100% !important;
}

@media (max-width: 900px) {
    .grid {
        grid-template-columns: 1fr;
        grid-template-rows: none;
        height: auto;
    }

    .panel {
        height: 270px;
    }
}
.monitor-header{display:flex;align-items:center;justify-content:space-between;gap:20px;margin-bottom:8px}
.monitor-header h1{margin:0}.monitor-header a{background:crimson;color:white;text-decoration:none;padding:10px 14px;border-radius:5px}
.monitor-info{color:#aab8cc;margin:0 0 15px;font-size:13px}
.grid{height:calc(100vh - 115px)}
@media(max-width:900px){.grid{height:auto}}
</style>
</head>

<body>

<header class="monitor-header"><h1>jocarsa | crimson · Recursos del sistema</h1><a href="index.html">Volver al ERP</a></header>
<p class="monitor-info">Histórico y última muestra registrada · Actualización cada 60 segundos</p>

<div class="grid">

    <div class="panel">
        <h2>Histórico CPU</h2>
        <div class="grafica">
            <canvas id="cpuLinea"></canvas>
        </div>
    </div>

    <div class="panel">
        <h2>Histórico RAM</h2>
        <div class="grafica">
            <canvas id="ramLinea"></canvas>
        </div>
    </div>

    <div class="panel">
        <h2>Histórico disco</h2>
        <div class="grafica">
            <canvas id="discoLinea"></canvas>
        </div>
    </div>

    <div class="panel">
        <h2>CPU actual</h2>
        <div class="grafica">
            <canvas id="cpuGauge"></canvas>
        </div>
    </div>

    <div class="panel">
        <h2>RAM actual</h2>
        <div class="grafica">
            <canvas id="ramGauge"></canvas>
        </div>
    </div>

    <div class="panel">
        <h2>Disco actual</h2>
        <div class="grafica">
            <canvas id="discoGauge"></canvas>
        </div>
    </div>

</div>

<script>
const fechas = <?php echo json_encode($fechas, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT); ?>;
const cpu = <?php echo json_encode($cpu); ?>;
const ram = <?php echo json_encode($ram); ?>;
const disco = <?php echo json_encode($disco); ?>;

const actualCPU = <?php echo json_encode($actualCPU); ?>;
const actualRAM = <?php echo json_encode($actualRAM); ?>;
const actualDisco = <?php echo json_encode($actualDisco); ?>;

Chart.defaults.color = "#aab8cc";
Chart.defaults.borderColor = "#344154";

// GRÁFICAS HISTÓRICAS

function crearLinea(id, datos, color) {
    new Chart(document.getElementById(id), {
        type: "line",
        data: {
            labels: fechas,
            datasets: [{
                label: "Uso (%)",
                data: datos,
                borderColor: color,
                backgroundColor: color + "25",
                fill: true,
                tension: 0.3,
                pointRadius: 0,
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: false,
            interaction: {
                intersect: false,
                mode: "index"
            },
            scales: {
                y: {
                    min: 0,
                    max: 100,
                    ticks: {
                        callback: value => value + "%"
                    }
                },
                x: {
                    ticks: {
                        maxTicksLimit: 7,
                        callback: function(value) {
                            const fecha = this.getLabelForValue(value);
                            return fecha.substring(11, 16);
                        }
                    }
                }
            },
            plugins: {
                legend: { display: false }
            }
        }
    });
}

// GAUGES SEMICIRCULARES

const textoGauge = {
    id: "textoGauge",
    afterDraw(chart) {
        if (chart.config.type !== "doughnut") return;

        const {ctx, chartArea} = chart;
        if (!chartArea) return;

        const valor = chart.options.plugins.valorGauge;
        if (valor === undefined) return;

        const meta = chart.getDatasetMeta(0);
        const arco = meta.data[0];
        if (!arco) return;

        ctx.save();
        ctx.textAlign = "center";
        ctx.textBaseline = "middle";
        ctx.fillStyle = "#ffffff";
        ctx.font = "bold 30px Arial";

        ctx.fillText(
            valor.toFixed(1) + "%",
            arco.x,
            arco.y - arco.outerRadius * 0.25
        );

        ctx.restore();
    }
};

Chart.register(textoGauge);

function crearGauge(id, valor, color) {
    valor = Math.max(0, Math.min(100, valor));

    new Chart(document.getElementById(id), {
        type: "doughnut",
        data: {
            datasets: [{
                data: [valor, 100 - valor],
                backgroundColor: [
                    color,
                    "#354153"
                ],
                borderWidth: 0,
                circumference: 180,
                rotation: 270,
                cutout: "75%"
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: {
                duration: 700
            },
            plugins: {
                legend: { display: false },
                tooltip: { enabled: false },
                valorGauge: valor
            }
        }
    });
}

// CREAR LAS SEIS GRÁFICAS

crearLinea("cpuLinea", cpu, "#38bdf8");
crearLinea("ramLinea", ram, "#34d399");
crearLinea("discoLinea", disco, "#fbbf24");

crearGauge("cpuGauge", actualCPU, "#38bdf8");
crearGauge("ramGauge", actualRAM, "#34d399");
crearGauge("discoGauge", actualDisco, "#fbbf24");
</script>

</body>
</html>
