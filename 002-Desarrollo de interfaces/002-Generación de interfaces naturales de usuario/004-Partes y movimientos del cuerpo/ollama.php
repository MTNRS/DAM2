<?php

header("Content-Type: application/json; charset=utf-8");


// Recoger JSON enviado desde JavaScript

$entrada = json_decode(
    file_get_contents("php://input"),
    true
);

$mensaje = $entrada["mensaje"] ?? "";


// Petición para Ollama

$datos = [
    "model" => "phi3:mini",

    "prompt" => $mensaje,

    "stream" => false
];


// Conectar con Ollama

$ch = curl_init(
    "http://localhost:11434/api/generate"
);

curl_setopt(
    $ch,
    CURLOPT_POST,
    true
);

curl_setopt(
    $ch,
    CURLOPT_HTTPHEADER,
    [
        "Content-Type: application/json"
    ]
);

curl_setopt(
    $ch,
    CURLOPT_POSTFIELDS,
    json_encode($datos)
);

curl_setopt(
    $ch,
    CURLOPT_RETURNTRANSFER,
    true
);


// Ejecutar

$respuesta = curl_exec($ch);

curl_close($ch);


// Decodificar respuesta de Ollama

$resultado = json_decode(
    $respuesta,
    true
);


// Obtener únicamente el texto

$texto = $resultado["response"] ?? "No he podido responder.";


// Responder al navegador

echo json_encode([
    "respuesta" => $texto
]);