<?php

// ============================================================
// CONFIGURACIÓN
// ============================================================

// No enviar warnings PHP al navegador,
// porque romperían el JSON.

ini_set(
    "display_errors",
    0
);

error_reporting(
    E_ALL
);


header(
    "Content-Type: application/json; charset=utf-8"
);


header(
    "Cache-Control: no-store, no-cache, must-revalidate, max-age=0"
);


$archivoParticipantes =
    __DIR__ .
    "/participantes.json";

$archivoTrabajos =
    __DIR__ .
    "/trabajos.json";

$archivoResultados =
    __DIR__ .
    "/resultados.json";


// ============================================================
// RESPONDER JSON
// ============================================================

function responder($datos){

    echo json_encode(

        $datos,

        JSON_PRETTY_PRINT |
        JSON_UNESCAPED_UNICODE

    );

    exit;

}


// ============================================================
// CARGAR JSON
// ============================================================

function cargarJSON($archivo){

    if(
        !file_exists($archivo)
    ){

        return [];

    }


    $contenido =
        file_get_contents(
            $archivo
        );


    if(
        $contenido === false ||
        trim($contenido) === ""
    ){

        return [];

    }


    $datos =
        json_decode(
            $contenido,
            true
        );


    if(
        !is_array($datos)
    ){

        return [];

    }


    return $datos;

}


// ============================================================
// GUARDAR JSON
// ============================================================

function guardarJSON(
    $archivo,
    $datos
){

    $json =
        json_encode(

            $datos,

            JSON_PRETTY_PRINT |
            JSON_UNESCAPED_UNICODE

        );


    if(
        $json === false
    ){

        return false;

    }


    return file_put_contents(

        $archivo,

        $json,

        LOCK_EX

    );

}


// ============================================================
// CARGAR DATOS
// ============================================================

$participantes =
    cargarJSON(
        $archivoParticipantes
    );

$trabajos =
    cargarJSON(
        $archivoTrabajos
    );

$resultados =
    cargarJSON(
        $archivoResultados
    );


// ============================================================
// LIMPIAR ESCLAVOS DESCONECTADOS
// ============================================================
//
// Si un esclavo no se comunica durante más de
// 5 segundos lo eliminamos.
// ============================================================

$ahora =
    time();


foreach(
    $participantes
    as $hash => $datos
){

    if(

        !isset(
            $datos["ultimo_latido"]
        )

        ||

        $ahora -
        $datos["ultimo_latido"]
        > 5

    ){

        unset(
            $participantes[$hash]
        );

    }

}


guardarJSON(

    $archivoParticipantes,

    $participantes

);


// ============================================================
// LEER POST
// ============================================================

$entrada = [];


if(
    $_SERVER["REQUEST_METHOD"]
    === "POST"
){

    $contenidoEntrada =
        file_get_contents(
            "php://input"
        );


    $entrada =
        json_decode(
            $contenidoEntrada,
            true
        );


    if(
        !is_array($entrada)
    ){

        responder([

            "estado" =>
                "error",

            "mensaje" =>
                "JSON POST no válido"

        ]);

    }

}


// ============================================================
// POST
// CREAR TRABAJO
// ============================================================

if(

    $_SERVER["REQUEST_METHOD"]
    === "POST"

    &&

    isset(
        $entrada["accion"]
    )

    &&

    $entrada["accion"]
    === "crear_trabajo"

){

    if(

        !isset(
            $entrada["maestro"]
        )

        ||

        !isset(
            $entrada["datos"]
        )

    ){

        responder([

            "estado" =>
                "error",

            "mensaje" =>
                "Faltan datos del trabajo"

        ]);

    }


    // --------------------------------------------------------
    // Identificador del trabajo
    // --------------------------------------------------------

    $id =
        uniqid(
            "trabajo_",
            true
        );


    // --------------------------------------------------------
    // Crear trabajo
    // --------------------------------------------------------

    $trabajos[$id] = [

        "id" =>
            $id,

        "maestro" =>
            $entrada["maestro"],

        "datos" =>
            $entrada["datos"],

        "estado" =>
            "pendiente",

        "esclavo" =>
            null,

        "creado" =>
            time()

    ];


    guardarJSON(

        $archivoTrabajos,

        $trabajos

    );


    responder([

        "estado" =>
            "ok",

        "id" =>
            $id

    ]);

}


// ============================================================
// POST
// RECIBIR RESULTADO
// ============================================================

if(

    $_SERVER["REQUEST_METHOD"]
    === "POST"

    &&

    isset(
        $entrada["accion"]
    )

    &&

    $entrada["accion"]
    === "resultado"

){

    if(

        !isset(
            $entrada["trabajo"]
        )

        ||

        !isset(
            $entrada["hash"]
        )

        ||

        !isset(
            $entrada["resultado"]
        )

    ){

        responder([

            "estado" =>
                "error",

            "mensaje" =>
                "Faltan datos del resultado"

        ]);

    }


    $idTrabajo =
        $entrada["trabajo"];


    // --------------------------------------------------------
    // Comprobar que existe
    // --------------------------------------------------------

    if(
        !isset(
            $trabajos[$idTrabajo]
        )
    ){

        responder([

            "estado" =>
                "error",

            "mensaje" =>
                "Trabajo no encontrado"

        ]);

    }


    // --------------------------------------------------------
    // Marcar completado
    // --------------------------------------------------------

    $trabajos
        [$idTrabajo]
        ["estado"]
        =
        "completado";


    $trabajos
        [$idTrabajo]
        ["finalizado"]
        =
        time();


    // --------------------------------------------------------
    // Guardar resultado
    // --------------------------------------------------------

    $resultados[] = [

        "trabajo" =>
            $idTrabajo,

        "esclavo" =>
            $entrada["hash"],

        "resultado" =>
            $entrada["resultado"],

        "fecha" =>
            time()

    ];


    guardarJSON(

        $archivoTrabajos,

        $trabajos

    );


    guardarJSON(

        $archivoResultados,

        $resultados

    );


    responder([

        "estado" =>
            "ok"

    ]);

}


// ============================================================
// ACCIÓN GET
// ============================================================

$accion =
    $_GET["accion"]
    ?? "";


// ============================================================
// GET
// PARTICIPANTES
// ============================================================

if(
    $accion ===
    "participantes"
){

    responder(

        array_values(
            $participantes
        )

    );

}


// ============================================================
// GET
// PEDIR TRABAJO
//
// IMPORTANTE:
//
// Esta petición también funciona como LATIDO.
// Por tanto, aunque no haya ningún trabajo,
// el esclavo queda registrado como conectado.
// ============================================================

if(
    $accion ===
    "pedir_trabajo"
){

    $hash =
        $_GET["hash"]
        ?? null;


    if(!$hash){

        responder(null);

    }


    // ========================================================
    // REGISTRAR ESCLAVO
    // ========================================================

    $participantes[$hash] = [

        "hash" =>
            $hash,

        "ultimo_latido" =>
            time()

    ];


    guardarJSON(

        $archivoParticipantes,

        $participantes

    );


    // ========================================================
    // BUSCAR TRABAJO
    // ========================================================

    $trabajoEncontrado =
        null;


    foreach(
        $trabajos
        as $id => $trabajo
    ){

        if(

            isset(
                $trabajo["estado"]
            )

            &&

            $trabajo["estado"]
            === "pendiente"

        ){

            // ------------------------------------------------
            // Reservar para este esclavo
            // ------------------------------------------------

            $trabajos
                [$id]
                ["estado"]
                =
                "procesando";


            $trabajos
                [$id]
                ["esclavo"]
                =
                $hash;


            $trabajos
                [$id]
                ["inicio"]
                =
                time();


            $trabajoEncontrado =
                $trabajos[$id];


            break;

        }

    }


    // ========================================================
    // SI ENCONTRAMOS TRABAJO
    // ========================================================

    if(
        $trabajoEncontrado
        !== null
    ){

        guardarJSON(

            $archivoTrabajos,

            $trabajos

        );


        responder(
            $trabajoEncontrado
        );

    }


    // ========================================================
    // NO HAY TRABAJO
    // ========================================================

    responder(null);

}


// ============================================================
// GET
// TRABAJOS
// ============================================================

if(
    $accion ===
    "trabajos"
){

    responder(

        array_values(
            $trabajos
        )

    );

}


// ============================================================
// GET
// RESULTADOS
// ============================================================

if(
    $accion ===
    "resultados"
){

    responder(

        array_values(
            $resultados
        )

    );

}


// ============================================================
// ACCIÓN DESCONOCIDA
// ============================================================

responder([

    "estado" =>
        "error",

    "mensaje" =>
        "Acción desconocida"

]);