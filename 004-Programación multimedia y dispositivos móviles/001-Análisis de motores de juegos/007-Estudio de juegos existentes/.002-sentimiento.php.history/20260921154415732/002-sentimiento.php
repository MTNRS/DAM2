<?php

$url = 'http://localhost:11434/api/generate';

$prompt = <<<PROMPT
Escribe un texto en español compuesto por 6 párrafos.

Cada párrafo debe expresar claramente una emoción diferente:
1. Alegría
2. Tristeza
3. Enfado
4. Miedo
5. Sorpresa
6. Calma

No escribas el nombre de la emoción.
Simplemente escribe cada párrafo de forma que la emoción se pueda deducir
por el contenido, el vocabulario y el tono.

Cada párrafo debe tener entre 3 y 5 frases.
Separa claramente los párrafos con una línea en blanco.
PROMPT;

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

if (isset($result['response'])) {
    echo nl2br(
        htmlspecialchars($result['response'])
    );
} else {
    echo '<pre>';
    print_r($result);
    echo '</pre>';
}
?>