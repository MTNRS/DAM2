<?php

header("Content-Type: application/json");

$archivo = __DIR__ . "/participantes.json";


// --------------------------------------
// Cargar participantes
// --------------------------------------

if(file_exists($archivo)){

    $contenido = file_get_contents($archivo);

    $participantes = json_decode($contenido,true);

    if(!is_array($participantes)){
        $participantes = [];
    }

}else{

    $participantes = [];

}


// --------------------------------------
// Limpiar esclavos muertos
// --------------------------------------

// Si no han enviado latido en 5 segundos
// consideramos que ya no están conectados

$ahora = time();

foreach($participantes as $hash => $datos){

    if(
        !isset($datos["ultimo_latido"]) ||
        $ahora - $datos["ultimo_latido"] > 5
    ){

        unset($participantes[$hash]);

    }

}


// --------------------------------------
// POST
// --------------------------------------

if($_SERVER["REQUEST_METHOD"] === "POST"){

    $entrada = json_decode(
        file_get_contents("php://input"),
        true
    );


    if(
        isset($entrada["accion"]) &&
        $entrada["accion"] === "latido" &&
        isset($entrada["hash"])
    ){

        $hash = $entrada["hash"];

        $participantes[$hash] = [

            "hash" => $hash,

            "ultimo_latido" => time()

        ];


        file_put_contents(
            $archivo,
            json_encode(
                $participantes,
                JSON_PRETTY_PRINT
            ),
            LOCK_EX
        );


        echo json_encode([
            "estado" => "ok"
        ]);

        exit;

    }

}


// --------------------------------------
// GET participantes
// --------------------------------------

if(
    isset($_GET["accion"]) &&
    $_GET["accion"] === "participantes"
){

    // Guardamos también la limpieza
    file_put_contents(
        $archivo,
        json_encode(
            $participantes,
            JSON_PRETTY_PRINT
        ),
        LOCK_EX
    );

    echo json_encode(
        array_values($participantes)
    );

    exit;

}


// --------------------------------------
// Acción desconocida
// --------------------------------------

echo json_encode([
    "estado" => "error",
    "mensaje" => "Acción desconocida"
]);