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


// ----------------------------------------------------
// PASADA 1
// Generar texto con diferentes emociones
// ----------------------------------------------------

$promptGeneracion = <<<PROMPT

Escribe un texto en español compuesto por 6 párrafos.

Cada párrafo debe expresar claramente una emoción diferente:

1. Alegría
2. Tristeza
3. Enfado
4. Miedo
5. Sorpresa
6. Calma

IMPORTANTE:

No escribas el nombre de la emoción.
No numeres los párrafos.
No expliques qué emoción estás utilizando.

Simplemente escribe los párrafos.

Cada párrafo debe tener entre 3 y 5 frases.

Separa cada párrafo mediante una línea en blanco.

PROMPT;


$texto = ollama($promptGeneracion);


// ----------------------------------------------------
// Mostrar texto generado
// ----------------------------------------------------

echo "<h1>Texto generado</h1>";

echo '<div style="max-width:800px;line-height:1.6">';
echo nl2br(htmlspecialchars($texto));
echo '</div>';


// ----------------------------------------------------
// PASADA 2
// Analizar emocionalmente el texto
// ----------------------------------------------------

$promptAnalisis = <<<PROMPT

Analiza emocionalmente cada uno de los párrafos del siguiente texto.

Para cada párrafo determina:

- emocion: emoción principal
- valencia: valor entre -1 y 1
- intensidad: valor entre 0 y 1
- activacion: valor entre 0 y 1

Donde:

valencia:
-1 = extremadamente negativa
 0 = neutra
 1 = extremadamente positiva

intensidad:
0 = emoción prácticamente inexistente
1 = emoción extremadamente intensa

activacion:
0 = estado muy tranquilo
1 = estado extremadamente excitado o energético

Devuelve ÚNICAMENTE JSON válido.

Formato exacto:

[
    {
        "parrafo": 1,
        "emocion": "alegria",
        "valencia": 0.8,
        "intensidad": 0.7,
        "activacion": 0.6
    }
]

No escribas markdown.
No uses ```json.
No añadas explicaciones.

TEXTO A ANALIZAR:

$texto

PROMPT;


$analisis = ollama($promptAnalisis);


// ----------------------------------------------------
// Mostrar resultado
// ----------------------------------------------------

echo "<h1>Análisis emocional</h1>";

echo '<pre>';
echo htmlspecialchars($analisis);
echo '</pre>';

?>