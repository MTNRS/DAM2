<?php

header("Content-Type: application/json");

$archivoParticipantes =
    __DIR__ . "/participantes.json";

$archivoTrabajos =
    __DIR__ . "/trabajos.json";

$archivoResultados =
    __DIR__ . "/resultados.json";


// ============================================
// FUNCIÓN CARGAR JSON
// ============================================

function cargarJSON($archivo){

    if(!file_exists($archivo)){
        return [];
    }

    $contenido =
        file_get_contents($archivo);

    $datos =
        json_decode(
            $contenido,
            true
        );

    if(!is_array($datos)){
        return [];
    }

    return $datos;

}


// ============================================
// FUNCIÓN GUARDAR JSON
// ============================================

function guardarJSON(
    $archivo,
    $datos
){

    file_put_contents(

        $archivo,

        json_encode(
            $datos,
            JSON_PRETTY_PRINT
        ),

        LOCK_EX

    );

}


// ============================================
// CARGAR DATOS
// ============================================

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


// ============================================
// LIMPIAR ESCLAVOS MUERTOS
// ============================================

$ahora = time();

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


// ============================================
// POST
// ============================================

if(
    $_SERVER["REQUEST_METHOD"]
    === "POST"
){

    $entrada =
        json_decode(
            file_get_contents(
                "php://input"
            ),
            true
        );


    // ========================================
    // LATIDO
    // ========================================

    if(
        isset($entrada["accion"])
        &&
        $entrada["accion"]
        === "latido"
    ){

        $hash =
            $entrada["hash"];

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


        echo json_encode([
            "estado" => "ok"
        ]);

        exit;

    }


    // ========================================
    // CREAR TRABAJO
    // ========================================

    if(
        isset($entrada["accion"])
        &&
        $entrada["accion"]
        === "crear_trabajo"
    ){

        $id =
            uniqid(
                "trabajo_"
            );


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


        echo json_encode([

            "estado" =>
                "ok",

            "id" =>
                $id

        ]);

        exit;

    }


    // ========================================
    // RECIBIR RESULTADO
    // ========================================

    if(
        isset($entrada["accion"])
        &&
        $entrada["accion"]
        === "resultado"
    ){

        $idTrabajo =
            $entrada["trabajo"];


        if(
            isset(
                $trabajos[$idTrabajo]
            )
        ){

            $trabajos[$idTrabajo]
                ["estado"]
                = "completado";


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


            echo json_encode([
                "estado" => "ok"
            ]);

            exit;

        }

    }

}


// ============================================
// GET
// ============================================

$accion =
    $_GET["accion"]
    ?? "";


// ============================================
// PARTICIPANTES
// ============================================

if(
    $accion
    === "participantes"
){

    echo json_encode(
        array_values(
            $participantes
        )
    );

    exit;

}


// ============================================
// PEDIR TRABAJO
// ============================================

if(
    $accion
    === "pedir_trabajo"
){

    $hash =
        $_GET["hash"]
        ?? null;


    if(!$hash){

        echo json_encode(null);

        exit;

    }


    foreach(
        $trabajos
        as $id => &$trabajo
    ){

        if(
            $trabajo["estado"]
            === "pendiente"
        ){

            // Lo reservamos para este esclavo

            $trabajo["estado"] =
                "procesando";

            $trabajo["esclavo"] =
                $hash;

            $trabajo["inicio"] =
                time();


            guardarJSON(
                $archivoTrabajos,
                $trabajos
            );


            echo json_encode(
                $trabajo
            );

            exit;

        }

    }


    // No hay trabajo disponible

    echo json_encode(null);

    exit;

}


// ============================================
// TRABAJOS
// ============================================

if(
    $accion
    === "trabajos"
){

    echo json_encode(
        array_values(
            $trabajos
        )
    );

    exit;

}


// ============================================
// RESULTADOS
// ============================================

if(
    $accion
    === "resultados"
){

    echo json_encode(
        array_values(
            $resultados
        )
    );

    exit;

}


// ============================================
// ACCIÓN DESCONOCIDA
// ============================================

echo json_encode([

    "estado" =>
        "error",

    "mensaje" =>
        "Acción desconocida"

]);