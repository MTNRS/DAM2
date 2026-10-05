<?php

function ollama($prompt) {

    $url = 'http://localhost:11434/api/generate';

    $data = [
        'model' => 'qwen2.5:3b',
        'prompt' => $prompt,
        'stream' => false
    ];

    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json'
        ],
        CURLOPT_POSTFIELDS => json_encode($data)
    ]);

    $response = curl_exec($ch);

    if ($response === false) {
        die('Error CURL: ' . curl_error($ch));
    }

    curl_close($ch);

    $result = json_decode($response, true);

    return $result['response'] ?? '';
}


// ============================================================
// 1. GENERACIÓN DEL TEXTO
// ============================================================

$promptGeneracion = <<<PROMPT

Escribe un texto en español compuesto por 6 párrafos.

Cada párrafo debe expresar claramente una emoción diferente.

Puedes utilizar emociones como:

alegria
tristeza
enfado
miedo
sorpresa
calma
amor
asco
ansiedad
esperanza
nostalgia
neutral

No escribas el nombre de la emoción.
No numeres los párrafos.

Cada párrafo debe tener entre 3 y 5 frases.

Separa cada párrafo mediante una línea en blanco.

PROMPT;

$texto = ollama($promptGeneracion);


// ============================================================
// 2. SEPARAR LOS PÁRRAFOS
// ============================================================

$parrafos = preg_split(
    '/\R\s*\R/',
    trim($texto)
);


// ============================================================
// 3. ANÁLISIS EMOCIONAL
// ============================================================

$promptAnalisis = <<<PROMPT

Analiza emocionalmente cada párrafo del siguiente texto.

Debes seleccionar EXACTAMENTE una de estas emociones:

alegria
tristeza
enfado
miedo
sorpresa
calma
amor
asco
ansiedad
esperanza
nostalgia
neutral

Devuelve ÚNICAMENTE JSON válido.

Formato:

[
    {
        "parrafo": 1,
        "emocion": "alegria",
        "valencia": 0.8,
        "intensidad": 0.7,
        "activacion": 0.6
    }
]

No uses markdown.
No uses bloques ```json.
No añadas explicaciones.

TEXTO:

$texto

PROMPT;

$respuestaAnalisis = ollama($promptAnalisis);

$analisis = json_decode($respuestaAnalisis, true);

if (!is_array($analisis)) {

    die(
        '<h2>Error interpretando el análisis</h2>' .
        '<pre>' .
        htmlspecialchars($respuestaAnalisis) .
        '</pre>'
    );
}


// ============================================================
// 4. CONSTRUIR DATOS PARA JAVASCRIPT
// ============================================================

$datos = [];

foreach ($parrafos as $indice => $parrafo) {

    $info = $analisis[$indice] ?? [];

    $emocion = strtolower(
        trim($info['emocion'] ?? 'neutral')
    );

    $datos[] = [
        'texto'      => $parrafo,
        'emocion'    => $emocion,
        'valencia'   => $info['valencia'] ?? 0,
        'intensidad' => $info['intensidad'] ?? 0,
        'activacion' => $info['activacion'] ?? 0
    ];
}

?>

<!DOCTYPE html>
<html lang="es">

<head>

<meta charset="UTF-8">

<title>Avatar emocional</title>

<style>

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    padding: 50px;

    font-family:
        system-ui,
        -apple-system,
        BlinkMacSystemFont,
        "Segoe UI",
        sans-serif;

    background: #f5f5f5;
    color: #333;
}


/* ============================================================
   TEXTO
   ============================================================ */

#texto {
    max-width: 800px;
    margin: auto;
    padding-right: 200px;
}

.parrafo {
    margin-bottom: 35px;
    line-height: 1.8;
    font-size: 20px;
}

.palabra {
    transition:
        background 0.15s,
        color 0.15s,
        opacity 0.15s;

    opacity: 0.22;
}

.palabra.leida {
    opacity: 1;
}

.palabra.actual {
    background: #222;
    color: white;

    padding: 2px 4px;
    border-radius: 4px;
}


/* ============================================================
   AVATAR FLOTANTE
   ============================================================ */

#avatar {
    position: fixed;

    right: 30px;
    bottom: 30px;

    width: 258px;
    height: 505px;

    pointer-events: none;

    z-index: 1000;
}


/* Imagen real */

#avatar img {
    width: 100%;
    height: 100%;

    object-fit: contain;

    transition:
        opacity 0.15s ease,
        transform 0.2s ease;
}


/* pequeña animación al cambiar de emoción */

#avatar.cambiando img {
    opacity: 0;
    transform: scale(0.97);
}


/* ============================================================
   DEBUG EMOCIONAL
   ============================================================ */

#estado {
    position: fixed;

    right: 30px;
    top: 30px;

    padding: 10px 15px;

    background: rgba(0, 0, 0, 0.75);
    color: white;

    border-radius: 6px;

    font-size: 13px;

    z-index: 1001;
}

</style>

</head>

<body>


<div id="texto"></div>


<div id="estado">
    Esperando...
</div>


<div id="avatar">

    <img
        id="imagenAvatar"
        src="expresiones/neutral.png"
        alt="Avatar"
    >

</div>


<script>

// ============================================================
// DATOS GENERADOS POR PHP
// ============================================================

const parrafos = <?= json_encode(
    $datos,
    JSON_UNESCAPED_UNICODE |
    JSON_UNESCAPED_SLASHES
) ?>;


// ============================================================
// CONFIGURACIÓN
// ============================================================

// milisegundos por palabra

const velocidad = 230;


// emociones válidas

const emocionesValidas = [
    "alegria",
    "amor",
    "ansiedad",
    "asco",
    "calma",
    "enfado",
    "esperanza",
    "miedo",
    "neutral",
    "nostalgia",
    "sorpresa",
    "tristeza"
];


// ============================================================
// REFERENCIAS DOM
// ============================================================

const contenedorTexto =
    document.querySelector("#texto");

const avatar =
    document.querySelector("#avatar");

const imagenAvatar =
    document.querySelector("#imagenAvatar");

const estado =
    document.querySelector("#estado");


// ============================================================
// CREAR EL TEXTO
// ============================================================

const elementosParrafos = [];

parrafos.forEach((parrafo, indiceParrafo) => {

    const p =
        document.createElement("div");

    p.className = "parrafo";


    // --------------------------------------------------------
    // Separamos por espacios conservando palabras
    // --------------------------------------------------------

    const palabras =
        parrafo.texto.trim().split(/\s+/);


    const elementosPalabras = [];


    palabras.forEach((palabra, indicePalabra) => {

        const span =
            document.createElement("span");

        span.className = "palabra";

        span.textContent = palabra;

        span.dataset.parrafo =
            indiceParrafo;

        span.dataset.palabra =
            indicePalabra;


        p.appendChild(span);

        p.appendChild(
            document.createTextNode(" ")
        );


        elementosPalabras.push(span);

    });


    contenedorTexto.appendChild(p);


    elementosParrafos.push({
        elemento: p,
        palabras: elementosPalabras
    });

});


// ============================================================
// CAMBIAR EMOCIÓN
// ============================================================

function cambiarEmocion(emocion) {

    emocion =
        emocion
            .toLowerCase()
            .trim();


    // Protección por si Qwen devuelve algo inesperado

    if (!emocionesValidas.includes(emocion)) {

        emocion = "neutral";

    }


    const nuevaImagen =
        "expresiones/" +
        emocion +
        ".png";


    // Si ya tenemos esa expresión no hacemos nada

    if (
        imagenAvatar.dataset.emocion === emocion
    ) {
        return;
    }


    avatar.classList.add("cambiando");


    setTimeout(() => {

        imagenAvatar.src =
            nuevaImagen;

        imagenAvatar.dataset.emocion =
            emocion;


        avatar.classList.remove(
            "cambiando"
        );

    }, 150);

}


// ============================================================
// ESPERAR
// ============================================================

function esperar(ms) {

    return new Promise(resolve => {

        setTimeout(resolve, ms);

    });

}


// ============================================================
// REPRODUCIR TEXTO
// ============================================================

async function reproducir() {

    for (
        let indiceParrafo = 0;
        indiceParrafo < parrafos.length;
        indiceParrafo++
    ) {

        const datosParrafo =
            parrafos[indiceParrafo];

        const elementos =
            elementosParrafos[indiceParrafo];


        // ====================================================
        // CAMBIAMOS EXPRESIÓN
        // ====================================================

        cambiarEmocion(
            datosParrafo.emocion
        );


        estado.innerHTML =
            "<strong>" +
            datosParrafo.emocion.toUpperCase() +
            "</strong>" +
            "<br>" +
            "Valencia: " +
            datosParrafo.valencia +
            "<br>" +
            "Intensidad: " +
            datosParrafo.intensidad +
            "<br>" +
            "Activación: " +
            datosParrafo.activacion;


        // ====================================================
        // RECORREMOS LAS PALABRAS
        // ====================================================

        for (
            let i = 0;
            i < elementos.palabras.length;
            i++
        ) {

            const palabra =
                elementos.palabras[i];


            // quitar highlight anterior

            document
                .querySelectorAll(
                    ".palabra.actual"
                )
                .forEach(elemento => {

                    elemento.classList.remove(
                        "actual"
                    );

                });


            // palabra actual

            palabra.classList.add(
                "actual"
            );

            palabra.classList.add(
                "leida"
            );


            // mantenerla visible

            palabra.scrollIntoView({
                behavior: "smooth",
                block: "center"
            });


            await esperar(
                velocidad
            );

        }


        // quitar última palabra activa

        elementos.palabras
            .forEach(palabra => {

                palabra.classList.remove(
                    "actual"
                );

            });


        // pequeña pausa entre párrafos

        await esperar(600);

    }


    // ========================================================
    // FINAL
    // ========================================================

    cambiarEmocion("neutral");

    estado.innerHTML =
        "<strong>FINALIZADO</strong>";

}


// ============================================================
// INICIAR
// ============================================================

reproducir();

</script>

</body>
</html>