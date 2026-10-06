<!DOCTYPE html>
<html lang="es">

<head>
<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>Avatar narrador emocional</title>

<style>

* {
    box-sizing: border-box;
}

html,
body {
    width: 100%;
    height: 100%;
}

body {
    margin: 0;
    overflow: hidden;

    font-family:
        system-ui,
        -apple-system,
        BlinkMacSystemFont,
        "Segoe UI",
        sans-serif;

    background:
        radial-gradient(
            circle at 50% 35%,
            #ffffff 0%,
            #f5f5f5 55%,
            #e8e8e8 100%
        );

    color: #222;
}


/* ============================================================
   BARRA DE PROGRESO
   ============================================================ */

#barra {
    position: fixed;

    left: 0;
    top: 0;

    width: 100%;
    height: 5px;

    background: rgba(0, 0, 0, 0.08);

    z-index: 2000;
}

#progreso {
    width: 0%;
    height: 100%;

    background: #222;

    transition:
        width 0.08s linear;
}


/* ============================================================
   ESCENA
   ============================================================ */

#escena {
    position: relative;

    width: 100%;
    height: 100vh;
}


/* ============================================================
   AVATAR
   ============================================================ */

#avatar {
    position: absolute;

    right: 8vw;
    bottom: 0;

    width: 258px;
    height: 505px;

    z-index: 10;

    pointer-events: none;
}

.capa-avatar {
    position: absolute;

    left: 0;
    top: 0;

    width: 100%;
    height: 100%;

    object-fit: contain;

    transition: none;
}

#avatarA {
    opacity: 1;
}

#avatarB {
    opacity: 0;
}


/* ============================================================
   BOCADILLO
   ============================================================ */

#bocadillo {
    position: absolute;

    right: calc(8vw + 220px);
    bottom: 330px;

    width: min(620px, 55vw);
    min-height: 150px;

    padding:
        28px
        32px;

    background: white;

    border:
        3px solid
        #222;

    border-radius:
        25px;

    box-shadow:
        0
        12px
        35px
        rgba(0, 0, 0, 0.12);

    font-size: 22px;
    line-height: 1.55;

    z-index: 20;
}


/* ============================================================
   COLA DEL BOCADILLO
   ============================================================ */

#bocadillo::before {
    content: "";

    position: absolute;

    right: -31px;
    bottom: 42px;

    width: 0;
    height: 0;

    border-top:
        20px solid
        transparent;

    border-bottom:
        20px solid
        transparent;

    border-left:
        32px solid
        #222;
}

#bocadillo::after {
    content: "";

    position: absolute;

    right: -25px;
    bottom: 45px;

    width: 0;
    height: 0;

    border-top:
        17px solid
        transparent;

    border-bottom:
        17px solid
        transparent;

    border-left:
        27px solid
        white;
}


/* ============================================================
   TEXTO DEL BOCADILLO
   ============================================================ */

#textoBocadillo {
    min-height: 70px;
}

.palabra {
    opacity: 0;

    transition:
        opacity 0.08s ease;
}

.palabra.visible {
    opacity: 1;
}


/* ============================================================
   CURSOR DE ESCRITURA
   ============================================================ */

#cursor {
    display: inline-block;

    width: 3px;
    height: 1em;

    margin-left: 3px;

    vertical-align: -2px;

    background: #222;

    animation:
        parpadeo
        0.7s
        infinite;
}

@keyframes parpadeo {

    0%,
    45% {
        opacity: 1;
    }

    46%,
    100% {
        opacity: 0;
    }
}


/* ============================================================
   ESTADO
   ============================================================ */

#estado {
    position: fixed;

    top: 25px;
    right: 25px;

    min-width: 180px;

    padding:
        12px
        16px;

    background:
        rgba(0, 0, 0, 0.78);

    color: white;

    border-radius: 8px;

    font-size: 13px;
    line-height: 1.6;

    z-index: 1001;

    backdrop-filter:
        blur(5px);
}

#estado .emocion {
    font-size: 16px;
    font-weight: bold;
}


/* ============================================================
   RESPONSIVE
   ============================================================ */

@media (max-width: 800px) {

    #avatar {
        width: 180px;
        height: 352px;

        right: 10px;
    }

    #bocadillo {
        left: 20px;
        right: 20px;
        bottom: 370px;

        width: auto;

        font-size: 18px;
    }

    #bocadillo::before,
    #bocadillo::after {
        display: none;
    }

    #estado {
        display: none;
    }
}

</style>
</head>

<body>


<!-- ==========================================================
     PROGRESO
     ========================================================== -->

<div id="barra">
    <div id="progreso"></div>
</div>


<!-- ==========================================================
     ESCENA
     ========================================================== -->

<div id="escena">


    <!-- ======================================================
         BOCADILLO
         ====================================================== -->

    <div id="bocadillo">

        <div id="textoBocadillo"></div>

        <span id="cursor"></span>

    </div>


    <!-- ======================================================
         AVATAR
         ====================================================== -->

    <div id="avatar">

        <img
            id="avatarA"
            class="capa-avatar"
            src="expresiones/neutral.png"
            alt=""
        >

        <img
            id="avatarB"
            class="capa-avatar"
            src="expresiones/neutral.png"
            alt=""
        >

    </div>

</div>


<!-- ==========================================================
     ESTADO
     ========================================================== -->

<div id="estado">

    <div class="emocion">
        NEUTRAL
    </div>

    <div>
        Preparando historia...
    </div>

</div>


<script>

// ============================================================
// HISTORIA EN MEMORIA
//
// No generamos aquí todos los párrafos en HTML.
// PHP únicamente entrega un array JavaScript.
//
// El navegador mantiene toda la historia en memoria,
// pero en el DOM solamente mostramos el párrafo actual.
// ============================================================

const parrafos = <?= json_encode(
    $datos,
    JSON_UNESCAPED_UNICODE |
    JSON_UNESCAPED_SLASHES
) ?>;


// ============================================================
// CONFIGURACIÓN
// ============================================================

// Antes teníamos 230 ms.
// Ahora el write-on es aproximadamente 3 veces más rápido.

const velocidad = 75;


// Pequeña pausa cuando termina de "decir" un párrafo.

const pausaEntreParrafos = 650;


// ============================================================
// EMOCIONES
// ============================================================

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
// DOM
// ============================================================

const textoBocadillo =
    document.querySelector(
        "#textoBocadillo"
    );

const cursor =
    document.querySelector(
        "#cursor"
    );

const avatarA =
    document.querySelector(
        "#avatarA"
    );

const avatarB =
    document.querySelector(
        "#avatarB"
    );

const estado =
    document.querySelector(
        "#estado"
    );

const progreso =
    document.querySelector(
        "#progreso"
    );


// ============================================================
// NORMALIZAR EMOCIÓN
// ============================================================

function normalizarEmocion(emocion)
{
    if (!emocion) {
        return "neutral";
    }

    emocion =
        emocion
            .toLowerCase()
            .trim();

    if (!emocionesValidas.includes(emocion)) {
        return "neutral";
    }

    return emocion;
}


// ============================================================
// RUTA DE LA IMAGEN
// ============================================================

function imagenEmocion(emocion)
{
    emocion =
        normalizarEmocion(
            emocion
        );

    return (
        "expresiones/" +
        emocion +
        ".png"
    );
}


// ============================================================
// PRECARGAR AVATARES
// ============================================================

const imagenesPrecargadas = {};

emocionesValidas.forEach(
    emocion => {

        const imagen =
            new Image();

        imagen.src =
            imagenEmocion(
                emocion
            );

        imagenesPrecargadas[emocion] =
            imagen;
    }
);


// ============================================================
// CONTAR PALABRAS
//
// Esto se hace únicamente en memoria.
// No se crean elementos HTML.
// ============================================================

let totalPalabrasHistoria = 0;

parrafos.forEach(
    parrafo => {

        const palabras =
            parrafo.texto
                .trim()
                .split(/\s+/);

        totalPalabrasHistoria +=
            palabras.length;
    }
);


// ============================================================
// CROSSFADING DE EMOCIONES
// ============================================================

function mezclarEmociones(
    emocionOrigen,
    emocionDestino,
    factor
)
{
    emocionOrigen =
        normalizarEmocion(
            emocionOrigen
        );

    emocionDestino =
        normalizarEmocion(
            emocionDestino
        );

    factor =
        Math.max(
            0,
            Math.min(
                1,
                factor
            )
        );


    // --------------------------------------------------------
    // MISMA EMOCIÓN
    // --------------------------------------------------------

    if (
        emocionOrigen ===
        emocionDestino
    ) {

        avatarA.src =
            imagenEmocion(
                emocionOrigen
            );

        avatarA.style.opacity =
            1;

        avatarB.style.opacity =
            0;

        return;
    }


    // --------------------------------------------------------
    // EMOCIONES DIFERENTES
    // --------------------------------------------------------

    avatarA.src =
        imagenEmocion(
            emocionOrigen
        );

    avatarB.src =
        imagenEmocion(
            emocionDestino
        );

    avatarA.style.opacity =
        1 - factor;

    avatarB.style.opacity =
        factor;
}


// ============================================================
// ACTUALIZAR AVATAR
//
// Conservamos tu comportamiento:
//
// centro párrafo N
//          ↓
// emoción N = 100%
//
// centro párrafo N+1
//          ↓
// emoción N+1 = 100%
//
// El crossfade ocurre continuamente entre ambos.
// ============================================================

function actualizarAvatar(
    indiceParrafo,
    indicePalabra,
    totalPalabras
)
{
    let posicion;

    if (totalPalabras <= 1) {

        posicion = 0.5;

    } else {

        posicion =
            indicePalabra /
            (totalPalabras - 1);
    }


    const emocionActual =
        normalizarEmocion(
            parrafos[
                indiceParrafo
            ].emocion
        );


    // ========================================================
    // PRIMERA MITAD
    // ========================================================

    if (posicion < 0.5) {

        let emocionAnterior;

        if (indiceParrafo > 0) {

            emocionAnterior =
                normalizarEmocion(
                    parrafos[
                        indiceParrafo - 1
                    ].emocion
                );

        } else {

            emocionAnterior =
                "neutral";
        }


        const factor =
            0.5 +
            posicion;


        mezclarEmociones(
            emocionAnterior,
            emocionActual,
            factor
        );
    }


    // ========================================================
    // SEGUNDA MITAD
    // ========================================================

    else {

        let emocionSiguiente;

        if (
            indiceParrafo <
            parrafos.length - 1
        ) {

            emocionSiguiente =
                normalizarEmocion(
                    parrafos[
                        indiceParrafo + 1
                    ].emocion
                );

        } else {

            emocionSiguiente =
                "neutral";
        }


        const factor =
            posicion -
            0.5;


        mezclarEmociones(
            emocionActual,
            emocionSiguiente,
            factor
        );
    }


    // ========================================================
    // DEBUG
    // ========================================================

    const datos =
        parrafos[indiceParrafo];


    estado.innerHTML = `

        <div class="emocion">
            ${datos.emocion.toUpperCase()}
        </div>

        <div>
            Párrafo:
            ${indiceParrafo + 1}
            /
            ${parrafos.length}
        </div>

        <div>
            Posición:
            ${Math.round(posicion * 100)}%
        </div>

        <div>
            Valencia:
            ${datos.valencia}
        </div>

        <div>
            Intensidad:
            ${datos.intensidad}
        </div>

        <div>
            Activación:
            ${datos.activacion}
        </div>
    `;
}


// ============================================================
// ESPERAR
// ============================================================

function esperar(ms)
{
    return new Promise(
        resolve => {

            setTimeout(
                resolve,
                ms
            );

        }
    );
}


// ============================================================
// MOSTRAR UNA PALABRA
// ============================================================

function escribirPalabra(
    palabra,
    primera
)
{
    const span =
        document.createElement(
            "span"
        );

    span.className =
        "palabra";

    if (!primera) {

        textoBocadillo.appendChild(
            document.createTextNode(" ")
        );
    }

    span.textContent =
        palabra;

    textoBocadillo.appendChild(
        span
    );


    // Forzamos reflow para que funcione
    // la transición de opacidad.

    span.offsetWidth;


    span.classList.add(
        "visible"
    );
}


// ============================================================
// REPRODUCIR HISTORIA
// ============================================================

async function reproducir()
{
    let palabrasReproducidas = 0;


    // ========================================================
    // RECORRER PÁRRAFOS
    // ========================================================

    for (
        let indiceParrafo = 0;
        indiceParrafo < parrafos.length;
        indiceParrafo++
    ) {

        const parrafo =
            parrafos[indiceParrafo];


        // ----------------------------------------------------
        // El párrafo anterior desaparece completamente.
        //
        // En el DOM sólo existirá el párrafo actual.
        // ----------------------------------------------------

        textoBocadillo.innerHTML =
            "";


        const palabras =
            parrafo.texto
                .trim()
                .split(/\s+/);


        const totalPalabras =
            palabras.length;


        // ====================================================
        // WRITE-ON
        // ====================================================

        for (
            let i = 0;
            i < totalPalabras;
            i++
        ) {

            escribirPalabra(
                palabras[i],
                i === 0
            );


            // ------------------------------------------------
            // EXPRESIÓN DEL AVATAR
            // ------------------------------------------------

            actualizarAvatar(
                indiceParrafo,
                i,
                totalPalabras
            );


            // ------------------------------------------------
            // PROGRESO GLOBAL
            // ------------------------------------------------

            palabrasReproducidas++;


            const porcentaje =
                (
                    palabrasReproducidas /
                    totalPalabrasHistoria
                ) * 100;


            progreso.style.width =
                porcentaje + "%";


            // ------------------------------------------------
            // VELOCIDAD
            // ------------------------------------------------

            await esperar(
                velocidad
            );
        }


        // ====================================================
        // PÁRRAFO COMPLETO
        //
        // Lo dejamos un momento para que pueda terminar
        // de leerse antes de sustituirlo.
        // ====================================================

        await esperar(
            pausaEntreParrafos
        );
    }


    // ========================================================
    // FINAL
    // ========================================================

    avatarA.src =
        imagenEmocion(
            "neutral"
        );

    avatarA.style.opacity =
        1;

    avatarB.style.opacity =
        0;


    progreso.style.width =
        "100%";


    cursor.style.display =
        "none";


    estado.innerHTML = `

        <div class="emocion">
            NEUTRAL
        </div>

        <div>
            Historia finalizada
        </div>
    `;
}


// ============================================================
// INICIAR
// ============================================================

reproducir();

</script>

</body>
</html>